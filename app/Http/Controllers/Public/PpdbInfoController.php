<?php

namespace App\Http\Controllers\Public;

use App\Enums\PpdbPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman informasi PPDB untuk pengunjung umum.
 */
class PpdbInfoController extends Controller
{
    public function show(): View
    {
        $period = PpdbPeriod::query()
            ->where('status', PpdbPeriodStatus::Open)
            ->orderByDesc('registration_start')
            ->first();

        $units = $period instanceof PpdbPeriod
            ? $period->schoolUnits()->orderBy('level')->get()
            : collect();

        $programs = $units->isEmpty()
            ? collect()
            : Program::query()
                ->whereIn('school_unit_id', $units->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->groupBy('school_unit_id');

        $requirements = $period instanceof PpdbPeriod
            ? $period->requirements()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('public.ppdb.info', [
            'period' => $period,
            'units' => $units,
            'programs' => $programs instanceof Collection ? $programs : collect(),
            'requirements' => $requirements,
        ]);
    }
}
