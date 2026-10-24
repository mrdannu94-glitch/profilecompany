<?php

namespace Tests\Feature\Ppdb;

use App\Models\PpdbPeriod;
use App\Models\SchoolUnit;

/**
 * Halaman portal internal PPDB harus dapat dirender untuk role yang berhak.
 */
class AdminPortalTest extends PpdbScenario
{
    public function test_halaman_periode_bisa_diakses_admin(): void
    {
        $admin = $this->makeUserWithPermission([
            'manage_periods',
            'manage_requirements',
            'verify_ppdb_documents',
            'verify_ppdb_payment_manual',
            'set_selection_result',
            'reset_access_code',
        ], 'admin-portal');

        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $this->coverPeriod($period, $unit);

        $this->actingAs($admin)->get(route('admin.ppdb.periods.index'))
            ->assertOk()
            ->assertSee($period->name);

        $this->actingAs($admin)->get(route('admin.ppdb.periods.create'))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.ppdb.periods.edit', $period))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.ppdb.requirements.index'))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.ppdb.registrations.index'))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.ppdb.documents.index'))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.ppdb.payments.index'))
            ->assertOk();
    }

    public function test_halaman_detail_pendaftaran_bisa_diakses(): void
    {
        $admin = $this->makeUserWithPermission([
            'manage_periods',
            'verify_ppdb_documents',
            'verify_ppdb_payment_manual',
            'set_selection_result',
            'reset_access_code',
        ], 'admin-portal');

        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $this->actingAs($admin)
            ->get(route('admin.ppdb.registrations.show', $registration))
            ->assertOk()
            ->assertSee($registration->registration_number);
    }

    public function test_admin_bisa_membuat_periode_baru(): void
    {
        $admin = $this->makeUserWithPermission(['manage_periods'], 'admin-portal');
        $unit = SchoolUnit::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.ppdb.periods.store'), [
                'code' => 'PPDB-2027',
                'name' => 'PPDB 2027/2028',
                'registration_start' => '2027-01-01',
                'registration_end' => '2027-06-30',
                'status' => 'scheduled',
                'registration_fee' => '350000',
                'currency' => 'IDR',
                'school_units' => [$unit->id],
            ])
            ->assertRedirect(route('admin.ppdb.periods.index'));

        $this->assertDatabaseHas('ppdb_periods', ['code' => 'PPDB-2027']);

        $period = PpdbPeriod::query()->where('code', 'PPDB-2027')->firstOrFail();

        $this->assertTrue($period->schoolUnits->contains('id', $unit->id));
    }

    public function test_admin_bisa_menambah_persyaratan_dokumen(): void
    {
        $admin = $this->makeUserWithPermission(['manage_requirements'], 'admin-portal');
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.ppdb.requirements.store'), [
                'ppdb_period_id' => $period->id,
                'school_unit_id' => $unit->id,
                'requirement_code' => 'IJAZAH',
                'name' => 'Scan ijazah',
                'max_file_size' => 2048,
                'sort_order' => 1,
                'is_required' => '1',
                'accepted_mime_types' => ['application/pdf'],
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ppdb_requirements', [
            'ppdb_period_id' => $period->id,
            'requirement_code' => 'IJAZAH',
        ]);
    }
}
