<?php

namespace App\Services\Ppdb;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\StorageVisibility;
use App\Exceptions\Ppdb\DocumentUploadException;
use App\Models\Media;
use App\Models\PpdbRequirement;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service layer dokumen pendaftaran.
 *
 * Berkas PPDB disimpan pada disk privat dengan nama acak. Unggahan ulang
 * menjadi versi baru sehingga riwayat pemeriksaan sebelumnya tetap ada.
 */
class DocumentService
{
    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Simpan berkas dokumen untuk satu persyaratan.
     *
     * @throws DocumentUploadException
     */
    public function store(Registration $registration, PpdbRequirement $requirement, UploadedFile $file): RegistrationDocument
    {
        $this->assertRegistrationOpen($registration);
        $this->assertRequirementApplies($registration, $requirement);
        $this->assertFileAccepted($requirement, $file);

        $disk = (string) config('dmr.ppdb.private_disk', 'ppdb');
        $path = $this->storeFileOnPrivateDisk($disk, $registration, $file);

        $media = new Media;
        $media->forceFill([
            'uploaded_by' => null,
            'disk' => $disk,
            'file_path' => $path,
            'original_name' => Str::limit((string) $file->getClientOriginalName(), 180, ''),
            'mime_type' => (string) $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
            'storage_visibility' => StorageVisibility::Private,
            'checksum' => hash_file('sha256', $file->getRealPath()),
        ])->save();

        $document = DB::transaction(function () use ($registration, $requirement, $media): RegistrationDocument {
            $version = (int) RegistrationDocument::query()
                ->where('registration_id', $registration->id)
                ->where('ppdb_requirement_id', $requirement->id)
                ->max('version_number') + 1;

            $document = new RegistrationDocument;
            $document->forceFill([
                'registration_id' => $registration->id,
                'ppdb_requirement_id' => $requirement->id,
                'media_id' => $media->id,
                'version_number' => $version,
                'status' => DocumentStatus::Uploaded,
                'uploaded_at' => now(),
            ])->save();

            return $document;
        });

        $this->audit->log(
            action: 'ppdb.document.uploaded',
            entityType: RegistrationDocument::class,
            entityId: $document->id,
            outcome: 'success',
            description: 'Dokumen pendaftaran diunggah oleh calon siswa.',
            context: [
                'registration_number' => $registration->registration_number,
                'requirement_code' => $requirement->requirement_code,
                'version_number' => $document->version_number,
                'mime_type' => $media->mime_type,
                'file_size' => $media->file_size,
            ],
        );

        $this->syncRegistrationStatus($registration);

        return $document;
    }

    /**
     * Pendaftaran yang sudah selesai atau kedaluwarsa tidak menerima
     * unggahan baru.
     *
     * @throws DocumentUploadException
     */
    protected function assertRegistrationOpen(Registration $registration): void
    {
        if (in_array($registration->status, [
            RegistrationStatus::Completed,
            RegistrationStatus::Expired,
        ], true)) {
            throw DocumentUploadException::registrationClosed();
        }
    }

    /**
     * Selaraskan status pendaftaran dengan kondisi dokumen terbaru.
     *
     * Status dokumen dan pembayaran tetap terpisah; nilai ini hanya
     * merangkum tahap dokumen pada pendaftaran.
     */
    public function syncRegistrationStatus(Registration $registration): void
    {
        if (in_array($registration->status, [
            RegistrationStatus::Completed,
            RegistrationStatus::Expired,
        ], true)) {
            return;
        }

        $hasReturnedDocument = $this->latestDocuments($registration)
            ->contains(fn (RegistrationDocument $document): bool => in_array(
                $document->status,
                [DocumentStatus::NeedsRevision, DocumentStatus::Rejected],
                true
            ));

        $status = match (true) {
            $hasReturnedDocument => RegistrationStatus::DocumentsRejected,
            $this->missingRequirements($registration)->isNotEmpty() => RegistrationStatus::DocumentsPending,
            default => RegistrationStatus::DocumentsApproved,
        };

        if ($registration->status !== $status) {
            $registration->forceFill(['status' => $status])->save();
        }
    }

    /**
     * Persyaratan harus berlaku untuk pendaftaran terkait.
     *
     * @throws DocumentUploadException
     */
    protected function assertRequirementApplies(Registration $registration, PpdbRequirement $requirement): void
    {
        $samePeriod = $requirement->ppdb_period_id === $registration->ppdb_period_id;
        $sameUnit = $requirement->school_unit_id === $registration->school_unit_id;
        $sameProgram = $requirement->program_id === null
            || $requirement->program_id === $registration->program_id;

        if (! $samePeriod || ! $sameUnit || ! $sameProgram || ! $requirement->is_active) {
            throw DocumentUploadException::requirementNotApplicable();
        }
    }

    /**
     * Validasi jenis berkas dan ukuran memakai nilai dari server.
     *
     * @throws DocumentUploadException
     */
    protected function assertFileAccepted(PpdbRequirement $requirement, UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw DocumentUploadException::mimeNotAllowed($file->getClientOriginalName());
        }

        $accepted = $requirement->accepted_mime_types ?? [];

        if ($accepted !== [] && ! in_array((string) $file->getMimeType(), $accepted, true)) {
            throw DocumentUploadException::mimeNotAllowed($file->getClientOriginalName());
        }

        $maxSize = (int) $requirement->max_file_size;

        if ($maxSize > 0 && (int) $file->getSize() > $maxSize) {
            throw DocumentUploadException::tooLarge(
                $file->getClientOriginalName(),
                (int) round($maxSize / 1024)
            );
        }
    }

    /**
     * Simpan berkas dengan nama acak pada disk privat.
     */
    protected function storeFileOnPrivateDisk(string $disk, Registration $registration, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = Str::uuid()->toString().'.'.$extension;
        $directory = 'registrations/'.$registration->id.'/'.now()->format('Y/m');

        Storage::disk($disk)->putFileAs($directory, $file, $name);

        return $directory.'/'.$name;
    }

    /**
     * Versi dokumen terbaru untuk setiap persyaratan.
     *
     * @return Collection<int, RegistrationDocument>
     */
    public function latestDocuments(Registration $registration): Collection
    {
        $latest = RegistrationDocument::query()
            ->where('registration_id', $registration->id)
            ->orderByDesc('version_number')
            ->get()
            ->unique('ppdb_requirement_id');

        return $latest->values();
    }

    /**
     * Persyaratan wajib yang belum memiliki dokumen yang disetujui.
     *
     * @return Collection<int, PpdbRequirement>
     */
    public function missingRequirements(Registration $registration): Collection
    {
        $requirements = PpdbRequirement::query()
            ->where('ppdb_period_id', $registration->ppdb_period_id)
            ->where('school_unit_id', $registration->school_unit_id)
            ->where('is_active', true)
            ->where('is_required', true)
            ->where(function ($query) use ($registration): void {
                $query->whereNull('program_id');

                if ($registration->program_id !== null) {
                    $query->orWhere('program_id', $registration->program_id);
                }
            })
            ->orderBy('sort_order')
            ->get();

        $satisfied = $this->latestDocuments($registration)
            ->filter(fn (RegistrationDocument $document): bool => $document->status === DocumentStatus::Approved)
            ->pluck('ppdb_requirement_id')
            ->all();

        return $requirements->reject(fn (PpdbRequirement $requirement): bool => in_array($requirement->id, $satisfied, true))
            ->values();
    }

    /**
     * Unduh berkas privat melalui endpoint yang memeriksa otorisasi.
     */
    public function download(RegistrationDocument $document): StreamedResponse
    {
        $media = $document->media;

        abort_if($media === null, 404);

        return Storage::disk($media->disk)->download(
            $media->file_path,
            $media->original_name ?: 'dokumen',
            ['Content-Type' => $media->mime_type]
        );
    }
}
