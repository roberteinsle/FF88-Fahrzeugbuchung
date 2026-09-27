<?php

use App\Models\LoginToken;
use App\Models\User;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

beforeEach(function () {
    RateLimiter::clear('magic-link:ip:127.0.0.1');
});

test('magic link form is accessible', function () {
    $this->get(route('auth.magic-link.form'))
        ->assertOk();
});

test('sending magic link shows sent page', function () {
    $user = User::factory()->create();

    $this->post(route('auth.magic-link.send'), ['email' => $user->email])
        ->assertRedirect(route('auth.magic-link.sent'));
});

test('sending link to unknown email still redirects (no enumeration)', function () {
    $this->post(route('auth.magic-link.send'), ['email' => 'nonexistent@example.com'])
        ->assertRedirect(route('auth.magic-link.sent'));
});

test('GET confirm route does not consume token', function () {
    $user = User::factory()->create();
    $rawToken = Str::random(64);

    LoginToken::factory()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->get(route('auth.magic-link.confirm', $rawToken))
        ->assertOk();

    // Token must NOT be consumed
    $this->assertDatabaseMissing('login_tokens', [
        'token_hash' => hash('sha256', $rawToken),
        'used_at' => null,
    ]);
    // Actually assert it is still null
    $this->assertDatabaseHas('login_tokens', [
        'token_hash' => hash('sha256', $rawToken),
        'used_at' => null,
    ]);
});

test('POST confirm route logs in user and marks token used', function () {
    $user = User::factory()->create();
    $rawToken = Str::random(64);

    LoginToken::factory()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->post(route('auth.magic-link.consume', $rawToken))
        ->assertRedirect(route('calendar'));

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseMissing('login_tokens', [
        'token_hash' => hash('sha256', $rawToken),
        'used_at' => null,
    ]);
});

test('expired token is rejected', function () {
    $user = User::factory()->create();
    $rawToken = Str::random(64);

    LoginToken::factory()->expired()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $rawToken),
    ]);

    $this->post(route('auth.magic-link.consume', $rawToken))
        ->assertRedirect(route('auth.magic-link.form'));

    $this->assertGuest();
});

test('used token cannot be reused', function () {
    $user = User::factory()->create();
    $rawToken = Str::random(64);

    LoginToken::factory()->used()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->post(route('auth.magic-link.consume', $rawToken))
        ->assertRedirect(route('auth.magic-link.form'));

    $this->assertGuest();
});

test('inactive user cannot log in', function () {
    $user = User::factory()->inactive()->create();
    $rawToken = Str::random(64);

    LoginToken::factory()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->post(route('auth.magic-link.consume', $rawToken))
        ->assertRedirect();

    $this->assertGuest();
});

test('rate limit blocks excessive requests per email', function () {
    $user = User::factory()->create();
    $key = 'magic-link:email:' . sha1($user->email);

    // Exhaust the limit
    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);

    $this->post(route('auth.magic-link.send'), ['email' => $user->email])
        ->assertRedirect(); // still redirects (no enumeration), but no token created

    $this->assertDatabaseCount('login_tokens', 0);
});
