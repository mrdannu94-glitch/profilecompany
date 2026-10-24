<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRegistrationAccess;
use App\Http\Requests\StatusCheckRequest;
use App\Models\Registration;
use App\Services\Ppdb\AccessCodeService;
use App\Services\Ppdb\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pemeriksaan status pendaftaran.
 *
 * Calon siswa tidak memakai akun. Akses memakai nomor pendaftaran dan kode
 * akses rahasia. Pesan galat sengaja tidak membedakan antara nomor yang
 * tidak terdaftar dan kode yang salah.
 */
class StatusController extends Controller
{
    public function __construct(
        protected readonly AccessCodeService $accessCodes,
        protected readonly DocumentService $documents,
    ) {}

    /**
     * Formulir pemeriksaan status.
     */
    public function create(): View
    {
        return view('public.ppdb.status-form');
    }

    /**
     * Periksa kredensial dan simpan pendaftaran pada sesi.
     */
    public function store(StatusCheckRequest $request): RedirectResponse
    {
        $number = trim((string) $request->input('registration_number'));
        $code = trim((string) $request->input('access_code'));

        $registration = Registration::query()
            ->whereIn('registration_number', array_unique([$number, Str::upper($number)]))
            ->first();

        if ($registration === null || ! $this->accessCodes->verify($registration, $code)) {
            $this->accessCodes->logFailedAttempt($number);

            return back()
                ->withInput(['registration_number' => $request->input('registration_number')])
                ->withErrors(['kredensial' => 'Nomor pendaftaran atau kode akses tidak sesuai.']);
        }

        $request->session()->put(EnsureRegistrationAccess::SESSION_KEY, $registration->id);

        return redirect()->route('ppdb.status.show');
    }

    /**
     * Halaman status pendaftaran.
     */
    public function show(): View
    {
        $registration = request()->attributes->get('registration');

        $documents = $this->documents->latestDocuments($registration);
        $missing = $this->documents->missingRequirements($registration);

        return view('public.ppdb.status', [
            'registration' => $registration,
            'documents' => $documents,
            'missingRequirements' => $missing,
        ]);
    }
}
