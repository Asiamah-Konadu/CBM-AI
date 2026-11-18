<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id', 'category_id', 'listing_type', 'title', 'slug', 'description', 'keywords',
    'listing_status', 'price_type', 'price_amount', 'currency_code', 'availability_status',
    'available_quantity', 'minimum_order_quantity', 'condition', 'last_confirmed_at', 'published_at',
])]
class Listing extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'available_quantity' => 'integer',
            'minimum_order_quantity' => 'integer',
            'last_confirmed_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function scopePublished($query)
    {
        return $query->where('listing_status', 'published');
    }
}
