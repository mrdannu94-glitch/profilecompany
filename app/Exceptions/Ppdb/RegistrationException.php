<?php

namespace App\Exceptions\Ppdb;

use RuntimeException;

/**
 * Kegagalan pada proses pendaftaran calon siswa.
 *
 * Pesan pada kelas ini ditulis untuk ditampilkan ke calon siswa dan tidak
 * boleh membocorkan data pendaftaran milik orang lain.
 */
class RegistrationException extends RuntimeException
{
    public static function periodNotAccepting(): self
    {
        return new self('Pendaftaran untuk periode ini sedang tidak dibuka.');
    }

    public static function unitNotInPeriod(): self
    {
        return new self('Jenjang yang dipilih tidak menerima pendaftaran pada periode ini.');
    }

    public static function programNotAllowed(): self
    {
        return new self('Program yang dipilih tidak tersedia untuk jenjang tersebut.');
    }

    public static function programRequired(): self
    {
        return new self('Silakan pilih program yang tersedia untuk jenjang tersebut.');
    }

    public static function duplicate(): self
    {
        return new self('Pendaftaran dengan data yang sama sudah pernah dibuat untuk periode ini. Hubungi CS sekolah bila ini berbeda.');
    }
}
