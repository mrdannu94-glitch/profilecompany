<?php

namespace App\Http\Controllers\Public;

use App\Enums\PpdbPeriodStatus;
use App\Exceptions\Ppdb\PaymentException;
use App\Exceptions\Ppdb\RegistrationException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRegistrationAccess;
use App\Http\Requests\RegistrationRequest;
use App\Models\PpdbPeriod;
use App\Models\Program;
use App\Services\Ppdb\PaymentService;
use App\Services\Ppdb\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Formulir pendaftaran calon siswa.
 */
class RegistrationController extends Controller
{
    public function __construct(
        protected readonly RegistrationService $registrations,
        protected readonly PaymentService $payments,
    ) {}

    /**
     * Tampilkan formulir pendaftaran.
     */
    public function create(): View|RedirectResponse
    {
        $period = $this->openPeriod();

        if ($period === null) {
            return redirect()
                ->route('ppdb.info')
                ->withErrors(['periode' => 'Pendaftaran sedang tidak dibuka. Silakan cek informasi PPDB.']);
        }

        $units = $period->schoolUnits()->orderBy('level')->get();

        $programs = $units->isEmpty()
            ? collect()
            : Program::query()
                ->whereIn('school_unit_id', $units->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->groupBy('school_unit_id');

        return view('public.ppdb.register', [
            'period' => $period,
            'units' => $units,
            'programs' => $programs instanceof Collection ? $programs : collect(),
        ]);
    }

    /**
     * Simpan pendaftaran dan terbitkan nomor pendaftaran serta kode akses.
     */
    public function store(RegistrationRequest $request): RedirectResponse|View
    {
        try {
            $result = $this->registrations->submit($request->registrationData());
        } catch (RegistrationException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        $registration = $result->registration;

        $request->session()->put(EnsureRegistrationAccess::SESSION_KEY, $registration->id);

        $payment = null;
        $instruction = null;

        try {
            $payment = $this->payments->createAttempt($registration);
            $instruction = $this->payments->provider($payment->provider)
                ->buildInstruction($payment, $registration);
        } catch (PaymentException) {
            // Nominal biaya belum ditetapkan, sehingga belum ada percobaan
            // pembayaran yang dibuat. Instructions diberikan saat periode
            // dikonfigurasi.
        }

        return view('public.ppdb.registered', [
            'registration' => $registration,
            'accessCode' => $result->accessCode,
            'payment' => $payment,
            'instruction' => $instruction,
        ]);
    }

    /**
     * Periode yang sedang menerima pendaftaran.
     */
    protected function openPeriod(): ?PpdbPeriod
    {
        return PpdbPeriod::query()
            ->where('status', PpdbPeriodStatus::Open)
            ->orderByDesc('registration_start')
            ->first();
    }
}
