<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class BookingForm extends Component
{
    public ?int $bookingId = null;

    public ?int $vehicleId = null;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $purpose = '';

    public string $destination = '';

    public string $notes = '';

    public ?int $groupId = null;

    public bool $show = false;

    // Availability check result
    public bool $available = true;

    public ?array $conflict = null;

    public array $alternatives = [];

    public function mount(?int $bookingId = null, ?string $date = null): void
    {
        if ($bookingId) {
            $this->loadBooking($bookingId);
        } elseif ($date) {
            $this->startsAt = Carbon::parse($date)->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T08:00';
            $this->endsAt = Carbon::parse($date)->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T18:00';
        }
    }

    #[On('open-booking-form')]
    public function open(?string $date = null, ?int $vehicleId = null): void
    {
        $this->reset(['purpose', 'destination', 'notes', 'groupId', 'conflict', 'alternatives', 'bookingId']);
        $this->available = true;

        if ($date) {
            $this->startsAt = Carbon::parse($date)->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T08:00';
            $this->endsAt = Carbon::parse($date)->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T18:00';
        } else {
            $this->startsAt = now()->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T08:00';
            $this->endsAt = now()->setTimezone('Europe/Berlin')->format('Y-m-d') . 'T18:00';
        }

        $this->vehicleId = $vehicleId ?? Vehicle::active()->first()?->id;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function openForEdit(int $bookingId): void
    {
        $this->reset(['purpose', 'destination', 'notes', 'groupId', 'conflict', 'alternatives']);
        $this->available = true;
        $this->loadBooking($bookingId);
        $this->show = true;
    }

    public function updatedVehicleId(): void
    {
        $this->checkAvailability();
    }

    public function updatedStartsAt(): void
    {
        $this->checkAvailability();
    }

    public function updatedEndsAt(): void
    {
        $this->checkAvailability();
    }

    public function checkAvailability(): void
    {
        if (! $this->vehicleId || ! $this->startsAt || ! $this->endsAt) {
            return;
        }

        try {
            $starts = Carbon::createFromFormat('Y-m-d\TH:i', $this->startsAt, 'Europe/Berlin')->utc();
            $ends = Carbon::createFromFormat('Y-m-d\TH:i', $this->endsAt, 'Europe/Berlin')->utc();
        } catch (\Exception) {
            return;
        }

        if ($starts >= $ends) {
            return;
        }

        $service = app(BookingService::class);
        $result = $service->checkAvailability($this->vehicleId, $starts, $ends, $this->bookingId);

        $this->available = $result['available'];
        $this->conflict = $result['conflict']
            ? [
                'id' => $result['conflict']->id,
                'userName' => $result['conflict']->user->name,
                'purpose' => $result['conflict']->purpose,
                'startsAt' => $result['conflict']->starts_at->setTimezone('Europe/Berlin')->format('d.m.Y H:i'),
                'endsAt' => $result['conflict']->ends_at->setTimezone('Europe/Berlin')->format('d.m.Y H:i'),
            ]
            : null;

        $this->alternatives = $result['alternatives']
            ->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'color' => $v->color])
            ->toArray();
    }

    public function save(BookingService $service): void
    {
        $this->validate([
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'startsAt' => ['required'],
            'endsAt' => ['required'],
            'purpose' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'vehicleId.required' => 'Bitte wähle ein Fahrzeug.',
            'startsAt.required' => 'Bitte gib die Startzeit an.',
            'endsAt.required' => 'Bitte gib die Endzeit an.',
            'purpose.required' => 'Bitte gib einen Zweck an.',
        ]);

        $starts = Carbon::createFromFormat('Y-m-d\TH:i', $this->startsAt, 'Europe/Berlin')->utc();
        $ends = Carbon::createFromFormat('Y-m-d\TH:i', $this->endsAt, 'Europe/Berlin')->utc();

        if ($starts >= $ends) {
            $this->addError('endsAt', 'Die Endzeit muss nach der Startzeit liegen.');
            return;
        }

        try {
            if ($this->bookingId) {
                $booking = Booking::findOrFail($this->bookingId);
                $this->authorize('update', $booking);
                $service->update($booking, [
                    'vehicle_id' => $this->vehicleId,
                    'group_id' => $this->groupId,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'purpose' => $this->purpose,
                    'destination' => $this->destination ?: null,
                    'notes' => $this->notes ?: null,
                ]);
            } else {
                $this->authorize('create', Booking::class);
                $service->create([
                    'vehicle_id' => $this->vehicleId,
                    'group_id' => $this->groupId,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'purpose' => $this->purpose,
                    'destination' => $this->destination ?: null,
                    'notes' => $this->notes ?: null,
                ], auth()->id());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
            return;
        }

        $this->show = false;
        $this->dispatch('calendar-refresh');
        session()->flash('success', $this->bookingId ? 'Buchung aktualisiert.' : 'Buchung gespeichert.');
    }

    public function loadBooking(int $bookingId): void
    {
        $booking = Booking::findOrFail($bookingId);
        $this->bookingId = $booking->id;
        $this->vehicleId = $booking->vehicle_id;
        $this->groupId = $booking->group_id;
        $this->startsAt = $booking->starts_at->setTimezone('Europe/Berlin')->format('Y-m-d\TH:i');
        $this->endsAt = $booking->ends_at->setTimezone('Europe/Berlin')->format('Y-m-d\TH:i');
        $this->purpose = $booking->purpose;
        $this->destination = $booking->destination ?? '';
        $this->notes = $booking->notes ?? '';
    }

    public function render()
    {
        $vehicles = Vehicle::active()->get();
        $groups = auth()->user()?->groups ?? collect();

        return view('livewire.booking.booking-form', [
            'vehicles' => $vehicles,
            'groups' => $groups,
        ]);
    }
}
