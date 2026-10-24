<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

/**
 * Otorisasi data pendaftaran.
 *
 * Calon siswa bukan pengguna portal. Halaman statusnya memakai nomor
 * pendaftaran dan kode akses, bukan policy pengguna. Policy ini hanya
 * mengatur pengguna internal.
 */
class RegistrationPolicy
{
    /**
     * Daftar pendaftaran seluruh unit.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(['manage_periods', 'verify_ppdb_documents', 'verify_ppdb_payment_manual']);
    }

    /**
     * Detail satu pendaftaran.
     */
    public function view(User $user, Registration $registration): bool
    {
        return $user->hasPermission([
            'manage_periods',
            'verify_ppdb_documents',
            'verify_ppdb_payment_manual',
            'set_selection_result',
        ]);
    }

    /**
     * Ubah data pendaftaran.
     */
    public function update(User $user, Registration $registration): bool
    {
        return $user->hasPermission('manage_periods');
    }

    /**
     * Pemulihan kode akses pendaftaran.
     */
    public function resetAccessCode(User $user, Registration $registration): bool
    {
        return $user->hasPermission('reset_access_code');
    }

    /**
     * Tetapkan hasil seleksi.
     */
    public function decide(User $user, Registration $registration): bool
    {
        return $user->hasPermission('set_selection_result');
    }
}
