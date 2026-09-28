<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Services\BookingService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MyBookings extends Component
{
    use WithPagination;

    public string $filter = 'upcoming'; // upcoming | past | cancelled

    public ?int $editingBookingId = null;

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function editBooking(int $bookingId): void
    {
        $this->dispatch('open-edit-booking', bookingId: $bookingId);
    }

    /** Re-render after the booking form saved a change */
    #[On('calendar-refresh')]
    public function refreshList(): void {}

    public function cancelBooking(int $bookingId, BookingService $service): void
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorize('cancel', $booking);
        $service->cancel($booking);
        session()->flash('success', 'Buchung storniert.');
    }

    public function render()
    {
        $query = Booking::with(['vehicle', 'group'])
            ->where('user_id', auth()->id())
            ->orderBy('starts_at', $this->filter === 'past' ? 'desc' : 'asc');

        $query = match ($this->filter) {
            'past' => $query->visible()->where('ends_at', '<', now()),
            'cancelled' => $query->where(fn ($q) => $q->whereNotNull('cancelled_at')->orWhere('status', Booking::STATUS_REJECTED)),
            default => $query->visible()->where('ends_at', '>=', now()),
        };

        return view('livewire.booking.my-bookings', [
            'bookings' => $query->paginate(15),
        ]);
    }
}
