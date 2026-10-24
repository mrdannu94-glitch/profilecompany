<?php

namespace App\Policies;

use App\Models\RegistrationDocument;
use App\Models\User;

/**
 * Otorisasi pemeriksaan dan pengunduhan dokumen pendaftaran.
 *
 * Dokumen PPDB bersifat privat. Hanya petugas yang berwenang yang boleh
 * melihat berkas tersebut, dan pengunduhan selalu melalui endpoint yang
 * memeriksa policy ini.
 */
class RegistrationDocumentPolicy
{
    /**
     * Lihat daftar dokumen yang menunggu pemeriksaan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('verify_ppdb_documents');
    }

    /**
     * Lihat detail satu dokumen.
     */
    public function view(User $user, RegistrationDocument $document): bool
    {
        return $user->hasPermission('verify_ppdb_documents');
    }

    /**
     * Periksa dokumen: setujui, kembalikan, atau tolak.
     */
    public function review(User $user, RegistrationDocument $document): bool
    {
        return $user->hasPermission('verify_ppdb_documents');
    }

    /**
     * Unduh berkas dokumen privat.
     */
    public function download(User $user, RegistrationDocument $document): bool
    {
        return $user->hasPermission('verify_ppdb_documents');
    }
}
