<?php

namespace App\Models;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'receives_escalations',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'receives_escalations' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeEscalation($query)
    {
        return $query->where('receives_escalations', true);
    }
}
