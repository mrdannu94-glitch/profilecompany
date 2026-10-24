<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_tanpa_role_tidak_bisa_membuka_dasbor_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.admin'))
            ->assertForbidden();
    }

    public function test_admin_bisa_membuka_dasbor_admin(): void
    {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Dasbor Administrator');
    }

    public function test_role_tidak_bisa_membuka_dasbor_role_lain(): void
    {
        $user = $this->userWithRole('teacher');

        $this->actingAs($user)
            ->get(route('dashboard.admin'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk();
    }

    public function test_permission_menjaga_akses_modul(): void
    {
        $user = $this->userWithRole('ppdb_officer');
        $user->roles()->first()->permissions()->attach(
            Permission::create([
                'name' => 'verify_ppdb_documents',
                'guard_name' => 'web',
            ])->id
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Verifikasi Dokumen PPDB')
            ->assertDontSee('Lihat Audit Log');
    }

    public function test_halaman_dasbor_menampilkan_modul_sesuai_permission(): void
    {
        $user = $this->userWithRole('admin');
        $user->roles()->first()->permissions()->attach(
            Permission::create([
                'name' => 'view_audit_log',
                'guard_name' => 'web',
            ])->id
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lihat Audit Log');
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /**
     * Buat pengguna yang memiliki satu role.
     */
    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'display_name' => ucfirst($roleName),
        ]);

        $user->roles()->attach($role->id);

        return $user->fresh();
    }
}
