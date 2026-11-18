<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(User $user, Business $business): bool
    {
        return (int) $business->owner_user_id === (int) $user->id;
    }

    public function create(User $user, Business $business): bool
    {
        return $business->owner_user_id === $user->id;
    }

    public function update(User $user, Listing $listing): bool
    {
        return $listing->business()->where('owner_user_id', $user->id)->exists();
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $listing->business()->where('owner_user_id', $user->id)->exists();
    }
}
