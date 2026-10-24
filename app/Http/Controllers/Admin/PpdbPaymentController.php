<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Exceptions\Ppdb\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Ppdb\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verifikasi pembayaran pendaftaran secara manual.
 */
class PpdbPaymentController extends Controller
{
    public function __construct(
        protected readonly PaymentService $payments,
    ) {}

    /**
     * Daftar percobaan pembayaran.
     */
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['registration.schoolUnit'])
            ->when($request->filled('status'), fn ($query) => $query->where(
                'status',
                $request->string('status')->toString()
            ))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim().'%';

                $query->whereHas('registration', function ($inner) use ($keyword): void {
                    $inner->where('registration_number', 'like', $keyword);
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ppdb.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    /**
     * Konfirmasi pembayaran manual.
     */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('verify', $payment);

        $note = $request->string('note')->trim()->toString();

        try {
            $this->payments->confirmManually($payment, $request->user(), $note ?: null);
        } catch (PaymentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Pembayaran dikonfirmasi.');
    }

    /**
     * Tandai percobaan pembayaran sebagai gagal.
     */
    public function fail(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('verify', $payment);

        $reason = $request->string('note')->trim()->toString();

        if ($reason === '') {
            return back()->withErrors(['note' => 'Alasan kegagalan wajib diisi.']);
        }

        $this->payments->failManually($payment, $request->user(), $reason);

        return back()->with('status', 'Percobaan pembayaran ditandai gagal.');
    }
}
