<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_diwarisi_dari_role(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'ppdb_officer',
            'guard_name' => 'web',
            'display_name' => 'Petugas PPDB',
        ]);

        $permission = Permission::create([
            'name' => 'verify_ppdb_documents',
            'guard_name' => 'web',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $user = $user->fresh();

        $this->assertTrue($user->hasRole('ppdb_officer'));
        $this->assertTrue($user->hasPermission('verify_ppdb_documents'));
        $this->assertFalse($user->hasPermission('manage_users'));
    }

    public function test_pengguna_dengan_lebih_dari_satu_role_menggabungkan_permission(): void
    {
        $user = User::factory()->create();

        $teacher = Role::create(['name' => 'teacher', 'guard_name' => 'web', 'display_name' => 'Guru']);
        $staff = Role::create(['name' => 'staff', 'guard_name' => 'web', 'display_name' => 'Staff']);

        $teacher->permissions()->attach(
            Permission::create(['name' => 'create_content_draft', 'guard_name' => 'web'])->id
        );
        $staff->permissions()->attach(
            Permission::create(['name' => 'view_assistance_queue', 'guard_name' => 'web'])->id
        );

        $user->roles()->attach([$teacher->id, $staff->id]);

        $user = $user->fresh();

        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue($user->hasRole('staff'));
        $this->assertTrue($user->hasPermission('create_content_draft'));
        $this->assertTrue($user->hasPermission('view_assistance_queue'));
        $this->assertFalse($user->hasAllPermissions(['create_content_draft', 'manage_users']));
    }

    public function test_pengguna_tanpa_role_tidak_memiliki_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasPermission('manage_users'));
        $this->assertTrue($user->permissionNames()->isEmpty());
    }

    public function test_has_role_menerima_daftar_role(): void
    {
        $user = User::factory()->create();

        $role = Role::create(['name' => 'cs_admin', 'guard_name' => 'web', 'display_name' => 'CS Admin']);
        $user->roles()->attach($role->id);

        $user = $user->fresh();

        $this->assertTrue($user->hasRole(['admin', 'cs_admin']));
        $this->assertFalse($user->hasRole(['admin', 'ppdb_officer']));
        $this->assertTrue($user->hasAllRoles(['cs_admin']));
        $this->assertFalse($user->hasAllRoles(['cs_admin', 'admin']));
    }
}
