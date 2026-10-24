<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PpdbPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\SchoolUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan periode penerimaan PPDB.
 */
class PpdbPeriodController extends Controller
{
    public function index(): View
    {
        $periods = PpdbPeriod::query()
            ->with('schoolUnits')
            ->withCount('registrations')
            ->orderByDesc('registration_start')
            ->get();

        return view('admin.ppdb.periods.index', [
            'periods' => $periods,
            'statuses' => PpdbPeriodStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ppdb.periods.form', [
            'period' => new PpdbPeriod,
            'units' => SchoolUnit::query()->orderBy('level')->get(),
            'selectedUnits' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $period = new PpdbPeriod;
        $period->forceFill($data);
        $period->created_by = $request->user()->id;
        $period->save();

        $this->syncUnits($period, $request->input('school_units', []));

        return redirect()
            ->route('admin.ppdb.periods.index')
            ->with('status', 'Periode PPDB dibuat.');
    }

    public function edit(PpdbPeriod $period): View
    {
        return view('admin.ppdb.periods.form', [
            'period' => $period,
            'units' => SchoolUnit::query()->orderBy('level')->get(),
            'selectedUnits' => $period->schoolUnits->pluck('id'),
        ]);
    }

    public function update(Request $request, PpdbPeriod $period): RedirectResponse
    {
        $data = $this->validated($request);

        $period->fill($data);
        $period->save();

        $this->syncUnits($period, $request->input('school_units', []));

        return redirect()
            ->route('admin.ppdb.periods.index')
            ->with('status', 'Periode PPDB diperbarui.');
    }

    /**
     * Validasi input periode.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:ppdb_periods,code,'.$request->route('period')?->id],
            'name' => ['required', 'string', 'max:120'],
            'registration_start' => ['required', 'date'],
            'registration_end' => ['required', 'date', 'after_or_equal:registration_start'],
            'announcement_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_column(PpdbPeriodStatus::cases(), 'value'))],
            'registration_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
        ]);

        $validated['registration_fee'] = $validated['registration_fee'] ?? null;

        return $validated;
    }

    /**
     * Sinkronkan unit yang dicakup periode.
     *
     * @param  array<int, mixed>  $unitIds
     */
    protected function syncUnits(PpdbPeriod $period, array $unitIds): void
    {
        $ids = collect($unitIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $period->schoolUnits()->sync($ids);
    }
}
