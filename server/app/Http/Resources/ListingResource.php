<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->listing_type,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'status' => $this->listing_status,
            'price' => [
                'type' => $this->price_type,
                'amount' => $this->price_amount,
                'currency' => $this->currency_code,
            ],
            'availability' => [
                'status' => $this->availability_status,
                'quantity' => $this->available_quantity,
                'minimum_order_quantity' => $this->minimum_order_quantity,
                'last_confirmed_at' => $this->last_confirmed_at?->toISOString(),
            ],
            'condition' => $this->condition,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
