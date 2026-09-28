<?php

namespace App\Livewire\Decisions;

use App\Models\BookingDecision;
use Livewire\Component;

class DecisionIndex extends Component
{
    public function render()
    {
        $with = ['booking.vehicle', 'booking.user', 'decider'];

        return view('livewire.decisions.decision-index', [
            'open' => BookingDecision::with($with)->pending()->oldest()->get(),
            'closed' => BookingDecision::with($with)
                ->where('status', '!=', BookingDecision::STATUS_PENDING)
                ->latest('decided_at')
                ->limit(50)
                ->get(),
        ]);
    }
}
