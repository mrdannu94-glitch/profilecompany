<?php

namespace App\Services\Ppdb;

use App\Enums\DocumentStatus;
use App\Models\RegistrationDocument;
use App\Models\User;
use App\Services\AuditLogger;

/**
 * Service layer pemeriksaan dokumen pendaftaran.
 *
 * Setiap keputusan pemeriksaan mencatat pelaku, waktu, dan catatan yang
 * dapat dibaca calon siswa. Catatan koreksi wajib diisi ketika dokumen
 * dikembalikan.
 */
class DocumentReviewService
{
    public function __construct(
        protected readonly AuditLogger $audit,
        protected readonly DocumentService $documents,
    ) {}

    /**
     * Tandai dokumen sedang diperiksa.
     */
    public function startReview(RegistrationDocument $document, User $actor): RegistrationDocument
    {
        $document->forceFill([
            'status' => DocumentStatus::UnderReview,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
        ])->save();

        $this->audit->log(
            action: 'ppdb.document.review_started',
            entityType: RegistrationDocument::class,
            entityId: $document->id,
            outcome: 'success',
            actor: $actor,
            description: 'Pemeriksaan dokumen dimulai.',
            context: ['registration_number' => $document->registration?->registration_number],
        );

        return $document;
    }

    /**
     * Setujui dokumen.
     */
    public function approve(RegistrationDocument $document, User $actor, ?string $note = null): RegistrationDocument
    {
        $document->forceFill([
            'status' => DocumentStatus::Approved,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->audit->log(
            action: 'ppdb.document.approved',
            entityType: RegistrationDocument::class,
            entityId: $document->id,
            outcome: 'success',
            actor: $actor,
            description: 'Dokumen pendaftaran disetujui.',
            context: [
                'registration_number' => $document->registration?->registration_number,
                'requirement_code' => $document->requirement?->requirement_code,
                'note' => $note,
            ],
        );

        $this->syncRegistrationStatus($document);

        return $document;
    }

    /**
     * Kembalikan dokumen untuk diperbaiki dengan catatan koreksi.
     */
    public function requestRevision(RegistrationDocument $document, User $actor, string $note): RegistrationDocument
    {
        $document->forceFill([
            'status' => DocumentStatus::NeedsRevision,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $registration = $document->registration;

        $this->syncRegistrationStatus($document);

        $this->audit->log(
            action: 'ppdb.document.needs_revision',
            entityType: RegistrationDocument::class,
            entityId: $document->id,
            outcome: 'success',
            actor: $actor,
            description: 'Dokumen dikembalikan kepada calon siswa untuk diperbaiki.',
            context: [
                'registration_number' => $registration?->registration_number,
                'requirement_code' => $document->requirement?->requirement_code,
                'note' => $note,
            ],
        );

        return $document;
    }

    /**
     * Tolak dokumen tanpa membuka kesempatan perbaikan.
     */
    public function reject(RegistrationDocument $document, User $actor, string $note): RegistrationDocument
    {
        $document->forceFill([
            'status' => DocumentStatus::Rejected,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->syncRegistrationStatus($document);

        $this->audit->log(
            action: 'ppdb.document.rejected',
            entityType: RegistrationDocument::class,
            entityId: $document->id,
            outcome: 'failure',
            actor: $actor,
            description: 'Dokumen pendaftaran ditolak.',
            context: [
                'registration_number' => $document->registration?->registration_number,
                'requirement_code' => $document->requirement?->requirement_code,
                'note' => $note,
            ],
        );

        return $document;
    }

    /**
     * Perbarui status pendaftaran setelah pemeriksaan dokumen.
     *
     * Perhitungan dipusatkan pada DocumentService agar unggahan ulang dan
     * keputusan petugas memakai aturan yang sama.
     */
    protected function syncRegistrationStatus(RegistrationDocument $document): void
    {
        $registration = $document->registration;

        if ($registration === null) {
            return;
        }

        $this->documents->syncRegistrationStatus($registration);
    }

    /**
     * Hitung ulang status pendaftaran setelah perubahan dokumen.
     */
    public function refreshRegistrationStatus(RegistrationDocument $document): void
    {
        $this->syncRegistrationStatus($document);
    }
}
