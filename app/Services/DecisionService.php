<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDecision;
use App\Models\User;
use App\Notifications\DecisionClosedNotification;
use App\Notifications\DecisionMadeNotification;
use App\Notifications\DecisionRequestedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DecisionService
{
    /**
     * Store a booking that overlaps confirmed bookings as a pending request
     * and ask the deciders to resolve the conflict.
     *
     * With $replaces the request is a change of that booking: it stays untouched
     * until the decision and is cancelled when the change is approved.
     */
    public function request(array $data, User $requester, string $reason, ?Booking $replaces = null): BookingDecision
    {
        $decision = DB::transaction(function () use ($data, $requester, $reason, $replaces) {
            $conflicts = $this->conflictsFor($data['vehicle_id'], $data['starts_at'], $data['ends_at'], $replaces?->id);

            if ($conflicts->isEmpty()) {
                throw ValidationException::withMessages([
                    'vehicleId' => ['Das Fahrzeug ist in diesem Zeitraum frei – du kannst direkt buchen.'],
                ]);
            }

            $booking = Booking::create([
                'vehicle_id' => $data['vehicle_id'],
                'user_id' => $requester->id,
                'group_id' => $data['group_id'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'purpose' => $data['purpose'],
                'destination' => $data['destination'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Booking::STATUS_PENDING,
            ]);

            return BookingDecision::create([
                'booking_id' => $booking->id,
                'replaces_booking_id' => $replaces?->id,
                'conflicting_booking_ids' => $conflicts->pluck('id')->all(),
                'reason' => $reason,
                'notified_user_ids' => $this->deciders()->pluck('id')->all(),
            ]);
        });

        $this->notify(User::whereIn('id', $decision->notified_user_ids)->get(), new DecisionRequestedNotification($decision));

        return $decision;
    }

    /**
     * Approve: the request gets the vehicle and every confirmed booking it overlaps is cancelled.
     * Reject: the existing bookings stay and the request is rejected.
     */
    public function decide(BookingDecision $decision, User $decider, bool $approve, ?string $note = null): BookingDecision
    {
        DB::transaction(function () use ($decision, $decider, $approve, $note) {
            $locked = BookingDecision::whereKey($decision->id)->lockForUpdate()->first();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'decision' => ['Hier wurde bereits entschieden.'],
                ]);
            }

            $request = $locked->booking;

            if ($approve) {
                // Re-check: bookings may have been added or cancelled since the request
                $conflicts = $this->conflictsFor($request->vehicle_id, $request->starts_at, $request->ends_at, $locked->replaces_booking_id);
                $conflicts->each->update(['cancelled_at' => now()]);
                if ($locked->replacedBooking && ! $locked->replacedBooking->isCancelled()) {
                    $locked->replacedBooking->update(['cancelled_at' => now()]);
                }
                $request->update(['status' => Booking::STATUS_CONFIRMED]);
                $locked->conflicting_booking_ids = $conflicts->pluck('id')->all();
            } else {
                $request->update(['status' => Booking::STATUS_REJECTED]);
            }

            $locked->fill([
                'status' => $approve ? BookingDecision::STATUS_APPROVED : BookingDecision::STATUS_REJECTED,
                'decided_by' => $decider->id,
                'decided_at' => now(),
                'decision_note' => $note ?: null,
            ])->save();
        });

        $decision->refresh();

        $affected = $decision->conflictingBookings()->pluck('user')
            ->push($decision->booking->user)
            ->unique('id');
        $this->notify($affected, new DecisionMadeNotification($decision));

        $otherDeciders = User::whereIn('id', $decision->notified_user_ids ?? [])
            ->where('id', '!=', $decider->id)
            ->get();
        $this->notify($otherDeciders, new DecisionClosedNotification($decision));

        return $decision;
    }

    /** Called when the requester cancels their pending request */
    public function withdraw(BookingDecision $decision): void
    {
        if (! $decision->isPending()) {
            return;
        }

        $decision->update(['status' => BookingDecision::STATUS_WITHDRAWN, 'decided_at' => now()]);

        $this->notify(
            User::whereIn('id', $decision->notified_user_ids ?? [])->get(),
            new DecisionClosedNotification($decision)
        );
    }

    /** Active deciders; falls back to admins so a request never goes unanswered */
    public function deciders(): Collection
    {
        $deciders = User::active()->where('is_decider', true)->get();

        return $deciders->isNotEmpty() ? $deciders : User::active()->where('is_admin', true)->get();
    }

    private function conflictsFor(int $vehicleId, $startsAt, $endsAt, ?int $excludeBookingId = null): Collection
    {
        return Booking::active()
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->get();
    }

    /**
     * The decision is already stored when mails go out; a mail server problem
     * must not turn that into an error page. Failures end up in the log.
     */
    private function notify(Collection $users, Notification $notification): void
    {
        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
