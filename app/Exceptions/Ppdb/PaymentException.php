<?php

namespace App\Exceptions\Ppdb;

use RuntimeException;

/**
 * Kegagalan pada proses pembayaran pendaftaran.
 */
class PaymentException extends RuntimeException
{
    public static function feeNotConfigured(): self
    {
        return new self('Nominal biaya pendaftaran belum ditetapkan untuk periode ini.');
    }

    public static function providerNotAvailable(string $provider): self
    {
        return new self("Provider pembayaran {$provider} belum tersedia.");
    }

    public static function alreadyConfirmed(): self
    {
        return new self('Percobaan pembayaran ini sudah berstatus terkonfirmasi.');
    }

    public static function invalidSignature(): self
    {
        return new self('Signature webhook tidak valid.');
    }
}
