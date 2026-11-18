<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerBusinessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Business::class);

        $businesses = $request->user()->businesses()
            ->with(['primaryCategory', 'categories'])
            ->select('*')
            ->selectRaw('ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude')
            ->latest()
            ->get();

        return BusinessResource::collection($businesses)->response();
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Business::class);

        $data = $request->validate($this->rules());
        $business = DB::transaction(function () use ($data, $request): Business {
            $business = Business::create([
                ...$this->businessFields($data),
                'owner_user_id' => $request->user()->id,
                'slug' => $this->uniqueSlug($data['name']),
                'business_status' => 'draft',
                'verification_status' => 'unsubmitted',
            ]);

            $business->categories()->sync([
                $data['primary_category_id'] => ['is_primary' => true],
            ]);

            $this->updateLocation($business, $data);

            return $business;
        });

        return (new BusinessResource($this->loadBusiness($business)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Business $business): BusinessResource
    {
        Gate::authorize('view', $business);

        return new BusinessResource($this->loadBusiness($business));
    }

    public function update(Request $request, Business $business): BusinessResource
    {
        Gate::authorize('update', $business);
        $data = $request->validate($this->rules($business));

        DB::transaction(function () use ($business, $data): void {
            $fields = $this->businessFields($data);
            if (array_key_exists('name', $data)) {
                $fields['slug'] = $this->uniqueSlug($data['name'], $business->id);
            }
            $business->update($fields);

            if (array_key_exists('primary_category_id', $data)) {
                $business->categories()->sync([
                    $data['primary_category_id'] => ['is_primary' => true],
                ]);
            }

            if (array_key_exists('latitude', $data) || array_key_exists('longitude', $data)) {
                $this->updateLocation($business, $data);
            }
        });

        return new BusinessResource($this->loadBusiness($business->fresh()));
    }

    public function publish(Business $business): JsonResponse
    {
        Gate::authorize('update', $business);

        if ($business->business_status === 'suspended') {
            return response()->json(['message' => 'This business cannot be published.'], 409);
        }

        $business->update(['business_status' => 'published', 'published_at' => now()]);

        return (new BusinessResource($this->loadBusiness($business->fresh())))->response();
    }

    public function unpublish(Business $business): JsonResponse
    {
        Gate::authorize('update', $business);

        if ($business->business_status === 'suspended') {
            return response()->json(['message' => 'A suspended business cannot change its publication status.'], 409);
        }

        $business->update(['business_status' => 'draft', 'published_at' => null]);

        return (new BusinessResource($this->loadBusiness($business->fresh())))->response();
    }

    private function rules(?Business $business = null): array
    {
        return [
            'name' => [$business ? 'sometimes' : 'required', 'string', 'max:180'],
            'description' => [$business ? 'sometimes' : 'required', 'string', 'max:5000'],
            'primary_category_id' => [
                $business ? 'sometimes' : 'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('is_active', true)->whereIn('applies_to', ['business', 'both'])),
            ],
            'phone' => [$business ? 'sometimes' : 'required', 'string', 'max:40'],
            'whatsapp_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'address_line' => [$business ? 'sometimes' : 'required', 'string', 'max:500'],
            'neighborhood' => ['sometimes', 'nullable', 'string', 'max:120'],
            'city' => [$business ? 'sometimes' : 'required', 'string', 'max:120'],
            'region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'country_code' => ['sometimes', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'opening_hours' => ['sometimes', 'array'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    private function businessFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'primary_category_id', 'name', 'description', 'phone', 'whatsapp_phone',
            'website_url', 'address_line', 'neighborhood', 'city', 'region',
            'country_code', 'currency_code', 'opening_hours',
        ]));
    }

    private function updateLocation(Business $business, array $data): void
    {
        if (($data['latitude'] ?? null) === null || ($data['longitude'] ?? null) === null) {
            DB::statement('UPDATE businesses SET location = NULL WHERE id = ?', [$business->id]);
            return;
        }

        DB::statement(
            'UPDATE businesses SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$data['longitude'], $data['latitude'], $business->id],
        );
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $suffix = 2;

        while (Business::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function loadBusiness(Business $business): Business
    {
        return Business::query()->with(['primaryCategory', 'categories', 'listings.category'])
            ->select('*')
            ->selectRaw('ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude')
            ->findOrFail($business->id);
    }
}
