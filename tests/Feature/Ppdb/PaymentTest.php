<?php

namespace Tests\Feature\Ppdb;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationMethod;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\SchoolUnit;
use App\Services\Ppdb\PaymentService;

/**
 * Verifikasi pembayaran pendaftaran.
 */
class PaymentTest extends PpdbScenario
{
    public function test_petugas_dengan_permission_bisa_mengonfirmasi_pembayaran(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)
            ->post(route('admin.ppdb.payments.confirm', $payment), ['note' => 'Dana diterima.'])
            ->assertSessionHasNoErrors();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame($officer->id, $payment->verified_by);
        $this->assertSame(VerificationMethod::Manual, $payment->verification_method);
        $this->assertNotNull($payment->confirmed_at);
    }

    public function test_konfirmasi_pembayaran_mencatat_audit_log(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)->post(route('admin.ppdb.payments.confirm', $payment), [
            'note' => 'Dana diterima.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ppdb.payment.confirmed',
            'entity_type' => Payment::class,
            'entity_id' => (string) $payment->id,
            'actor_user_id' => $officer->id,
            'outcome' => 'success',
        ]);
    }

    public function test_audit_log_tidak_menyimpan_nilai_rahasia(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODESEKRET');
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)->post(route('admin.ppdb.payments.confirm', $payment), [
            'note' => 'Dana diterima.',
        ]);

        $log = AuditLog::query()->where('action', 'ppdb.payment.confirmed')->firstOrFail();

        $this->assertStringNotContainsString('KODESEKRET', (string) $log->description);
        $this->assertStringNotContainsString($registration->access_code_hash, (string) $log->description);
    }

    public function test_pengguna_tanpa_permission_tidak_bisa_mengonfirmasi_pembayaran(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $user = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($user)
            ->post(route('admin.ppdb.payments.confirm', $payment))
            ->assertForbidden();

        $this->assertSame(PaymentStatus::PendingPayment, $payment->fresh()->status);
    }

    public function test_pembayaran_gagal_wajib_punya_alasan(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)
            ->post(route('admin.ppdb.payments.fail', $payment))
            ->assertSessionHasErrors('note');

        $this->assertSame(PaymentStatus::PendingPayment, $payment->fresh()->status);
    }

    public function test_pembayaran_gagal_menonaktifkan_percobaan(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)->post(route('admin.ppdb.payments.fail', $payment), [
            'note' => 'Nominal tidak sesuai.',
        ])->assertSessionHasNoErrors();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertFalse($payment->is_active);
        $this->assertSame('Nominal tidak sesuai.', $payment->failure_reason);
    }

    public function test_percobaan_pembayaran_baru_menonaktifkan_percobaan_lama(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $first = $this->makePayment($registration);

        $second = app(PaymentService::class)->createAttempt($registration);

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->is_active);
        $this->assertSame(2, $second->attempt_number);
        $this->assertSame(1, Payment::query()->where('is_active', true)->count());
    }

    public function test_status_pendaftaran_naik_setelah_pembayaran_dikonfirmasi(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $payment = $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)->post(route('admin.ppdb.payments.confirm', $payment));

        $this->assertSame(RegistrationStatus::DocumentsPending, $registration->fresh()->status);
    }

    public function test_daftar_pembayaran_hanya_untuk_pengguna_berhak(): void
    {
        $period = $this->makePeriod(['registration_fee' => 250000]);
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);
        $this->makePayment($registration);

        $officer = $this->makeUserWithPermission(['verify_ppdb_payment_manual']);

        $this->actingAs($officer)
            ->get(route('admin.ppdb.payments.index'))
            ->assertOk()
            ->assertSee($registration->registration_number);

        $other = $this->makeUserWithPermission(['view_student_dashboard']);

        $this->actingAs($other)
            ->get(route('admin.ppdb.payments.index'))
            ->assertForbidden();
    }

    /**
     * Percobaan pembayaran yang menunggu verifikasi.
     */
    protected function makePayment($registration): Payment
    {
        return Payment::create([
            'registration_id' => $registration->id,
            'provider' => 'manual',
            'payment_method' => 'bank_transfer',
            'amount' => 250000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PendingPayment,
            'verification_method' => VerificationMethod::Manual,
            'attempt_number' => 1,
            'is_active' => true,
        ]);
    }
}
