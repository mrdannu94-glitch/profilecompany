<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Implementasi pembayaran manual.
 *
 * Provider ini dipakai selama metode pembayaran resmi belum ditetapkan
 * sekolah. Unggahan bukti pembayaran tidak pernah dianggap sebagai
 * konfirmasi: konfirmasi tetap dilakukan petugas yang berwenang melalui
 * `PaymentService::confirmManually()`.
 */
class ManualPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function supportsAutomaticVerification(): bool
    {
        return false;
    }

    /**
     * Instruksi diambil dari `payment_policy` periode yang diisi Admin.
     *
     * @return array{method: string, amount: string, currency: string, reference: string|null, instructions: array<int, string>}
     */
    public function buildInstruction(Payment $payment, Registration $registration): array
    {
        $policy = $registration->period?->payment_policy ?? [];

        $instructions = [];

        if (isset($policy['instructions']) && is_array($policy['instructions'])) {
            $instructions = array_values(array_map(
                static fn (mixed $line): string => (string) $line,
                $policy['instructions']
            ));
        }

        if ($instructions === []) {
            $instructions = [
                'Metode pembayaran resmi belum ditetapkan sekolah. Hubungi CS sekolah untuk memperoleh informasi pembayaran.',
            ];
        }

        return [
            'method' => (string) ($policy['method'] ?? 'Menunggu penetapan sekolah'),
            'amount' => (string) $payment->amount,
            'currency' => (string) $payment->currency,
            'reference' => $payment->provider_reference,
            'instructions' => $instructions,
        ];
    }

    /**
     * Pembayaran manual tidak memiliki webhook resmi.
     */
    public function verifySignature(Request $request): bool
    {
        return false;
    }

    public function parseEvent(Request $request): ?PaymentEventPayload
    {
        return null;
    }
}
