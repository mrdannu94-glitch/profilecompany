<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dasbor umum untuk pengguna yang sudah masuk.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.show', [
            'user' => $user,
            'section' => 'umum',
            'modules' => $this->modulesFor($user),
        ]);
    }

    /**
     * Dasbor khusus per role.
     */
    public function show(Request $request, string $role): View
    {
        $user = $request->user();

        return view('dashboard.show', [
            'user' => $user,
            'section' => $role,
            'modules' => $this->modulesFor($user),
        ]);
    }

    /**
     * Daftar modul yang boleh dibuka pengguna berdasarkan permission.
     *
     * @return array<int, array{label: string, description: string, permission: string}>
     */
    protected function modulesFor(User $user): array
    {
        $modules = [
            [
                'label' => 'Kelola Pengguna',
                'description' => 'Menambah pengguna internal dan mengatur role yang dimiliki.',
                'permission' => 'manage_users',
            ],
            [
                'label' => 'Kelola Role & Permission',
                'description' => 'Menyesuaikan role dan permission yang tersedia.',
                'permission' => 'manage_roles',
            ],
            [
                'label' => 'Kelola Unit Sekolah & Program',
                'description' => 'Mendaftarkan unit jenjang dan program yang dimiliki sekolah.',
                'permission' => 'manage_school_units',
            ],
            [
                'label' => 'Kelola Periode PPDB',
                'description' => 'Menyiapkan periode penerimaan dan cakupannya.',
                'permission' => 'manage_periods',
            ],
            [
                'label' => 'Kelola Persyaratan Dokumen',
                'description' => 'Menentukan dokumen wajib per periode, unit, dan program.',
                'permission' => 'manage_requirements',
            ],
            [
                'label' => 'Review & Publikasi Konten',
                'description' => 'Meninjau revisi konten sebelum dipublikasikan.',
                'permission' => 'review_content',
            ],
            [
                'label' => 'Verifikasi Dokumen PPDB',
                'description' => 'Memeriksa berkas dokumen yang diunggah calon siswa.',
                'permission' => 'verify_ppdb_documents',
            ],
            [
                'label' => 'Verifikasi Pembayaran Manual',
                'description' => 'Memkonfirmasi pembayaran pendaftaran yang masuk manual.',
                'permission' => 'verify_ppdb_payment_manual',
            ],
            [
                'label' => 'Tetapkan Hasil Seleksi',
                'description' => 'Mencatat keputusan hasil seleksi calon siswa.',
                'permission' => 'set_selection_result',
            ],
            [
                'label' => 'Lihat Audit Log',
                'description' => 'Meninjau aktivitas sensitif yang tercatat.',
                'permission' => 'view_audit_log',
            ],
        ];

        return array_values(array_filter(
            $modules,
            fn (array $module) => $user->hasPermission($module['permission'])
        ));
    }
}
