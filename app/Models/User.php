<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'calendar_view',
        'avatar_updated_at',
        'is_admin',
        'is_decider',
        'is_active',
        'last_login_at',
        'remember_token',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_decider' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'avatar_updated_at' => 'datetime',
        ];
    }

    /** FullCalendar view names a user can pick as their default */
    public const CALENDAR_VIEWS = [
        'timeGridWeek' => 'Woche',
        'dayGridMonth' => 'Monat',
        'listWeek' => 'Liste',
    ];

    public function calendarView(): string
    {
        return array_key_exists($this->calendar_view ?? '', self::CALENDAR_VIEWS)
            ? $this->calendar_view
            : 'timeGridWeek';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** Deciders and admins resolve booking conflicts */
    public function canDecide(): bool
    {
        return $this->is_active && ($this->is_decider || $this->is_admin);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->is_active;
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(UserAvatar::class);
    }

    /** Versioned URL so browsers can cache the image until it changes; null without avatar */
    public function avatarUrl(): ?string
    {
        return $this->avatar_updated_at
            ? route('avatars.show', ['user' => $this->id, 'v' => $this->avatar_updated_at->timestamp])
            : null;
    }

    /** "Robert Einsle" -> "RE" for the placeholder */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    public function feedbackThreads(): HasMany
    {
        return $this->hasMany(FeedbackThread::class);
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(LoginToken::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
