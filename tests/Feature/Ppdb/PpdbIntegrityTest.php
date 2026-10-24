<?php

namespace Tests\Feature\Ppdb;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PpdbPeriodStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SelectionDecisionStatus;
use App\Models\Media;
use App\Models\Payment;
use App\Models\PpdbPeriod;
use App\Models\PpdbPeriodUnit;
use App\Models\PpdbRequirement;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Models\SchoolUnit;
use App\Models\SelectionDecision;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test untuk temuan audit integrasi 8 model PPDB.
 *
 * Setiap test mengunci satu perbaikan agar data tidak rusak dan kolom
 * yang diterbitkan server tidak dapat diisi dari input pengguna.
 */
class PpdbIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_terbitkan_server_tidak_bisa_diisi_mass_assignment(): void
    {
        [$period, $unit] = $this->makeScope();

        $registration = new Registration;
        $registration->fill([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Calon Siswa',
            'registration_number' => 'REG-DIPAKSAI',
            'access_code_hash' => 'hash-palsu',
            'identity_fingerprint' => 'fingerprint-palsu',
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        $attributes = $registration->getAttributes();

        $this->assertArrayNotHasKey('registration_number', $attributes);
        $this->assertArrayNotHasKey('access_code_hash', $attributes);
        $this->assertArrayNotHasKey('identity_fingerprint', $attributes);
        $this->assertArrayNotHasKey('status', $attributes);
        $this->assertArrayNotHasKey('submitted_at', $attributes);
        $this->assertSame('Calon Siswa', $attributes['candidate_name']);
    }

    public function test_nomor_pendaftaran_wajib_diterbitkan_server(): void
    {
        [$period, $unit] = $this->makeScope();

        $this->expectException(QueryException::class);

        Registration::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Calon Siswa',
        ]);
    }

    public function test_persyaratan_dengan_scope_sama_ditolak(): void
    {
        [$period, $unit] = $this->makeScope();

        $this->makeRequirement($period, $unit, 'IJAZAH');

        $this->expectException(QueryException::class);

        $this->makeRequirement($period, $unit, 'IJAZAH');
    }

    public function test_persyaratan_umum_dan_khusus_program_dapat_berdampingan(): void
    {
        [$period, $unit] = $this->makeScope();
        $program = Program::factory()->create(['school_unit_id' => $unit->id]);

        $this->makeRequirement($period, $unit, 'IJAZAH');
        $this->makeRequirement($period, $unit, 'IJAZAH', $program);

        $this->assertSame(2, PpdbRequirement::count());
    }

    public function test_hanya_satu_pembayaran_aktif_per_pendaftaran(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);

        $this->makePayment($registration, 1, true);

        $this->expectException(QueryException::class);

        $this->makePayment($registration, 2, true);
    }

    public function test_percobaan_pembayaran_baru_menonaktifkan_percobaan_lama(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);

        $first = $this->makePayment($registration, 1, true);
        $second = $this->makePayment($registration, 2, false);

        $first->forceFill(['is_active' => false])->save();
        $second->forceFill(['is_active' => true])->save();

        $this->assertSame(1, Payment::where('is_active', true)->count());
        $this->assertTrue($registration->fresh()->activePayment->is($second));
    }

    public function test_hanya_satu_keputusan_berlaku_per_pendaftaran(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);

        SelectionDecision::create([
            'registration_id' => $registration->id,
            'decided_by' => User::factory()->create()->id,
            'decision_status' => SelectionDecisionStatus::Pending,
            'decided_at' => now(),
            'is_current' => true,
        ]);

        $this->expectException(QueryException::class);

        SelectionDecision::create([
            'registration_id' => $registration->id,
            'decided_by' => User::factory()->create()->id,
            'decision_status' => SelectionDecisionStatus::Accepted,
            'decided_at' => now(),
            'is_current' => true,
        ]);
    }

    public function test_keputusan_sebelumnya_disimpan_sebagai_riwayat(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);

        $decision = SelectionDecision::create([
            'registration_id' => $registration->id,
            'decided_by' => User::factory()->create()->id,
            'decision_status' => SelectionDecisionStatus::Accepted,
            'decision_note' => 'Lolos seleksi.',
            'decided_at' => now(),
            'is_current' => true,
        ]);

        $this->assertNull($decision->previous_status);
        $this->assertSame(SelectionDecisionStatus::Accepted, $decision->decision_status);
    }

    public function test_status_disimpan_sebagai_enum(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);
        $requirement = $this->makeRequirement($period, $unit, 'IJAZAH');

        $document = RegistrationDocument::create([
            'registration_id' => $registration->id,
            'ppdb_requirement_id' => $requirement->id,
            'media_id' => Media::create([
                'uploaded_by' => User::factory()->create()->id,
                'disk' => 'local',
                'file_path' => 'ppdb/'.uniqid().'.pdf',
                'original_name' => 'ijazah.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'storage_visibility' => 'private',
            ])->id,
            'version_number' => 1,
            'status' => DocumentStatus::Uploaded,
            'uploaded_at' => now(),
        ]);

        $payment = $this->makePayment($registration, 1, true);

        $this->assertSame(PpdbPeriodStatus::Open, $period->fresh()->status);
        $this->assertSame(RegistrationStatus::Submitted, $registration->fresh()->status);
        $this->assertSame(DocumentStatus::Uploaded, $document->fresh()->status);
        $this->assertSame(PaymentStatus::Confirmed, $payment->fresh()->status);
    }

    public function test_daftar_mime_persyaratan_disimpan_sebagai_array(): void
    {
        [$period, $unit] = $this->makeScope();

        $requirement = $this->makeRequirement($period, $unit, 'IJAZAH');

        $this->assertSame(['application/pdf', 'image/jpeg'], $requirement->fresh()->accepted_mime_types);
    }

    public function test_kode_akses_yang_dicabut_tidak_lagi_berlaku(): void
    {
        [$period, $unit] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit);

        $this->assertTrue($registration->hasUsableAccessCode());

        $registration->forceFill(['access_code_revoked_at' => now()])->save();

        $this->assertFalse($registration->fresh()->hasUsableAccessCode());
    }

    public function test_dua_tautan_periode_dan_unit_beda_dibedakan(): void
    {
        [$period, $unitA] = $this->makeScope();
        $unitB = SchoolUnit::factory()->create();

        $linkA = PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitA->id,
        ]);

        $linkB = PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitB->id,
        ]);

        $this->assertFalse($linkA->is($linkB));
        $this->assertNotSame($linkA->getKey(), $linkB->getKey());
        $this->assertSame(2, $period->periodUnits()->count());
    }

    /**
     * @return array{0: PpdbPeriod, 1: SchoolUnit}
     */
    private function makeScope(): array
    {
        $period = PpdbPeriod::forceCreate([
            'code' => 'PPDB-'.uniqid(),
            'name' => 'PPDB 2026',
            'registration_start' => '2026-11-01',
            'registration_end' => '2026-12-31',
            'status' => PpdbPeriodStatus::Open,
            'currency' => 'IDR',
            'created_by' => User::factory()->create()->id,
        ]);

        return [$period, SchoolUnit::factory()->create()];
    }

    private function makeRequirement(PpdbPeriod $period, SchoolUnit $unit, string $code, ?Program $program = null): PpdbRequirement
    {
        return PpdbRequirement::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program?->id,
            'requirement_code' => $code,
            'name' => 'Ijazah',
            'accepted_mime_types' => ['application/pdf', 'image/jpeg'],
            'max_file_size' => 2097152,
            'is_required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function makeRegistration(PpdbPeriod $period, SchoolUnit $unit): Registration
    {
        return Registration::forceCreate([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'registration_number' => 'REG-'.uniqid(),
            'candidate_name' => 'Calon Siswa',
            'access_code_hash' => hash('sha256', 'kode-akses'),
            'access_code_expires_at' => now()->addDays(30),
            'identity_fingerprint' => 'fingerprint-'.uniqid(),
            'status' => RegistrationStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    private function makePayment(Registration $registration, int $attempt, bool $isActive): Payment
    {
        return Payment::create([
            'registration_id' => $registration->id,
            'provider' => 'manual',
            'payment_method' => 'bank_transfer',
            'amount' => 250000,
            'currency' => 'IDR',
            'status' => PaymentStatus::Confirmed,
            'confirmed_at' => now(),
            'verified_by' => User::factory()->create()->id,
            'verification_method' => 'manual',
            'attempt_number' => $attempt,
            'is_active' => $isActive,
        ]);
    }
}
