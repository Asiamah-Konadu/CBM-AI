<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'business_id', 'submitted_by_user_id', 'status', 'seller_note', 'reviewer_user_id',
    'reviewer_note', 'submitted_at', 'reviewed_at',
])]
class VerificationSubmission extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'verification_documents', 'submission_id', 'media_id')
            ->withPivot('document_type');
    }
}
