<?php

namespace App\Models;

use App\Enums\SelectionDecisionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectionDecision extends Model
{
    protected $fillable = [
        'registration_id',
        'decided_by',
        'decision_status',
        'decision_note',
        'decided_at',
        'is_current',
        'previous_status',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'is_current' => 'boolean',
            'decision_status' => SelectionDecisionStatus::class,
            'previous_status' => SelectionDecisionStatus::class,
        ];
    }

    /**
     * Jaga sentinel keputusan berlaku tetap sesuai dengan status baris.
     */
    protected static function booted(): void
    {
        static::saving(function (self $decision): void {
            $decision->current_registration_id = $decision->is_current
                ? $decision->registration_id
                : null;
        });
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Pengambil keputusan hasil seleksi.
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
