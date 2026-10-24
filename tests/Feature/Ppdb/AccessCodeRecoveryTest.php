<?php

namespace Tests\Feature\Ppdb;

use App\Models\AuditLog;
use App\Models\SchoolUnit;
use Illuminate\Support\Facades\Hash;

/**
 * Pemulihan kode akses pendaftaran.
 */
class AccessCodeRecoveryTest extends PpdbScenario
{
    public function test_cs_admin_boleh_memulihkan_kode_akses(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODELAMA');

        $cs = $this->makeUserWithPermission(['reset_access_code']);

        $response = $this->actingAs($cs)
            ->post(route('admin.ppdb.registrations.access-code', $registration), [
                'note' => 'Verifikasi identitas via CS.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('access_code');

        $newCode = session('access_code');

        $this->assertIsString($newCode);
        $this->assertNotSame('KODELAMA', $newCode);
    }

    public function test_kode_lama_tidak_lagi_berlaku_setelah_pemulihan(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODELAMA');

        $cs = $this->makeUserWithPermission(['reset_access_code']);

        $this->actingAs($cs)->post(route('admin.ppdb.registrations.access-code', $registration));

        $registration->refresh();
        $newCode = session('access_code');

        $this->assertFalse(Hash::check('KODELAMA', $registration->access_code_hash));
        $this->assertTrue(Hash::check($newCode, $registration->access_code_hash));
        $this->assertTrue($registration->hasUsableAccessCode());
    }

    public function test_pemulihan_mencatat_audit_log_tanpa_nilai_kode(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODELAMA');

        $cs = $this->makeUserWithPermission(['reset_access_code']);

        $this->actingAs($cs)->post(route('admin.ppdb.registrations.access-code', $registration), [
            'note' => 'Verifikasi identitas via CS.',
        ]);

        $log = AuditLog::query()
            ->where('action', 'registration.access_code.recovered')
            ->firstOrFail();

        $this->assertSame($cs->id, $log->actor_user_id);
        $this->assertStringNotContainsString('KODELAMA', (string) $log->description);
    }

    public function test_pengguna_tanpa_permission_tidak_bisa_memulihkan_kode(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODELAMA');

        $user = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($user)
            ->post(route('admin.ppdb.registrations.access-code', $registration))
            ->assertForbidden();

        $this->assertTrue(Hash::check('KODELAMA', $registration->fresh()->access_code_hash));
    }

    public function test_calon_siswa_tidak_bisa_memulihkan_kode_lewat_halaman_status(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit, plainCode: 'KODELAMA');

        $this->post(route('ppdb.status.check'), [
            'registration_number' => $registration->registration_number,
            'access_code' => 'KODELAMA',
        ])->assertRedirect(route('ppdb.status.show'));

        $this->assertTrue(Hash::check('KODELAMA', $registration->fresh()->access_code_hash));
    }
}
