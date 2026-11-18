<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationSubmission;

class VerificationSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin']);
    }

    public function view(User $user, VerificationSubmission $submission): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin']);
    }

    public function decide(User $user, VerificationSubmission $submission): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin']);
    }
}
