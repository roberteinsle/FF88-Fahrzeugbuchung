<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Services\BookingService;
use Livewire\Component;

class BookingDetail extends Component
{
    public int $bookingId;

    public bool $confirmCancel = false;

    public function cancel(BookingService $service): void
    {
        $booking = Booking::findOrFail($this->bookingId);
        $this->authorize('cancel', $booking);

        $service->cancel($booking);

        $this->dispatch('close-detail-modal');
        $this->dispatch('calendar-refresh');
        session()->flash('success', 'Buchung storniert.');
    }

    public function editBooking(): void
    {
        $this->dispatch('close-detail-modal');
        $this->dispatch('open-edit-booking', bookingId: $this->bookingId);
    }

    public function render()
    {
        $booking = Booking::with(['vehicle', 'user', 'group'])->findOrFail($this->bookingId);

        return view('livewire.booking.booking-detail', [
            'booking' => $booking,
            'canEdit' => auth()->user()?->can('update', $booking) ?? false,
            'canCancel' => auth()->user()?->can('cancel', $booking) ?? false,
        ]);
    }
}
