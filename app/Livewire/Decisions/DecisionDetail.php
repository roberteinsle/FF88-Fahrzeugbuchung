<?php

namespace App\Livewire\Decisions;

use App\Models\BookingDecision;
use App\Services\DecisionService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DecisionDetail extends Component
{
    public BookingDecision $decision;

    public string $note = '';

    public function approve(DecisionService $service): void
    {
        $this->decide($service, true);
    }

    public function reject(DecisionService $service): void
    {
        $this->decide($service, false);
    }

    private function decide(DecisionService $service, bool $approve): void
    {
        $this->authorize('decide-bookings');
        $this->validate(['note' => ['nullable', 'string', 'max:2000']]);

        try {
            $service->decide($this->decision, auth()->user(), $approve, $this->note);
        } catch (ValidationException $e) {
            $this->addError('decision', $e->errors()['decision'][0] ?? 'Entscheidung nicht möglich.');
        }

        $this->decision->refresh();
    }

    public function render()
    {
        $this->decision->loadMissing(['booking.vehicle', 'booking.user', 'booking.group', 'replacedBooking.vehicle', 'replacedBooking.user', 'decider']);

        return view('livewire.decisions.decision-detail', [
            'request' => $this->decision->booking,
            'conflicts' => $this->decision->conflictingBookings(),
        ]);
    }
}
