@extends('layouts.app')

@section('title', $period->exists ? 'Ubah Periode PPDB' : 'Tambah Periode PPDB')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div>
            <a class="text-sm text-ink-500 hover:text-ink-700" href="{{ route('admin.ppdb.periods.index') }}">&larr; Kembali ke daftar periode</a>
            <h1 class="mt-2 text-2xl font-semibold text-ink-900">
                {{ $period->exists ? 'Ubah Periode PPDB' : 'Tambah Periode PPDB' }}
            </h1>
        </div>

        @if ($errors->any())
            <div class="rounded-control border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="space-y-6" method="POST"
            action="{{ $period->exists ? route('admin.ppdb.periods.update', $period) : route('admin.ppdb.periods.store') }}">
            @csrf
            @if ($period->exists)
                @method('PUT')
            @endif

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Informasi periode</legend>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="code">Kode periode</label>
                        <input class="field-input" id="code" name="code" type="text" required maxlength="50"
                            value="{{ old('code', $period->code) }}">
                    </div>

                    <div>
                        <label class="field-label" for="name">Nama periode</label>
                        <input class="field-input" id="name" name="name" type="text" required maxlength="120"
                            value="{{ old('name', $period->name) }}">
                    </div>

                    <div>
                        <label class="field-label" for="registration_start">Pendaftaran dibuka</label>
                        <input class="field-input" id="registration_start" name="registration_start" type="date" required
                            value="{{ old('registration_start', $period->registration_start?->format('Y-m-d')) }}">
                    </div>

                    <div>
                        <label class="field-label" for="registration_end">Pendaftaran ditutup</label>
                        <input class="field-input" id="registration_end" name="registration_end" type="date" required
                            value="{{ old('registration_end', $period->registration_end?->format('Y-m-d')) }}">
                    </div>

                    <div>
                        <label class="field-label" for="announcement_date">Tanggal pengumuman</label>
                        <input class="field-input" id="announcement_date" name="announcement_date" type="date"
                            value="{{ old('announcement_date', $period->announcement_date?->format('Y-m-d')) }}">
                    </div>

                    <div>
                        <label class="field-label" for="status">Status</label>
                        <select class="field-input" id="status" name="status" required>
                            @foreach (\App\Enums\PpdbPeriodStatus::cases() as $status)
                                <option value="{{ $status->value }}"
                                    @selected(old('status', $period->status?->value) === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Biaya pendaftaran</legend>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="registration_fee">Nominal</label>
                        <input class="field-input" id="registration_fee" name="registration_fee" type="number" min="0" step="1000"
                            value="{{ old('registration_fee', $period->registration_fee) }}">
                        <p class="mt-1 text-xs text-ink-500">Kosongkan bila nominal belum ditetapkan sekolah.</p>
                    </div>

                    <div>
                        <label class="field-label" for="currency">Mata uang</label>
                        <input class="field-input" id="currency" name="currency" type="text" maxlength="10"
                            value="{{ old('currency', $period->currency ?? 'IDR') }}">
                    </div>
                </div>
            </fieldset>

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Unit yang dilayani</legend>

                <div class="mt-4 space-y-2">
                    @foreach ($units as $unit)
                        <label class="flex items-center gap-3 text-sm text-ink-700">
                            <input type="checkbox" name="school_units[]" value="{{ $unit->id }}"
                                @checked($selectedUnits->contains($unit->id))>
                            <span>{{ $unit->name }} <span class="text-xs text-ink-400">({{ $unit->level }})</span></span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-3 text-xs text-ink-500">
                    Hanya unit yang dicentang yang menerima pendaftaran pada periode ini.
                </p>
            </fieldset>

            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Simpan Periode</button>
                <a class="btn-secondary" href="{{ route('admin.ppdb.periods.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
