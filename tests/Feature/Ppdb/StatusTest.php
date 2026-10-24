<?php

namespace Tests\Feature\Ppdb;

use App\Http\Middleware\EnsureRegistrationAccess;
use App\Models\SchoolUnit;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pemeriksaan status pendaftaran oleh calon siswa.
 */
class StatusTest extends PpdbScenario
{
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test');
    }

    public function test_halaman_pemeriksaan_status_bisa_diakses(): void
    {
        $this->get(route('ppdb.status'))
            ->assertOk()
            ->assertSee('Cek status pendaftaran');
    }

    public function test_kredensial_benar_membuka_halaman_status(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODEAKSES',
        ])->assertRedirect(route('ppdb.status.show'));

        $this->get(route('ppdb.status.show'))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertSee($registration->candidate_name);
    }

    public function test_nomor_pendaftaran_besar_kecil_tidak_berpengaruh(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => strtolower($registration->registration_number),
            'access_code' => 'KODEAKSES',
        ])->assertRedirect(route('ppdb.status.show'));
    }

    public function test_kode_salah_ditolak_dan_tidak_membocorkan_data(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $response = $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODESALAH',
        ]);

        $response->assertSessionHasErrors('kredensial');
        $response->assertSessionDoesntHaveErrors('registration_number');
        $this->assertStringNotContainsString('tidak terdaftar', session('errors')->first('kredensial'));
    }

    public function test_nomor_tidak_terdaftar_memberi_pesan_yang_sama(): void
    {
        $response = $this->post(route('ppdb.status.check'), [
            'registration_number' => 'PPDB-2026-9999',
            'access_code' => 'KODESALAH',
        ]);

        $response->assertSessionHasErrors('kredensial');
        $this->assertSame(
            'Nomor pendaftaran atau kode akses tidak sesuai.',
            session('errors')->first('kredensial')
        );
    }

    public function test_halaman_status_melindungi_akses_tanpa_sesi(): void
    {
        $this->get(route('ppdb.status.show'))
            ->assertRedirect(route('ppdb.status'))
            ->assertSessionHasErrors('kredensial');
    }

    public function test_kode_akses_yang_dicabut_tidak_lagi_dibuka(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODEAKSES',
        ])->assertRedirect(route('ppdb.status.show'));

        $registration->forceFill(['access_code_revoked_at' => now()])->save();

        $this->get(route('ppdb.status.show'))
            ->assertRedirect(route('ppdb.status'));
    }

    public function test_sesi_pendaftaran_tersimpan_di_sesi_browser(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODEAKSES',
        ]);

        $this->assertSame($registration->id, session(EnsureRegistrationAccess::SESSION_KEY));
    }

    public function test_percobaan_status_dibatasi(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $limit = (int) config('dmr.ppdb.status_rate_limit', 10);

        for ($attempt = 0; $attempt < $limit; $attempt++) {
            $this->post(route('ppdb.status.check'), [
                'registration_number' => $registration->registration_number,
                'access_code' => 'KODESALAH',
            ]);
        }

        $response = $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODEAKSES',
        ]);

        $this->assertSame(429, $response->getStatusCode());
    }

    public function test_percobaan_gagal_tercatat_di_audit_log(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODEAKSES');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODESALAH',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'registration.access_check.failed',
            'outcome' => 'failure',
        ]);
    }
}
