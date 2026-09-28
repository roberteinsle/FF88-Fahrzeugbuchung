<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BookingEventController;
use App\Models\BookingDecision;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn () => redirect()->route('calendar'));

// Laravel auth middleware expects a route named 'login'
Route::redirect('/login', '/auth/login')->name('login');

// -----------------------------------------------------------------------
// Auth routes (public)
// -----------------------------------------------------------------------
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [MagicLinkController::class, 'showForm'])->name('magic-link.form');
    Route::post('/login', [MagicLinkController::class, 'sendLink'])->name('magic-link.send');
    Route::get('/login/sent', [MagicLinkController::class, 'linkSent'])->name('magic-link.sent');

    // Two-step: GET shows confirmation button, POST consumes the token
    Route::get('/login/{token}', [MagicLinkController::class, 'showConfirm'])->name('magic-link.confirm');
    Route::post('/login/{token}', [MagicLinkController::class, 'consumeToken'])->name('magic-link.consume');
});

// -----------------------------------------------------------------------
// Authenticated routes
// -----------------------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/calendar', fn () => view('pages.calendar'))->name('calendar');
    Route::get('/my-bookings', fn () => view('pages.my-bookings'))->name('my-bookings');
    Route::get('/profile', fn () => view('pages.profile'))->name('profile');

    // Conflict decisions (deciders and admins)
    Route::middleware('can:decide-bookings')->group(function () {
        Route::get('/decisions', fn () => view('pages.decisions.index'))->name('decisions.index');
        Route::get('/decisions/{decision}', fn (BookingDecision $decision) => view('pages.decisions.show', ['decision' => $decision]))
            ->name('decisions.show');
    });

    // FullCalendar JSON events feed
    Route::get('/bookings/events', [BookingEventController::class, 'index'])->name('bookings.events');

    // Logout
    Route::post('/logout', [MagicLinkController::class, 'logout'])->name('logout');
});
