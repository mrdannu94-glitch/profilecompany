<?php

namespace App\Services\Ppdb;

use App\Models\Registration;

/**
 * Hasil pendaftaran yang berhasil dibuat.
 *
 * Kode akses hanya tersedia pada objek ini dan hanya ditampilkan satu kali
 * kepada calon siswa. Nilai kode tidak pernah disimpan di database maupun
 * di audit log.
 */
final readonly class RegistrationResult
{
    public function __construct(
        public Registration $registration,
        public string $accessCode,
    ) {}
}
