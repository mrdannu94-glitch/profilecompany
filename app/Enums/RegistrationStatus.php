<?php

namespace App\Enums;

/**
 * Status utama pendaftaran calon siswa.
 *
 * Status pembayaran, dokumen, dan hasil seleksi dicatat terpisah; status
 * ini hanya merangkum tahap pendaftaran.
 */
enum RegistrationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PaymentPending = 'payment_pending';
    case DocumentsPending = 'documents_pending';
    case DocumentsApproved = 'documents_approved';
    case DocumentsRejected = 'documents_rejected';
    case Completed = 'completed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Terkirim',
            self::PaymentPending => 'Menunggu Pembayaran',
            self::DocumentsPending => 'Menunggu Dokumen',
            self::DocumentsApproved => 'Dokumen Disetujui',
            self::DocumentsRejected => 'Dokumen Dikembalikan',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Pendaftaran yang sudah memiliki nomor pendaftaran dan kode akses.
     */
    public function isIssued(): bool
    {
        return $this !== self::Draft;
    }

    /**
     * Status yang menutup pendaftaran.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Expired], true);
    }
}
