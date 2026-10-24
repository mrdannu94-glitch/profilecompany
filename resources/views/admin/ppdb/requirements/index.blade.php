@extends('layouts.app')

@section('title', 'Persyaratan Dokumen PPDB')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Persyaratan Dokumen</h1>
            <p class="mt-1 text-sm text-ink-600">
                Daftar dokumen wajib per periode, jenjang, dan program. Belum ada daftar bawaan karena ketentuan
                sekolah belum ditetapkan.
            </p>
        </div>

        <form class="card p-4" method="GET" action="{{ route('admin.ppdb.requirements.index') }}">
            <div class="max-w-xs">
                <label class="field-label" for="period">Periode</label>
                <select class="field-input" id="period" name="period" onchange="this.form.submit()">
                    <option value="">Semua periode</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected($selectedPeriod?->id === $period->id)>
                            {{ $period->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card overflow-hidden">
                <h2 class="border-b border-ink-200 px-5 py-4 text-base font-semibold text-ink-900">Daftar persyaratan</h2>

                @if ($requirements->isEmpty())
                    <p class="px-5 py-8 text-sm text-ink-600">Belum ada persyaratan yang terdaftar.</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($requirements as $requirement)
                            <li class="px-5 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-ink-900">
                                            {{ $requirement->name }}
                                            <span class="font-mono text-xs font-normal text-ink-500">
                                                {{ $requirement->requirement_code }}
                                            </span>
                                        </p>
                                        <p class="mt-1 text-xs text-ink-600">
                                            {{ $requirement->schoolUnit?->name }} ·
                                            {{ $requirement->program?->name ?? 'Semua program' }} ·
                                            {{ $requirement->is_required ? 'Wajib' : 'Opsional' }}
                                        </p>
                                        <p class="mt-1 text-xs text-ink-500">
                                            {{ implode(', ', $requirement->accepted_mime_types ?? []) ?: 'Format belum ditentukan' }}
                                            · maks {{ round(($requirement->max_file_size ?? 0) / 1024) }} KB
                                        </p>
                                        @if (! $requirement->is_active)
                                            <p class="mt-1 text-xs text-ink-400">Nonaktif</p>
                                        @endif
                                    </div>

                                    <form method="POST"
                                        action="{{ route('admin.ppdb.requirements.destroy', $requirement) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-medium text-danger-700 hover:underline" type="submit">
                                            Nonaktifkan
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Tambah persyaratan</h2>

                @if ($errors->any())
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-danger-700">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                @endif

                <form class="mt-4 space-y-4" method="POST" action="{{ route('admin.ppdb.requirements.store') }}">
                    @csrf

                    <div>
                        <label class="field-label" for="ppdb_period_id">Periode</label>
                        <select class="field-input" id="ppdb_period_id" name="ppdb_period_id" required>
                            <option value="">Pilih periode</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((int) old('ppdb_period_id') === $period->id)>
                                    {{ $period->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="school_unit_id">Jenjang</label>
                        <select class="field-input" id="school_unit_id" name="school_unit_id" required>
                            <option value="">Pilih jenjang</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((int) old('school_unit_id') === $unit->id)>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="program_id">Program (opsional)</label>
                        <select class="field-input" id="program_id" name="program_id">
                            <option value="">Semua program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((int) old('program_id') === $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="requirement_code">Kode</label>
                            <input class="field-input font-mono" id="requirement_code" name="requirement_code" type="text"
                                required maxlength="50" value="{{ old('requirement_code') }}">
                        </div>

                        <div>
                            <label class="field-label" for="name">Nama dokumen</label>
                            <input class="field-input" id="name" name="name" type="text" required maxlength="120"
                                value="{{ old('name') }}">
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="description">Keterangan</label>
                        <textarea class="field-input" id="description" name="description" rows="2" maxlength="1000">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="max_file_size">Maksimum ukuran (KB)</label>
                            <input class="field-input" id="max_file_size" name="max_file_size" type="number" min="1"
                                max="20480" required value="{{ old('max_file_size', 2048) }}">
                        </div>

                        <div>
                            <label class="field-label" for="sort_order">Urutan</label>
                            <input class="field-input" id="sort_order" name="sort_order" type="number" min="0" max="999"
                                value="{{ old('sort_order', 0) }}">
                        </div>
                    </div>

                    <div>
                        <span class="field-label">Format berkas yang diterima</span>
                        <div class="mt-1 space-y-2">
                            @foreach (['application/pdf' => 'PDF', 'image/jpeg' => 'JPEG', 'image/png' => 'PNG'] as $mime => $label)
                                <label class="flex items-center gap-2 text-sm text-ink-700">
                                    <input type="checkbox" name="accepted_mime_types[]" value="{{ $mime }}"
                                        @checked(in_array($mime, old('accepted_mime_types', ['application/pdf', 'image/jpeg'])))>
                                    <span>{{ $label }} ({{ $mime }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="is_required" value="1" checked>
                        <span>Wajib diunggah</span>
                    </label>

                    <button class="btn-primary" type="submit">Tambah Persyaratan</button>
                </form>
            </div>
        </div>
    </div>
@endsection
