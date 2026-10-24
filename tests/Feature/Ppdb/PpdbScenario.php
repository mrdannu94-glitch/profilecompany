<?php

namespace Tests\Feature\Ppdb;

use App\Enums\PpdbPeriodStatus;
use App\Enums\RegistrationStatus;
use App\Models\Permission;
use App\Models\PpdbPeriod;
use App\Models\PpdbRequirement;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Role;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Basis skenario PPDB untuk feature test.
 *
 * Helper di sini membangun data periode, unit, persyaratan, dan pengguna
 * internal dengan permission tertentu supaya setiap test fokus pada satu
 * perilaku yang diuji.
 */
abstract class PpdbScenario extends TestCase
{
    use RefreshDatabase;

    /**
     * Periode PPDB yang menerima pendaftaran.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makePeriod(array $attributes = []): PpdbPeriod
    {
        $period = PpdbPeriod::forceCreate(array_merge([
            'code' => 'PPDB-'.uniqid(),
            'name' => 'PPDB 2026/2027',
            'registration_start' => now()->subDay()->toDateString(),
            'registration_end' => now()->addMonth()->toDateString(),
            'status' => PpdbPeriodStatus::Open,
            'currency' => 'IDR',
            'created_by' => User::factory()->create()->id,
        ], $attributes));

        return $period;
    }

    /**
     * Hubungkan unit ke periode.
     */
    protected function coverPeriod(PpdbPeriod $period, SchoolUnit $unit): void
    {
        $period->schoolUnits()->attach($unit->id);
    }

    /**
     * Persyaratan dokumen wajib.
     */
    protected function makeRequirement(
        PpdbPeriod $period,
        SchoolUnit $unit,
        ?Program $program = null,
        array $attributes = [],
    ): PpdbRequirement {
        return PpdbRequirement::create(array_merge([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program?->id,
            'requirement_code' => 'IJAZAH',
            'name' => 'Scan ijazah',
            'accepted_mime_types' => ['application/pdf', 'image/jpeg'],
            'max_file_size' => 2097152,
            'is_required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Pendaftaran yang sudah terbit.
     */
    protected function makeRegistration(
        PpdbPeriod $period,
        SchoolUnit $unit,
        ?Program $program = null,
        string $plainCode = 'KODEAKSES',
        array $attributes = [],
    ): Registration {
        return Registration::forceCreate(array_merge([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program?->id,
            'registration_number' => 'PPDB-2026-'.strtoupper(uniqid()),
            'candidate_name' => 'Calon Siswa',
            'parent_phone' => '081200000000',
            'access_code_hash' => Hash::make($plainCode),
            'access_code_expires_at' => now()->addDays(30),
            'identity_fingerprint' => 'fingerprint-'.uniqid(),
            'status' => RegistrationStatus::Submitted,
            'submitted_at' => now(),
        ], $attributes));
    }

    /**
     * Pengguna internal dengan satu permission.
     *
     * @param  array<int, string>  $permissions
     */
    protected function makeUserWithPermission(array $permissions, string $roleName = 'petugas'): User
    {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => $roleName.'-'.uniqid(),
            'guard_name' => 'web',
            'display_name' => 'Peran Uji',
        ]);

        foreach ($permissions as $permission) {
            $model = Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web', 'description' => 'Permission uji.']
            );

            $role->permissions()->attach($model->id);
        }

        $user->roles()->attach($role->id);

        return $user->fresh();
    }
}
