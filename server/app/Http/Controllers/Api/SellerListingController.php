<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Business;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerListingController extends Controller
{
    public function index(Business $business): JsonResponse
    {
        Gate::authorize('viewAny', [Listing::class, $business]);

        return ListingResource::collection(
            $business->listings()->with('category')->latest()->get(),
        )->response();
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        Gate::authorize('create', [Listing::class, $business]);
        $data = $request->validate($this->rules());
        $this->ensurePublishable($business, $data['listing_status'] ?? 'draft');

        if (in_array($data['price_type'], ['quote', 'not_listed'], true) && ($data['price_amount'] ?? null) !== null) {
            return response()->json(['message' => 'Do not include a price amount for quote-only or unlisted pricing.'], 422);
        }

        $listing = $business->listings()->create([
            ...$this->listingFields($data),
            'slug' => $this->uniqueSlug($business, $data['title']),
            'currency_code' => $data['currency_code'] ?? $business->currency_code,
            'last_confirmed_at' => array_key_exists('availability_status', $data) ? now() : null,
            'published_at' => ($data['listing_status'] ?? 'draft') === 'published' ? now() : null,
        ]);

        return (new ListingResource($listing->load('category')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Listing $listing): ListingResource|JsonResponse
    {
        Gate::authorize('update', $listing);
        $data = $request->validate($this->rules(true));
        $business = $listing->business;
        $this->ensurePublishable($business, $data['listing_status'] ?? $listing->listing_status);

        $fields = $this->listingFields($data);
        $effectivePriceType = $data['price_type'] ?? $listing->price_type;
        $effectivePriceAmount = array_key_exists('price_amount', $data) ? $data['price_amount'] : $listing->price_amount;
        if (in_array($effectivePriceType, ['quote', 'not_listed'], true) && $effectivePriceAmount !== null) {
            return response()->json(['message' => 'Do not include a price amount for quote-only or unlisted pricing.'], 422);
        }
        if (in_array($effectivePriceType, ['fixed', 'starting_from'], true) && $effectivePriceAmount === null) {
            return response()->json(['message' => 'A price amount is required for this price type.'], 422);
        }
        if (in_array($effectivePriceType, ['quote', 'not_listed'], true)) {
            $fields['price_amount'] = null;
        }
        if (array_key_exists('title', $data)) {
            $fields['slug'] = $this->uniqueSlug($business, $data['title'], $listing->id);
        }
        if (array_key_exists('availability_status', $data) || array_key_exists('available_quantity', $data)) {
            $fields['last_confirmed_at'] = now();
        }
        if (array_key_exists('listing_status', $data)) {
            $fields['published_at'] = $data['listing_status'] === 'published' ? ($listing->published_at ?? now()) : null;
        }

        $listing->update($fields);

        return new ListingResource($listing->fresh()->load('category'));
    }

    public function destroy(Listing $listing): JsonResponse
    {
        Gate::authorize('delete', $listing);
        $listing->delete();

        return response()->json(['message' => 'Listing deleted.']);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'category_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('is_active', true)->whereIn('applies_to', ['listing', 'both'])),
            ],
            'listing_type' => [$required, Rule::in(['product', 'service'])],
            'title' => [$required, 'string', 'max:180'],
            'description' => [$required, 'string', 'max:10000'],
            'keywords' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'listing_status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'price_type' => [$required, Rule::in(['fixed', 'starting_from', 'quote', 'not_listed'])],
            'price_amount' => ['nullable', 'numeric', 'min:0', 'required_if:price_type,fixed,starting_from'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'availability_status' => ['sometimes', Rule::in(['in_stock', 'limited', 'made_to_order', 'out_of_stock', 'unknown'])],
            'available_quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'minimum_order_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'condition' => ['sometimes', 'nullable', Rule::in(['new', 'used', 'refurbished', 'not_applicable'])],
        ];
    }

    private function listingFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'category_id', 'listing_type', 'title', 'description', 'keywords',
            'listing_status', 'price_type', 'price_amount', 'currency_code',
            'availability_status', 'available_quantity', 'minimum_order_quantity', 'condition',
        ]));
    }

    private function ensurePublishable(Business $business, string $status): void
    {
        abort_unless($status !== 'published' || $business->business_status === 'published', 422,
            'Publish the business profile before publishing its listings.');
    }

    private function uniqueSlug(Business $business, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'listing';
        $slug = $base;
        $suffix = 2;

        while ($business->listings()->withTrashed()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
