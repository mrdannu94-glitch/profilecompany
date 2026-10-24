<?php

namespace Tests\Feature\Foundation;

use App\Enums\PpdbPeriodStatus;
use App\Models\Permission;
use App\Models\PpdbPeriod;
use App\Models\Program;
use App\Models\Role;
use App\Models\SchoolUnit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_role_dan_permission_dasar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertNotNull(Role::where('name', 'admin')->first());
        $this->assertNotNull(Role::where('name', 'cs_admin')->first());
        $this->assertNotNull(Role::where('name', 'ppdb_officer')->first());
        $this->assertNotNull(Role::where('name', 'teacher')->first());
        $this->assertNotNull(Role::where('name', 'staff')->first());
        $this->assertNotNull(Role::where('name', 'student')->first());

        $this->assertNotNull(Permission::where('name', 'manage_users')->first());
        $this->assertNotNull(Permission::where('name', 'verify_ppdb_documents')->first());
    }

    public function test_admin_menerima_seluruh_permission(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Role::where('name', 'admin')->first();

        $this->assertSame(
            Permission::count(),
            $admin->permissions()->count()
        );
    }

    public function test_role_khusus_tidak_menerima_permission_luar_dokumentasi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacher = Role::where('name', 'teacher')->first();

        $this->assertEqualsCanonicalizing(
            ['create_content_draft', 'submit_content_review'],
            $teacher->permissions->pluck('name')->all()
        );
    }

    public function test_seeder_membuat_unit_dan_program_placeholder(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, SchoolUnit::count());
        $this->assertSame(2, Program::count());

        $smk = SchoolUnit::where('code', 'SMK')->first();

        $this->assertNotNull($smk);
        $this->assertEqualsCanonicalizing(
            ['Otomotif', 'PPLG (Pengembangan Perangkat Lunak dan Gim)'],
            $smk->programs->pluck('name')->all()
        );
    }

    public function test_seeder_membuat_akun_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@dmr.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermission('manage_users'));
    }

    public function test_seeder_membuat_periode_ppdb_placeholder_yang_belum_membuka_pendaftaran(): void
    {
        $this->seed(DatabaseSeeder::class);

        $period = PpdbPeriod::query()->where('code', 'PPDB-PLACEHOLDER')->first();

        $this->assertNotNull($period);
        $this->assertSame(PpdbPeriodStatus::Draft, $period->status);
        $this->assertNull($period->registration_fee);
        $this->assertSame(3, $period->schoolUnits()->count());
    }
}
