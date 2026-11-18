<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->business_status,
            'verification' => [
                'verified' => $this->isVerified(),
                'status' => $this->when(
                    $request->user() !== null && (int) $request->user()->id === (int) $this->owner_user_id,
                    $this->verification_status,
                ),
                'verified_at' => $this->verified_at?->toISOString(),
            ],
            'category' => $this->whenLoaded('primaryCategory', fn () => [
                'id' => $this->primaryCategory?->id,
                'name' => $this->primaryCategory?->name,
                'slug' => $this->primaryCategory?->slug,
            ]),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'primary' => (bool) $category->pivot->is_primary,
            ])),
            'contact' => [
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp_phone,
                'website' => $this->website_url,
            ],
            'address' => [
                'line' => $this->address_line,
                'neighborhood' => $this->neighborhood,
                'city' => $this->city,
                'region' => $this->region,
                'country_code' => $this->country_code,
            ],
            'location' => [
                'latitude' => $this->latitude === null ? null : (float) $this->latitude,
                'longitude' => $this->longitude === null ? null : (float) $this->longitude,
            ],
            'currency' => $this->currency_code,
            'opening_hours' => $this->opening_hours,
            'listings' => ListingResource::collection($this->whenLoaded('listings')),
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
