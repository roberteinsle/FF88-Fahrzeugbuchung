<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BookingEventController;
use App\Models\BookingDecision;
use App\Models\FeedbackThread;
use App\Models\User;
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
// Station monitor: read-only, no user. Admins or the secret DISPLAY_TOKEN;
// everyone else gets a 404 so the page doesn't reveal that it exists.
// -----------------------------------------------------------------------
Route::get('/monitor/{key?}', function (?string $key = null) {
    $token = (string) config('monitor.token');
    $validKey = $token !== '' && $key !== null && hash_equals($token, $key);

    abort_unless(auth()->user()?->is_admin || $validKey, 404);

    return response()
        ->view('pages.monitor')
        ->header('X-Robots-Tag', 'noindex, nofollow')
        ->header('Referrer-Policy', 'no-referrer');
})->name('monitor');

// -----------------------------------------------------------------------
// Authenticated routes
// -----------------------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/calendar', fn () => view('pages.calendar'))->name('calendar');
    Route::get('/my-bookings', fn () => view('pages.my-bookings'))->name('my-bookings');
    Route::get('/profile', fn () => view('pages.profile'))->name('profile');

    // Feedback conversation with the admins (own threads only)
    Route::get('/feedback/{thread}', function (FeedbackThread $thread) {
        abort_unless($thread->user_id === auth()->id(), 403);

        return view('pages.feedback.show', ['thread' => $thread]);
    })->name('feedback.show');

    // Conflict decisions (deciders and admins)
    Route::middleware('can:decide-bookings')->group(function () {
        Route::get('/decisions', fn () => view('pages.decisions.index'))->name('decisions.index');
        Route::get('/decisions/{decision}', fn (BookingDecision $decision) => view('pages.decisions.show', ['decision' => $decision]))
            ->name('decisions.show');
    });

    // Avatar images (versioned URL, cached by the browser)
    Route::get('/avatars/{user}', function (User $user) {
        $avatar = $user->avatar ?? abort(404);

        return response(base64_decode($avatar->data), 200, [
            'Content-Type' => $avatar->mime,
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    })->name('avatars.show');

    // FullCalendar JSON events feed
    Route::get('/bookings/events', [BookingEventController::class, 'index'])->name('bookings.events');

    // Logout
    Route::post('/logout', [MagicLinkController::class, 'logout'])->name('logout');
});
