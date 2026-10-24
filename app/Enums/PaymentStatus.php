<?php

namespace App\Enums;

/**
 * Status pembayaran PPDB.
 *
 * Mengikuti PRD bab 8.6 (hybrid verification). Daftar ini menggantikan
 * daftar status pada ERD lama agar hanya ada satu kosakata kanonik.
 */
enum PaymentStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingVerification = 'pending_verification';
    case Confirmed = 'confirmed';
    case ManualReview = 'manual_review';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::PendingVerification => 'Menunggu Verifikasi',
            self::Confirmed => 'Terkonfirmasi',
            self::ManualReview => 'Pemeriksaan Manual',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Pembayaran yang sudah lunas.
     */
    public function isSettled(): bool
    {
        return $this === self::Confirmed;
    }

    /**
     * Pembayaran yang masih aktif dan menunggu tindakan.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::PendingPayment, self::PendingVerification, self::ManualReview], true);
    }

    /**
     * Status yang menutup percobaan pembayaran.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Confirmed, self::Failed, self::Expired], true);
    }
}
