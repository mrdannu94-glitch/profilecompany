<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\PpdbRequirement;
use App\Models\Program;
use App\Models\SchoolUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan persyaratan dokumen PPDB.
 *
 * Daftar dokumen wajib belum ditetapkan sekolah, sehingga tidak ada data
 * bawaan. Admin yang menentukan daftar ini.
 */
class PpdbRequirementController extends Controller
{
    public function index(Request $request): View
    {
        $period = PpdbPeriod::query()->find($request->integer('period'));

        $requirements = PpdbRequirement::query()
            ->with(['schoolUnit', 'program'])
            ->when($period !== null, fn ($query) => $query->where('ppdb_period_id', $period->id))
            ->orderBy('school_unit_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.ppdb.requirements.index', [
            'requirements' => $requirements,
            'periods' => PpdbPeriod::query()->orderByDesc('registration_start')->get(),
            'selectedPeriod' => $period,
            'units' => SchoolUnit::query()->orderBy('level')->get(),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $requirement = new PpdbRequirement;
        $requirement->fill($data);
        $requirement->accepted_mime_types = $this->mimeTypes($request->input('accepted_mime_types', []));
        $requirement->save();

        return back()->with('status', 'Persyaratan dokumen ditambahkan.');
    }

    public function update(Request $request, PpdbRequirement $requirement): RedirectResponse
    {
        $data = $this->validated($request, $requirement);

        $requirement->fill($data);
        $requirement->accepted_mime_types = $this->mimeTypes($request->input('accepted_mime_types', []));
        $requirement->save();

        return back()->with('status', 'Persyaratan dokumen diperbarui.');
    }

    /**
     * Nonaktifkan persyaratan tanpa menghapus riwayat.
     */
    public function destroy(PpdbRequirement $requirement): RedirectResponse
    {
        $requirement->forceFill(['is_active' => false])->save();

        return back()->with('status', 'Persyaratan dokumen dinonaktifkan.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?PpdbRequirement $requirement = null): array
    {
        $periodId = $requirement?->ppdb_period_id ?? $request->integer('ppdb_period_id');

        $unique = Rule::unique('ppdb_requirements', 'requirement_code')
            ->where('ppdb_period_id', $periodId)
            ->where('school_unit_id', $request->integer('school_unit_id'));

        if ($requirement !== null) {
            $unique->ignore($requirement->id);
        }

        return $request->validate([
            'ppdb_period_id' => ['required', 'integer', 'exists:ppdb_periods,id'],
            'school_unit_id' => ['required', 'integer', 'exists:school_units,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'requirement_code' => ['required', 'string', 'max:50', $unique],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_file_size' => ['required', 'integer', 'min:1024', 'max:20480'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Daftar tipe MIME yang diizinkan.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function mimeTypes(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
