<?php

namespace App\Enums;

/**
 * Status periode PPDB.
 *
 * Nilai ini adalah kosakata kanonik yang disimpan di database. Lihat
 * docs/STATUS_MATRIX.md untuk transisi yang diperbolehkan.
 */
enum PpdbPeriodStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';
    case Announced = 'announced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
            self::Announced => 'Diumumkan',
        };
    }

    /**
     * Periode yang masih menerima pendaftaran baru.
     */
    public function acceptsRegistration(): bool
    {
        return $this === self::Open;
    }

    /**
     * Status yang tidak lagi dapat berubah secara otomatis.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Closed, self::Announced], true);
    }
}
