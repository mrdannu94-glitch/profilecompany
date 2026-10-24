<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Kontrak adapter provider pembayaran.
 *
 * Implementasi bawaan adalah {@see ManualPaymentProvider} yang dipakai
 * selama provider resmi belum ditetapkan sekolah. Kontrak ini disiapkan
 * supaya provider terverifikasi otomatis dapat ditambahkan kemudian tanpa
 * mengubah alur pendaftaran.
 */
interface PaymentProviderInterface
{
    /**
     * Nama provider yang disimpan pada tabel `payments`.
     */
    public function name(): string;

    /**
     * Apakah provider dapat memverifikasi pembayaran secara otomatis
     * melalui server-to-server.
     */
    public function supportsAutomaticVerification(): bool;

    /**
     * Instruksi pembayaran untuk calon siswa.
     *
     * @return array{method: string, amount: string, currency: string, reference: string|null, instructions: array<int, string>}
     */
    public function buildInstruction(Payment $payment, Registration $registration): array;

    /**
     * Periksa signature webhook memakai mekanisme resmi provider.
     */
    public function verifySignature(Request $request): bool;

    /**
     * Baca payload webhook menjadi data peristiwa provider.
     */
    public function parseEvent(Request $request): ?PaymentEventPayload;
}
