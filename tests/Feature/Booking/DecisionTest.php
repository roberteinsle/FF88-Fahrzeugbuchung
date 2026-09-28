<?php

use App\Livewire\Booking\BookingForm;
use App\Livewire\Decisions\DecisionDetail;
use App\Models\Booking;
use App\Models\BookingDecision;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DecisionClosedNotification;
use App\Notifications\DecisionMadeNotification;
use App\Notifications\DecisionRequestedNotification;
use App\Services\BookingService;
use App\Services\DecisionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();

    $this->vehicle = Vehicle::factory()->create();
    $this->owner = User::factory()->create();
    $this->requester = User::factory()->create();
    $this->deciderA = User::factory()->decider()->create();
    $this->deciderB = User::factory()->decider()->create();

    $this->existing = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'user_id' => $this->owner->id,
        'starts_at' => Carbon::parse('2026-10-10 10:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-10 14:00', 'Europe/Berlin')->utc(),
    ]);
});

function requestConflict(): BookingDecision
{
    return app(DecisionService::class)->request([
        'vehicle_id' => test()->vehicle->id,
        'starts_at' => Carbon::parse('2026-10-10 12:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-10 16:00', 'Europe/Berlin')->utc(),
        'purpose' => 'Übung Nachbarwehr',
    ], test()->requester, 'Nur mit diesem Fahrzeug möglich');
}

test('booking form sends a conflict request instead of failing', function () {
    Livewire::actingAs($this->requester)
        ->test(BookingForm::class)
        ->call('open', '2026-10-10', $this->vehicle->id)
        ->set('startsAt', '2026-10-10T12:00')
        ->set('endsAt', '2026-10-10T16:00')
        ->assertSet('available', false)
        ->set('purpose', 'Übung')
        ->call('save')
        ->assertHasErrors('reason')
        ->set('reason', 'Wichtig')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('calendar'));

    $request = Booking::where('user_id', $this->requester->id)->sole();
    expect($request->status)->toBe(Booking::STATUS_PENDING)
        ->and($request->decision->conflicting_booking_ids)->toBe([$this->existing->id]);
});

test('conflict request notifies all deciders with a link', function () {
    $decision = requestConflict();

    Notification::assertSentTo([$this->deciderA, $this->deciderB], DecisionRequestedNotification::class,
        function ($notification, $channels, $notifiable) use ($decision) {
            return $notification->toMail($notifiable)->actionUrl === route('decisions.show', $decision);
        });
    Notification::assertNotSentTo([$this->owner, $this->requester], DecisionRequestedNotification::class);
});

test('admins are asked when there is no decider', function () {
    User::where('is_decider', true)->update(['is_decider' => false]);
    $admin = User::factory()->admin()->create();

    requestConflict();

    Notification::assertSentTo($admin, DecisionRequestedNotification::class);
});

test('pending request does not block the vehicle and existing booking stays', function () {
    requestConflict();

    expect($this->existing->fresh()->isCancelled())->toBeFalse()
        ->and(Booking::active()->count())->toBe(1);
});

test('approving confirms the request, cancels the existing booking and informs everyone', function () {
    $decision = requestConflict();

    Livewire::actingAs($this->deciderA)
        ->test(DecisionDetail::class, ['decision' => $decision])
        ->set('note', 'Übung hat Vorrang')
        ->call('approve')
        ->assertHasNoErrors();

    $decision->refresh();
    expect($decision->status)->toBe(BookingDecision::STATUS_APPROVED)
        ->and($decision->decided_by)->toBe($this->deciderA->id)
        ->and($decision->decision_note)->toBe('Übung hat Vorrang')
        ->and($decision->booking->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($this->existing->fresh()->isCancelled())->toBeTrue();

    Notification::assertSentTo([$this->requester, $this->owner], DecisionMadeNotification::class);
    Notification::assertSentTo($this->deciderB, DecisionClosedNotification::class);
    Notification::assertNotSentTo($this->deciderA, DecisionClosedNotification::class);
});

test('rejecting keeps the existing booking', function () {
    $decision = requestConflict();

    app(DecisionService::class)->decide($decision, $this->deciderB, approve: false);

    expect($decision->fresh()->status)->toBe(BookingDecision::STATUS_REJECTED)
        ->and($decision->booking->fresh()->status)->toBe(Booking::STATUS_REJECTED)
        ->and($this->existing->fresh()->isCancelled())->toBeFalse();

    Notification::assertSentTo([$this->requester, $this->owner], DecisionMadeNotification::class);
    Notification::assertSentTo($this->deciderA, DecisionClosedNotification::class);
});

test('a second decider cannot decide again', function () {
    $decision = requestConflict();
    app(DecisionService::class)->decide($decision, $this->deciderA, approve: false);

    Livewire::actingAs($this->deciderB)
        ->test(DecisionDetail::class, ['decision' => $decision->fresh()])
        ->call('approve')
        ->assertHasErrors('decision');

    expect($decision->fresh()->status)->toBe(BookingDecision::STATUS_REJECTED)
        ->and($this->existing->fresh()->isCancelled())->toBeFalse();
});

test('cancelling a pending request withdraws the decision', function () {
    $decision = requestConflict();

    app(BookingService::class)->cancel($decision->booking);

    expect($decision->fresh()->status)->toBe(BookingDecision::STATUS_WITHDRAWN);
    Notification::assertSentTo([$this->deciderA, $this->deciderB], DecisionClosedNotification::class);
});

test('only deciders and admins can open decisions', function () {
    $decision = requestConflict();

    $this->actingAs($this->requester)->get(route('decisions.show', $decision))->assertForbidden();
    $this->actingAs($this->deciderA)->get(route('decisions.show', $decision))->assertOk()->assertSee('Anfrage genehmigen');
    $this->actingAs(User::factory()->admin()->create())->get(route('decisions.index'))->assertOk();
});

test('deciders see the red decision button while a decision is open', function () {
    $this->actingAs($this->deciderA)->get(route('calendar'))->assertDontSee('Entscheidung (');

    requestConflict();

    $this->actingAs($this->deciderA)->get(route('calendar'))->assertSee('Entscheidung (1)');
    $this->actingAs($this->requester)->get(route('calendar'))->assertDontSee('Entscheidung (');
});

test('pending requests show up in the calendar feed', function () {
    requestConflict();

    $this->actingAs($this->requester)
        ->getJson(route('bookings.events', ['start' => '2026-10-01T00:00:00', 'end' => '2026-11-01T00:00:00']))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['classNames' => ['fc-event-pending']]);
});
