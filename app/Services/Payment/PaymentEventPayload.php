<?php

namespace App\Services\Payment;

/**
 * Data peristiwa pembayaran yang dibaca dari webhook provider.
 */
final readonly class PaymentEventPayload
{
    public function __construct(
        public string $providerEventId,
        public string $eventType,
        public ?string $providerReference = null,
        public ?string $amount = null,
        public ?string $currency = null,
    ) {}
}
