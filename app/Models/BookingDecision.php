<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDecision extends Model
{
    public const STATUS_PENDING = 'pending';

    /** The request won: it was confirmed and the conflicting bookings were cancelled */
    public const STATUS_APPROVED = 'approved';

    /** The existing bookings were kept and the request was rejected */
    public const STATUS_REJECTED = 'rejected';

    /** The requester cancelled the request before anyone decided */
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'booking_id',
        'conflicting_booking_ids',
        'reason',
        'status',
        'notified_user_ids',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'conflicting_booking_ids' => 'array',
            'notified_user_ids' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    /** The conflict request (a booking with status pending until decided) */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return Collection<int, Booking> */
    public function conflictingBookings(): Collection
    {
        return Booking::with(['vehicle', 'user'])
            ->whereIn('id', $this->conflicting_booking_ids ?? [])
            ->orderBy('starts_at')
            ->get();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Anfrage genehmigt',
            self::STATUS_REJECTED => 'Bestehende Buchung behalten',
            self::STATUS_WITHDRAWN => 'Zurückgezogen',
            default => 'Offen',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
