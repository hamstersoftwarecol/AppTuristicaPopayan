<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id || $this->manage($user, $booking);
    }

    public function manage(User $user, Booking $booking): bool
    {
        return $user->can('bookings.manage') || ($user->can('inventory.manage-own') && $booking->provider->owner_id === $user->id);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id || $this->manage($user, $booking);
    }
}
