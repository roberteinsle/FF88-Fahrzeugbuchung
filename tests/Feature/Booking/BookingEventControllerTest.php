<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->vehicle = Vehicle::factory()->create(['color' => '#ef4444']);
    $this->service = app(BookingService::class);
    $this->actingAs($this->user);
});

test('events endpoint returns JSON for active bookings', function () {
    $this->service->create([
        'vehicle_id' => $this->vehicle->id,
        'group_id' => null,
        'starts_at' => Carbon::parse('2026-10-15 08:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-15 14:00', 'Europe/Berlin')->utc(),
        'purpose' => 'Test Event',
        'destination' => null,
        'notes' => null,
    ], $this->user->id);

    $this->getJson(route('bookings.events', [
        'start' => '2026-10-01T00:00:00',
        'end' => '2026-10-31T23:59:59',
        'vehicles' => [$this->vehicle->id],
    ]))->assertOk()
        ->assertJsonFragment(['title' => 'Test Event']);
});

test('cancelled bookings are not in the events feed', function () {
    $booking = $this->service->create([
        'vehicle_id' => $this->vehicle->id,
        'group_id' => null,
        'starts_at' => Carbon::parse('2026-10-15 08:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-15 14:00', 'Europe/Berlin')->utc(),
        'purpose' => 'Storniert',
        'destination' => null,
        'notes' => null,
    ], $this->user->id);

    $this->service->cancel($booking);

    $response = $this->getJson(route('bookings.events', [
        'start' => '2026-10-01T00:00:00',
        'end' => '2026-10-31T23:59:59',
    ]));

    $response->assertOk()
        ->assertJsonMissing(['title' => 'Storniert']);
});

test('vehicle filter limits results', function () {
    $otherVehicle = Vehicle::factory()->create();

    $this->service->create([
        'vehicle_id' => $this->vehicle->id,
        'group_id' => null,
        'starts_at' => Carbon::parse('2026-10-16 08:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-16 12:00', 'Europe/Berlin')->utc(),
        'purpose' => 'Vehicle A',
        'destination' => null,
        'notes' => null,
    ], $this->user->id);

    $this->service->create([
        'vehicle_id' => $otherVehicle->id,
        'group_id' => null,
        'starts_at' => Carbon::parse('2026-10-17 08:00', 'Europe/Berlin')->utc(),
        'ends_at' => Carbon::parse('2026-10-17 12:00', 'Europe/Berlin')->utc(),
        'purpose' => 'Vehicle B',
        'destination' => null,
        'notes' => null,
    ], $this->user->id);

    $this->getJson(route('bookings.events', [
        'start' => '2026-10-01T00:00:00',
        'end' => '2026-10-31T23:59:59',
        'vehicles' => [$this->vehicle->id],
    ]))->assertOk()
        ->assertJsonFragment(['title' => 'Vehicle A'])
        ->assertJsonMissing(['title' => 'Vehicle B']);
});

test('unauthenticated user cannot access events endpoint', function () {
    auth()->logout();
    $this->getJson(route('bookings.events'))
        ->assertRedirect();
});
