<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder role dan permission dasar.
 *
 * Role dan permission disimpan di database, bukan ditulis mati di route atau
 * controller. Daftar di bawah ini adalah titik awal dan dapat disempurnakan
 * melalui portal admin.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Permission yang tersedia beserta penjelasannya.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'manage_users' => 'Mengelola pengguna internal.',
        'manage_roles' => 'Mengelola role dan permission.',
        'manage_school_units' => 'Mengelola unit sekolah.',
        'manage_programs' => 'Mengelola program atau jurusan.',
        'manage_periods' => 'Mengelola periode penerimaan PPDB.',
        'manage_requirements' => 'Mengelola persyaratan dokumen PPDB.',
        'manage_content' => 'Mengelola konten dan revisi konten.',
        'publish_content' => 'Mempublikasikan konten ke website publik.',
        'review_content' => 'Meninjau revisi konten sebelum dipublikasikan.',
        'verify_payment' => 'Memverifikasi pembayaran PPDB.',
        'verify_ppdb_documents' => 'Memverifikasi dokumen pendaftaran PPDB.',
        'verify_ppdb_payment_manual' => 'Memkonfirmasi pembayaran PPDB secara manual.',
        'set_selection_result' => 'Menetapkan hasil seleksi calon siswa.',
        'view_assistance_queue' => 'Melihat antrean bantuan calon siswa.',
        'reset_access_code' => 'Memulihkan kode akses pendaftaran PPDB.',
        'view_audit_log' => 'Melihat audit log aktivitas sensitif.',
        'create_content_draft' => 'Membuat draf konten.',
        'submit_content_review' => 'Mengirim draf konten untuk ditinjau.',
        'view_student_dashboard' => 'Melihat dasbor siswa.',
    ];

    /**
     * Role beserta permission yang dimilikinya.
     *
     * `*` berarti role tersebut menerima seluruh permission yang tersedia.
     *
     * @var array<string, array{display_name: string, description: string, permissions: array<int, string>}>
     */
    public const ROLES = [
        'admin' => [
            'display_name' => 'Administrator',
            'description' => 'Mengelola seluruh isi portal internal, termasuk role, konten, dan PPDB.',
            'permissions' => ['*'],
        ],
        'cs_admin' => [
            'display_name' => 'CS Admin',
            'description' => 'Menangani bantuan calon siswa dan pemulihan kode akses.',
            'permissions' => ['view_assistance_queue', 'reset_access_code'],
        ],
        'ppdb_officer' => [
            'display_name' => 'Petugas PPDB',
            'description' => 'Memverifikasi dokumen dan pembayaran manual pendaftaran PPDB.',
            'permissions' => ['verify_ppdb_documents', 'verify_ppdb_payment_manual'],
        ],
        'teacher' => [
            'display_name' => 'Guru',
            'description' => 'Menyusun dan mengirim konten untuk ditinjau.',
            'permissions' => ['create_content_draft', 'submit_content_review'],
        ],
        'staff' => [
            'display_name' => 'Staff',
            'description' => 'Menyusun dan mengirim konten untuk ditinjau.',
            'permissions' => ['create_content_draft', 'submit_content_review'],
        ],
        'student' => [
            'display_name' => 'Siswa',
            'description' => 'Melihat dasbor siswa.',
            'permissions' => ['view_student_dashboard'],
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'description' => $description]
            );
        }

        foreach (self::ROLES as $name => $definition) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'guard_name' => 'web',
                    'display_name' => $definition['display_name'],
                    'description' => $definition['description'],
                ]
            );

            $permissionNames = $definition['permissions'] === ['*']
                ? array_keys(self::PERMISSIONS)
                : $definition['permissions'];

            $role->permissions()->sync(
                Permission::whereIn('name', $permissionNames)->pluck('id')->all()
            );
        }
    }
}
