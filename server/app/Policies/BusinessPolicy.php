<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('seller');
    }

    public function view(User $user, Business $business): bool
    {
        return (int) $business->owner_user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('seller');
    }

    public function update(User $user, Business $business): bool
    {
        return (int) $business->owner_user_id === (int) $user->id;
    }

    public function delete(User $user, Business $business): bool
    {
        return (int) $business->owner_user_id === (int) $user->id;
    }
}
