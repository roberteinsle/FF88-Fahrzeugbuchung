<?php

use App\Livewire\Booking\BookingForm;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

test('booking form opens on open-booking-form event', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->assertSet('show', false)
        ->dispatch('open-booking-form', date: '2026-10-05', vehicleId: $vehicle->id)
        ->assertSet('show', true)
        ->assertSet('vehicleId', $vehicle->id)
        ->assertSet('startsAt', '2026-10-05T08:00');
});

test('booking form opens without date from the plus button', function () {
    $user = User::factory()->create();
    Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', date: null, vehicleId: null)
        ->assertSet('show', true)
        ->assertSee('Neue Buchung');
});

test('switching to a free vehicle checks availability without error', function () {
    $user = User::factory()->create();
    $first = Vehicle::factory()->create();
    $second = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', date: '2026-10-05', vehicleId: $first->id)
        ->set('vehicleId', $second->id)
        ->assertSet('available', true)
        ->assertSet('alternatives', []);
});

test('booking form opens in edit mode on open-edit-booking event', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'purpose' => 'Übungsdienst',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-edit-booking', bookingId: $booking->id)
        ->assertSet('show', true)
        ->assertSet('bookingId', $booking->id)
        ->assertSet('purpose', 'Übungsdienst')
        ->set('purpose', 'Übungsdienst geändert')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertDispatched('calendar-refresh');

    expect($booking->fresh()->purpose)->toBe('Übungsdienst geändert');
});
