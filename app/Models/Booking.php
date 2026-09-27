<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'group_id',
        'starts_at',
        'ends_at',
        'purpose',
        'destination',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopeUpcoming($query)
    {
        return $query->active()->where('starts_at', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->active()->where('ends_at', '<', now());
    }
}
