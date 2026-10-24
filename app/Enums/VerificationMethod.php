<?php

namespace App\Enums;

/**
 * Cara pembayaran diverifikasi.
 */
enum VerificationMethod: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Otomatis',
            self::Manual => 'Manual',
        };
    }
}
