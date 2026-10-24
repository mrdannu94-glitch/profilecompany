<?php

namespace App\Services\Ppdb;

use App\Enums\PaymentEventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\Ppdb\PaymentException;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Registration;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Payment\ManualPaymentProvider;
use App\Services\Payment\PaymentEventPayload;
use App\Services\Payment\PaymentProviderInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service layer pembayaran pendaftaran.
 *
 * Pembayaran memakai model verifikasi hybrid. Percobaan pembayaran disimpan
 * sebagai baris terpisah sehingga percobaan ulang tidak menimpa riwayat.
 * Konfirmasi manual hanya boleh dilakukan petugas berwenang dan selalu
 * menghasilkan jejak audit.
 */
class PaymentService
{
    /**
     * Provider pembayaran yang tersedia.
     *
     * @var array<string, class-string<PaymentProviderInterface>>
     */
    protected const PROVIDERS = [
        'manual' => ManualPaymentProvider::class,
    ];

    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Buat percobaan pembayaran baru untuk satu pendaftaran.
     *
     * Percobaan sebelumnya dinonaktifkan, bukan dihapus, sehingga riwayat
     * tetap dapat ditelusuri.
     *
     * @throws PaymentException
     */
    public function createAttempt(
        Registration $registration,
        ?string $providerName = null,
        ?string $paymentMethod = null,
    ): Payment {
        $period = $registration->period;

        if ($period === null || $period->registration_fee === null) {
            throw PaymentException::feeNotConfigured();
        }

        $provider = $this->provider($providerName ?? 'manual');

        $payment = DB::transaction(function () use ($registration, $period, $provider, $paymentMethod): Payment {
            $this->deactivateActiveAttempts($registration);

            $attempt = (int) Payment::query()
                ->where('registration_id', $registration->id)
                ->max('attempt_number') + 1;

            $payment = new Payment;
            $payment->forceFill([
                'registration_id' => $registration->id,
                'provider' => $provider->name(),
                'payment_method' => $paymentMethod,
                'amount' => $period->registration_fee,
                'currency' => $period->currency ?? 'IDR',
                'status' => PaymentStatus::PendingPayment,
                'expires_at' => $this->expiryFor($period->payment_policy),
                'attempt_number' => $attempt,
                'is_active' => true,
                'verification_method' => $provider->supportsAutomaticVerification()
                    ? VerificationMethod::Automatic
                    : VerificationMethod::Manual,
            ])->save();

            return $payment;
        });

        $registration->forceFill(['status' => RegistrationStatus::PaymentPending])->save();

        $this->audit->log(
            action: 'ppdb.payment.attempt_created',
            entityType: Payment::class,
            entityId: $payment->id,
            outcome: 'success',
            description: 'Percobaan pembayaran pendaftaran dibuat.',
            context: [
                'registration_number' => $registration->registration_number,
                'provider' => $payment->provider,
                'attempt_number' => $payment->attempt_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
        );

        return $payment;
    }

    /**
     * Konfirmasi pembayaran secara manual oleh petugas berwenang.
     *
     * @throws PaymentException
     */
    public function confirmManually(Payment $payment, User $actor, ?string $note = null): Payment
    {
        if ($payment->status === PaymentStatus::Confirmed) {
            throw PaymentException::alreadyConfirmed();
        }

        $payment->forceFill([
            'status' => PaymentStatus::Confirmed,
            'confirmed_at' => now(),
            'verified_by' => $actor->getKey(),
            'verification_method' => VerificationMethod::Manual,
            'verification_note' => $note,
            'is_active' => true,
        ])->save();

        $registration = $payment->registration;

        if ($registration !== null && ! in_array($registration->status, [
            RegistrationStatus::DocumentsApproved,
            RegistrationStatus::Completed,
        ], true)) {
            $registration->forceFill(['status' => RegistrationStatus::DocumentsPending])->save();
        }

        $this->audit->log(
            action: 'ppdb.payment.confirmed',
            entityType: Payment::class,
            entityId: $payment->id,
            outcome: 'success',
            actor: $actor,
            description: 'Pembayaran dikonfirmasi secara manual.',
            context: [
                'registration_number' => $registration?->registration_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'note' => $note,
            ],
        );

        return $payment;
    }

    /**
     * Tandai percobaan pembayaran sebagai gagal.
     */
    public function failManually(Payment $payment, User $actor, string $reason): Payment
    {
        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'is_active' => false,
            'failure_reason' => $reason,
            'verified_by' => $actor->getKey(),
            'verification_method' => VerificationMethod::Manual,
            'verification_note' => $reason,
        ])->save();

        $this->audit->log(
            action: 'ppdb.payment.failed',
            entityType: Payment::class,
            entityId: $payment->id,
            outcome: 'failure',
            actor: $actor,
            description: 'Percobaan pembayaran dinyatakan gagal.',
            context: [
                'registration_number' => $payment->registration?->registration_number,
                'reason' => $reason,
            ],
        );

        return $payment;
    }

    /**
     * Proses webhook pembayaran secara idempoten.
     *
     * Peristiwa dengan `provider_event_id` yang sama tidak diproses dua
     * kali karena tabel `payment_events` memakai unique gabungan provider
     * dan id peristiwa.
     *
     * @throws PaymentException
     */
    public function handleProviderEvent(PaymentProviderInterface $provider, Request $request): ?PaymentEvent
    {
        if (! $provider->verifySignature($request)) {
            $this->audit->failure(
                action: 'ppdb.payment.webhook.rejected',
                entityType: PaymentEvent::class,
                description: 'Webhook pembayaran ditolak karena signature tidak valid.',
                context: ['provider' => $provider->name()],
            );

            throw PaymentException::invalidSignature();
        }

        $payload = $provider->parseEvent($request);

        if ($payload === null) {
            return null;
        }

        return $this->recordEvent($provider, $payload);
    }

    /**
     * Simpan peristiwa provider tanpa memproses dua kali.
     */
    protected function recordEvent(PaymentProviderInterface $provider, PaymentEventPayload $payload): ?PaymentEvent
    {
        $payment = Payment::query()
            ->where('provider', $provider->name())
            ->where('provider_reference', $payload->providerReference)
            ->latest('id')
            ->first();

        if ($payment === null) {
            $this->audit->failure(
                action: 'ppdb.payment.webhook.unmatched',
                entityType: PaymentEvent::class,
                description: 'Webhook pembayaran tidak cocok dengan percobaan pembayaran mana pun.',
                context: ['provider' => $provider->name()],
            );

            return null;
        }

        try {
            $event = PaymentEvent::create([
                'payment_id' => $payment->id,
                'provider' => $provider->name(),
                'provider_event_id' => $payload->providerEventId,
                'event_type' => $payload->eventType,
                'processing_status' => PaymentEventStatus::Received,
                'received_at' => now(),
            ]);
        } catch (QueryException) {
            // Peristiwa sudah pernah diterima. Pemrosesan diulang tidak
            // boleh membuat transaksi ganda.
            return null;
        }

        $this->applyEvent($payment, $payload, $event);

        return $event;
    }

    /**
     * Terapkan dampaknya pada percobaan pembayaran.
     */
    protected function applyEvent(Payment $payment, PaymentEventPayload $payload, PaymentEvent $event): void
    {
        if ($payload->eventType === 'payment.confirmed' && $payment->status !== PaymentStatus::Confirmed) {
            $payment->forceFill([
                'status' => PaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'verification_method' => VerificationMethod::Automatic,
            ])->save();

            $registration = $payment->registration;

            if ($registration !== null && ! in_array($registration->status, [
                RegistrationStatus::DocumentsApproved,
                RegistrationStatus::Completed,
            ], true)) {
                $registration->forceFill(['status' => RegistrationStatus::DocumentsPending])->save();
            }
        }

        $event->forceFill([
            'processing_status' => PaymentEventStatus::Processed,
            'processed_at' => now(),
        ])->save();

        $this->audit->log(
            action: 'ppdb.payment.webhook_processed',
            entityType: PaymentEvent::class,
            entityId: $event->id,
            outcome: 'success',
            description: 'Peristiwa pembayaran dari provider diproses.',
            context: [
                'provider' => $event->provider,
                'event_type' => $event->event_type,
                'payment_id' => $payment->id,
            ],
        );
    }

    /**
     * Nonaktifkan percobaan aktif sebelumnya.
     *
     * Perubahan dilakukan lewat model agar sentinel
     * `active_registration_id` ikut diperbarui.
     */
    protected function deactivateActiveAttempts(Registration $registration): void
    {
        Payment::query()
            ->where('registration_id', $registration->id)
            ->where('is_active', true)
            ->get()
            ->each(function (Payment $payment): void {
                $payment->forceFill(['is_active' => false])->save();
            });
    }

    /**
     * Batas waktu percobaan pembayaran dari kebijakan periode.
     *
     * @param  array<string, mixed>|null  $policy
     */
    protected function expiryFor(?array $policy): ?Carbon
    {
        $hours = $policy['payment_window_hours'] ?? null;

        if (! is_numeric($hours) || (int) $hours <= 0) {
            return null;
        }

        return now()->addHours((int) $hours);
    }

    /**
     * Provider pembayaran berdasarkan nama.
     *
     * @throws PaymentException
     */
    public function provider(string $name): PaymentProviderInterface
    {
        $class = self::PROVIDERS[$name] ?? null;

        if ($class === null) {
            throw PaymentException::providerNotAvailable($name);
        }

        return app($class);
    }
}
