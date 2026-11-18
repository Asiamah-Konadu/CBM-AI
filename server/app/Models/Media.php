<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id', 'listing_id', 'uploaded_by_user_id', 'purpose', 'storage_disk',
    'storage_key', 'original_filename', 'mime_type', 'byte_size', 'is_public', 'sort_order',
])]
#[Hidden(['storage_disk', 'storage_key'])]
class Media extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'byte_size' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
