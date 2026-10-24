<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SelectionDecisionStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\Ppdb\SelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penetapan hasil seleksi oleh pemegang permission khusus.
 */
class PpdbSelectionController extends Controller
{
    public function __construct(
        protected readonly SelectionService $selections,
    ) {}

    /**
     * Catat keputusan hasil seleksi.
     */
    public function store(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorize('decide', $registration);

        $status = SelectionDecisionStatus::tryFrom($request->string('decision_status')->toString());

        if ($status === null || $status === SelectionDecisionStatus::Pending) {
            return back()->withErrors(['decision_status' => 'Status hasil seleksi tidak dikenali.']);
        }

        $this->selections->decide(
            $registration,
            $status,
            $request->user(),
            $request->string('note')->trim()->toString() ?: null
        );

        return back()->with('status', 'Hasil seleksi telah ditetapkan.');
    }
}
