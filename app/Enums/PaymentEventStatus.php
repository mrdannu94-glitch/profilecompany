<?php

namespace App\Enums;

/**
 * Status pemrosesan satu peristiwa pembayaran (webhook atau aksi manual).
 */
enum PaymentEventStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Diterima',
            self::Processed => 'Diproses',
            self::Ignored => 'Diabaikan',
            self::Failed => 'Gagal Diproses',
        };
    }
}
