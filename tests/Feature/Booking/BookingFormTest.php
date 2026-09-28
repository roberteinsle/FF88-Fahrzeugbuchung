<?php

use App\Livewire\Booking\BookingForm;
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
