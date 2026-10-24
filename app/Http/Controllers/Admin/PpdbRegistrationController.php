<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\SchoolUnit;
use App\Services\Ppdb\AccessCodeService;
use App\Services\Ppdb\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan data pendaftaran PPDB oleh petugas.
 */
class PpdbRegistrationController extends Controller
{
    public function __construct(
        protected readonly AccessCodeService $accessCodes,
        protected readonly DocumentService $documents,
    ) {}

    /**
     * Daftar pendaftaran.
     */
    public function index(Request $request): View
    {
        $registrations = Registration::query()
            ->with(['schoolUnit', 'program', 'period'])
            ->when($request->filled('period'), fn ($query) => $query->where(
                'ppdb_period_id',
                $request->integer('period')
            ))
            ->when($request->filled('unit'), fn ($query) => $query->where(
                'school_unit_id',
                $request->integer('unit')
            ))
            ->when($request->filled('status'), fn ($query) => $query->where(
                'status',
                $request->string('status')->toString()
            ))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim().'%';

                $query->where(function ($inner) use ($keyword): void {
                    $inner->where('registration_number', 'like', $keyword)
                        ->orWhere('candidate_name', 'like', $keyword);
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ppdb.registrations.index', [
            'registrations' => $registrations,
            'periods' => PpdbPeriod::query()->orderByDesc('registration_start')->get(),
            'units' => SchoolUnit::query()->orderBy('level')->get(),
            'statuses' => RegistrationStatus::cases(),
        ]);
    }

    /**
     * Detail satu pendaftaran.
     */
    public function show(Registration $registration): View
    {
        $registration->loadMissing(['schoolUnit', 'program', 'period', 'documents.requirement', 'documents.media', 'payments', 'selectionDecisions.decider']);

        return view('admin.ppdb.registrations.show', [
            'registration' => $registration,
            'missingRequirements' => $this->documents->missingRequirements($registration),
        ]);
    }

    /**
     * Terbitkan kode akses baru dan batalkan kode lama.
     */
    public function recoverAccessCode(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorize('resetAccessCode', $registration);

        $code = $this->accessCodes->recover(
            $registration,
            $request->user(),
            $request->string('note')->toString() ?: null
        );

        return redirect()
            ->route('admin.ppdb.registrations.show', $registration)
            ->with('access_code', $code)
            ->with('status', 'Kode akses baru telah diterbitkan. Kode lama sudah dibatalkan dan hanya ditampilkan sekali.');
    }
}
