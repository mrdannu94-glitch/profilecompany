<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'registration_id',
        'provider',
        'provider_reference',
        'payment_method',
        'amount',
        'currency',
        'status',
        'expires_at',
        'confirmed_at',
        'verified_by',
        'verification_method',
        'verification_note',
        'attempt_number',
        'is_active',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'attempt_number' => 'integer',
            'is_active' => 'boolean',
            'status' => PaymentStatus::class,
            'verification_method' => VerificationMethod::class,
        ];
    }

    /**
     * Jaga sentinel percobaan aktif tetap sesuai dengan status baris.
     */
    protected static function booted(): void
    {
        static::saving(function (self $payment): void {
            $payment->active_registration_id = $payment->is_active
                ? $payment->registration_id
                : null;
        });
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Petugas yang melakukan verifikasi manual.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Peristiwa pembayaran yang diterima untuk percobaan ini.
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payment_id');
    }

    /**
     * Percobaan pembayaran yang sedang berlaku.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Pembayaran yang masih menunggu tindak lanjut.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PaymentStatus::PendingPayment,
            PaymentStatus::PendingVerification,
            PaymentStatus::ManualReview,
        ]);
    }
}
