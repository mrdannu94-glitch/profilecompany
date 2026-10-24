<?php

namespace App\Enums;

/**
 * Status satu revisi konten CMS.
 *
 * Alur normal: draft -> submitted -> approved -> published. Revisi dapat
 * dikembalikan menjadi needs_revision sebelum disetujui. Konten yang sudah
 * terbit tidak diedit langsung; perubahan membuat revisi baru berstatus
 * draft sehingga versi lama tetap tampil di website publik.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case NeedsRevision = 'needs_revision';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Menunggu Review',
            self::NeedsRevision => 'Perlu Revisi',
            self::Approved => 'Disetujui',
            self::Published => 'Terbit',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Revisi yang masih boleh diubah penulisnya.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::NeedsRevision], true);
    }

    /**
     * Revisi yang boleh diajukan untuk review.
     */
    public function canBeSubmitted(): bool
    {
        return $this->isEditable();
    }

    /**
     * Revisi yang siap dipublikasikan.
     */
    public function canBePublished(): bool
    {
        return $this === self::Approved;
    }
}
