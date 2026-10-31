<?php

namespace App\Policies;

use App\Models\TourismService;
use App\Models\User;

class TourismServicePolicy
{
    public function update(User $user, TourismService $service): bool
    {
        return $user->can('services.manage') || ($user->can('inventory.manage-own') && $service->provider->owner_id === $user->id);
    }
}
