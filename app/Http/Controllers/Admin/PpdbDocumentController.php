<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\RegistrationDocument;
use App\Services\Ppdb\DocumentReviewService;
use App\Services\Ppdb\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pemeriksaan dokumen pendaftaran oleh petugas PPDB.
 */
class PpdbDocumentController extends Controller
{
    public function __construct(
        protected readonly DocumentReviewService $reviews,
        protected readonly DocumentService $documents,
    ) {}

    /**
     * Antrean dokumen yang menunggu pemeriksaan.
     */
    public function index(Request $request): View
    {
        $documents = RegistrationDocument::query()
            ->with(['registration.schoolUnit', 'requirement', 'media'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = $request->string('status')->toString();

                if ($status === 'queue') {
                    $query->whereIn('status', [DocumentStatus::Uploaded, DocumentStatus::UnderReview]);
                } else {
                    $query->where('status', $status);
                }
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ppdb.documents.index', [
            'documents' => $documents,
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    /**
     * Catat keputusan pemeriksaan dokumen.
     */
    public function review(Request $request, RegistrationDocument $document): RedirectResponse
    {
        $this->authorize('review', $document);

        $action = $request->string('action')->toString();
        $note = $request->string('note')->trim()->toString();

        if ($action === 'approve') {
            $this->reviews->approve($document, $request->user(), $note ?: null);
            $message = 'Dokumen disetujui.';
        } elseif ($action === 'request_revision') {
            if ($note === '') {
                return back()->withErrors(['note' => 'Catatan koreksi wajib diisi saat mengembalikan dokumen.']);
            }

            $this->reviews->requestRevision($document, $request->user(), $note);
            $message = 'Dokumen dikembalikan kepada calon siswa.';
        } elseif ($action === 'reject') {
            if ($note === '') {
                return back()->withErrors(['note' => 'Alasan penolakan wajib diisi.']);
            }

            $this->reviews->reject($document, $request->user(), $note);
            $message = 'Dokumen ditolak.';
        } else {
            return back()->withErrors(['action' => 'Tindakan pemeriksaan tidak dikenali.']);
        }

        return back()->with('status', $message);
    }

    /**
     * Unduh berkas dokumen privat.
     */
    public function download(RegistrationDocument $document)
    {
        $this->authorize('download', $document);

        return $this->documents->download($document);
    }
}
