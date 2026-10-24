<?php

namespace Tests\Feature\Ppdb;

use App\Enums\PpdbPeriodStatus;
use App\Enums\RegistrationStatus;
use App\Models\Program;
use App\Models\Registration;
use App\Models\SchoolUnit;
use Illuminate\Support\Facades\Hash;

/**
 * Alur pendaftaran calon siswa.
 */
class RegistrationTest extends PpdbScenario
{
    public function test_halaman_informasi_ppdb_bisa_diakses(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->get(route('ppdb.info'))
            ->assertOk()
            ->assertSee('Informasi PPDB')
            ->assertSee($period->name);
    }

    public function test_formulir_pendaftaran_bisa_diakses_saat_periode_dibuka(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->get(route('ppdb.register'))
            ->assertOk()
            ->assertSee('Formulir Pendaftaran');
    }

    public function test_formulir_pendaftaran_menolak_saat_tidak_ada_periode_aktif(): void
    {
        $this->makePeriod(['status' => PpdbPeriodStatus::Closed]);

        $this->get(route('ppdb.register'))
            ->assertRedirect(route('ppdb.info'))
            ->assertSessionHasErrors('periode');
    }

    public function test_pendaftaran_menerbitkan_nomor_dan_kode_akses(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $response = $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'date_of_birth' => '2010-05-04',
            'parent_name' => 'Bapak Rahma',
            'parent_phone' => '081234567890',
            'parent_email' => 'ortu@contoh.test',
            'consent' => '1',
        ]);

        $response->assertOk();
        $response->assertSee('Simpan nomor dan kode akses Anda');

        $registration = Registration::query()->firstOrFail();

        $this->assertSame('Siti Rahma', $registration->candidate_name);
        $this->assertSame(RegistrationStatus::Submitted, $registration->status);
        $this->assertStringStartsWith('PPDB-', $registration->registration_number);
        $this->assertNotNull($registration->submitted_at);
        $this->assertNotNull($registration->data_consent_at);
    }

    public function test_kode_akses_disimpan_sebagai_hash(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $response = $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ]);

        $response->assertOk();

        $registration = Registration::query()->firstOrFail();

        preg_match('/tracking-widest[^>]*>([A-Z0-9]{8})</', $response->getContent(), $matches);
        $plainCode = $matches[1] ?? '';

        $this->assertNotSame('', $plainCode);
        $this->assertNotSame($plainCode, $registration->access_code_hash);
        $this->assertTrue(Hash::check($plainCode, $registration->access_code_hash));
    }

    public function test_pendaftaran_ganda_dengan_data_sama_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $payload = [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'date_of_birth' => '2010-05-04',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ];

        $this->post(route('ppdb.register.store'), $payload)->assertOk();

        $this->post(route('ppdb.register.store'), $payload)
            ->assertSessionHasErrors('form');

        $this->assertSame(1, Registration::query()->count());
    }

    public function test_unit_di_luar_periode_ditolak(): void
    {
        $period = $this->makePeriod();
        $covered = SchoolUnit::factory()->create();
        $other = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $covered);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $other->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ])->assertSessionHasErrors('form');

        $this->assertSame(0, Registration::query()->count());
    }

    public function test_program_dari_unit_lain_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $other = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $foreignProgram = Program::factory()->create(['school_unit_id' => $other->id]);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $foreignProgram->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ])->assertSessionHasErrors('form');

        $this->assertSame(0, Registration::query()->count());
    }

    public function test_unit_dengan_program_aktif_memilih_satu_program(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $program = Program::factory()->create(['school_unit_id' => $unit->id]);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ])->assertOk();

        $registration = Registration::query()->firstOrFail();

        $this->assertSame($program->id, $registration->program_id);
    }

    public function test_formulir_tanpa_persetujuan_pemrosesan_data_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
        ])->assertSessionHasErrors('consent');

        $this->assertSame(0, Registration::query()->count());
    }

    public function test_pendaftaran_baru_membuat_percobaan_pembayaran(): void
    {
        $period = $this->makePeriod(['registration_fee' => 300000]);
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ])->assertOk();

        $registration = Registration::query()->firstOrFail();
        $payment = $registration->payments()->firstOrFail();

        $this->assertSame('300000.00', $payment->amount);
        $this->assertSame(RegistrationStatus::PaymentPending, $registration->fresh()->status);
    }

    public function test_pendaftaran_tanpa_nominal_biaya_tidak_membuat_pembayaran(): void
    {
        $period = $this->makePeriod(['registration_fee' => null]);
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->post(route('ppdb.register.store'), [
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'candidate_name' => 'Siti Rahma',
            'parent_phone' => '081234567890',
            'consent' => '1',
        ])->assertOk();

        $registration = Registration::query()->firstOrFail();

        $this->assertSame(0, $registration->payments()->count());
    }
}
