<?php

namespace App\Exceptions\Ppdb;

use RuntimeException;

/**
 * Kegagalan pada unggah dokumen pendaftaran.
 *
 * Pesan menjelaskan bagian yang perlu diperbaiki calon siswa tanpa
 * menampilkan detail internal server.
 */
class DocumentUploadException extends RuntimeException
{
    public static function requirementNotApplicable(): self
    {
        return new self('Persyaratan dokumen ini tidak berlaku untuk pendaftaran Anda.');
    }

    public static function mimeNotAllowed(string $name): self
    {
        return new self("Format berkas {$name} tidak diterima. Gunakan format yang tercantum pada persyaratan.");
    }

    public static function tooLarge(string $name, int $maxKilobytes): self
    {
        return new self("Ukuran berkas {$name} melebihi batas {$maxKilobytes} KB.");
    }
}
