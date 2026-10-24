<?php

namespace App\Enums;

/**
 * Status keputusan hasil seleksi.
 */
enum SelectionDecisionStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case NotAccepted = 'not_accepted';
    case Waitlisted = 'waitlisted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Keputusan',
            self::Accepted => 'Diterima',
            self::NotAccepted => 'Tidak Diterima',
            self::Waitlisted => 'Daftar Tunggu',
        };
    }

    /**
     * Keputusan yang sudah Final dan dapat diumumkan.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Accepted, self::NotAccepted, self::Waitlisted], true);
    }
}
