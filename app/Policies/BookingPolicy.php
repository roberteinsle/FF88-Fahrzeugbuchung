<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Booking $booking): bool
    {
        // Conflict requests are decided, not edited
        if ($booking->isCancelled() || $booking->isPending() || $booking->isRejected()) {
            return false;
        }

        // Only admins can edit past bookings
        if ($booking->starts_at->isPast() && ! $user->is_admin) {
            return false;
        }

        return $user->is_admin || $booking->user_id === $user->id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($booking->isCancelled() || $booking->isRejected()) {
            return false;
        }

        // Only admins can cancel past bookings
        if ($booking->starts_at->isPast() && ! $user->is_admin) {
            return false;
        }

        return $user->is_admin || $booking->user_id === $user->id;
    }

    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->is_admin;
    }
}
