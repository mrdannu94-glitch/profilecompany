<?php

namespace App\Http\Controllers\Public;

use App\Enums\DocumentStatus;
use App\Exceptions\Ppdb\DocumentUploadException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\PpdbRequirement;
use App\Models\RegistrationDocument;
use App\Services\Ppdb\DocumentService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unggah dan unduh dokumen pendaftaran oleh calon siswa.
 */
class RegistrationDocumentController extends Controller
{
    public function __construct(
        protected readonly DocumentService $documents,
    ) {}

    /**
     * Simpan berkas dokumen yang diunggah calon siswa.
     */
    public function store(DocumentUploadRequest $request): RedirectResponse
    {
        $registration = $request->attributes->get('registration');

        $requirement = PpdbRequirement::query()->findOrFail($request->integer('ppdb_requirement_id'));

        try {
            $this->documents->store($registration, $requirement, $request->file('document'));
        } catch (DocumentUploadException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return back()->with('status', 'Dokumen berhasil diunggah dan menunggu pemeriksaan petugas.');
    }

    /**
     * Unduh dokumen milik pendaftaran pada sesi ini.
     */
    public function download(RegistrationDocument $document): StreamedResponse
    {
        $registration = request()->attributes->get('registration');

        abort_unless($document->registration_id === $registration->id, 403);

        return $this->documents->download($document);
    }

    /**
     * Status dokumen yang bisa ditampilkan ke calon siswa.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            DocumentStatus::Uploaded->value => 'Menunggu pemeriksaan',
            DocumentStatus::UnderReview->value => 'Sedang diperiksa',
            DocumentStatus::Approved->value => 'Disetujui',
            DocumentStatus::NeedsRevision->value => 'Perlu diperbaiki',
            DocumentStatus::Rejected->value => 'Ditolak',
        ];
    }
}
