<?php

namespace App\Models;

use App\Enums\PaymentEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    protected $fillable = [
        'payment_id',
        'provider',
        'provider_event_id',
        'event_type',
        'processing_status',
        'received_at',
        'processed_at',
        'payload_reference',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'processing_status' => PaymentEventStatus::class,
        ];
    }

    /**
     * `event_type` menyimpan nama event sesuai provider (misalnya
     * `payment.confirmed`) dan sengaja dibiarkan bebas. Yang dikendalikan
     * adalah `processing_status`.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
