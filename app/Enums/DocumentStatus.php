<?php

namespace App\Enums;

/**
 * Status satu versi dokumen pendaftaran.
 *
 * `missing` sengaja tidak ada sebagai nilai baris: status tersebut berarti
 * belum ada unggahan untuk persyaratan terkait, bukan baris dokumen.
 */
enum DocumentStatus: string
{
    case Uploaded = 'uploaded';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case NeedsRevision = 'needs_revision';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Terunggah',
            self::UnderReview => 'Sedang Diperiksa',
            self::Approved => 'Disetujui',
            self::NeedsRevision => 'Perlu Diperbaiki',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Dokumen yang masih boleh diunggah ulang.
     */
    public function allowsReupload(): bool
    {
        return in_array($this, [self::NeedsRevision, self::Rejected], true);
    }

    /**
     * Status yang menutup pemeriksaan dokumen.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }
}
