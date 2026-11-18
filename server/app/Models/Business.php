<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'owner_user_id', 'primary_category_id', 'name', 'slug', 'description', 'business_status',
    'verification_status', 'verified_at', 'verification_expires_at', 'phone', 'whatsapp_phone',
    'website_url', 'address_line', 'neighborhood', 'city', 'region', 'country_code',
    'currency_code', 'opening_hours', 'published_at',
])]
#[Hidden(['location'])]
class Business extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'verified_at' => 'immutable_datetime',
            'verification_expires_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'business_categories', 'business_id', 'category_id')
            ->withPivot('is_primary');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function verificationSubmissions(): HasMany
    {
        return $this->hasMany(VerificationSubmission::class);
    }

    public function scopePublished($query)
    {
        return $query->where('business_status', 'published');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified'
            && ($this->verification_expires_at === null || $this->verification_expires_at->isFuture());
    }
}
