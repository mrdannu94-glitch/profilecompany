@extends('layouts.public')

@section('title', 'Formulir Pendaftaran PPDB — '.config('app.name'))

@section('content')
    <section class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold tracking-wide text-primary-700 uppercase">Pendaftaran PPDB</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-900">Formulir Pendaftaran</h1>
        <p class="mt-3 text-sm text-ink-600">
            Periode: <span class="font-medium text-ink-800">{{ $period->name }}</span>
            ({{ $period->registration_start?->format('d M Y') }} — {{ $period->registration_end?->format('d M Y') }})
        </p>

        @if ($errors->any())
            <div class="mt-6 rounded-control border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700" role="alert">
                <p class="font-semibold">Formulir belum dapat dikirim.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="mt-8 space-y-8" method="POST" action="{{ route('ppdb.register.store') }}">
            @csrf

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Pilihan jenjang dan program</legend>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="school_unit_id">Jenjang sekolah</label>
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
                        <label class="field-label" for="program_id">Program / jurusan</label>
                        <select class="field-input" id="program_id" name="program_id">
                            <option value="">Pilih program (jika tersedia)</option>
                            @foreach ($units as $unit)
                                @foreach ($programs->get($unit->id, collect()) as $program)
                                    <option value="{{ $program->id }}" @selected((int) old('program_id') === $program->id)>
                                        {{ $unit->name }} — {{ $program->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-500">
                            Pilih satu program saja. Bila jenjang Anda belum memiliki program, biarkan kosong.
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Data calon siswa</legend>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="candidate_name">Nama lengkap calon siswa</label>
                        <input class="field-input" id="candidate_name" name="candidate_name" type="text" required
                            value="{{ old('candidate_name') }}" autocomplete="name">
                    </div>

                    <div>
                        <label class="field-label" for="date_of_birth">Tanggal lahir</label>
                        <input class="field-input" id="date_of_birth" name="date_of_birth" type="date"
                            value="{{ old('date_of_birth') }}">
                    </div>

                    <div>
                        <label class="field-label" for="parent_name">Nama orang tua atau wali</label>
                        <input class="field-input" id="parent_name" name="parent_name" type="text"
                            value="{{ old('parent_name') }}">
                    </div>

                    <div>
                        <label class="field-label" for="parent_phone">Nomor WhatsApp orang tua/wali</label>
                        <input class="field-input" id="parent_phone" name="parent_phone" type="text" required
                            value="{{ old('parent_phone') }}" inputmode="tel" autocomplete="tel">
                    </div>

                    <div>
                        <label class="field-label" for="parent_email">Email orang tua/wali</label>
                        <input class="field-input" id="parent_email" name="parent_email" type="email"
                            value="{{ old('parent_email') }}" autocomplete="email">
                    </div>
                </div>
            </fieldset>

            <fieldset class="card p-6">
                <legend class="px-1 text-base font-semibold text-ink-900">Persetujuan pemrosesan data</legend>

                <p class="mt-3 text-sm text-ink-600">
                    Data yang diisi dipakai untuk proses penerimaan peserta didik baru dan pemeriksaan dokumen. Pernyataan
                    privasi final menunggu penetapan sekolah; versi yang tercatat saat ini adalah
                    <span class="font-mono text-xs">{{ config('dmr.privacy_notice_version') }}</span>.
                </p>

                <label class="mt-4 flex items-start gap-3 text-sm text-ink-700">
                    <input class="mt-1" type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                    <span>Saya menyatakan data yang diisi benar dan menyetujui data tersebut diproses untuk keperluan PPDB.</span>
                </label>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button class="btn-primary" type="submit">Kirim Pendaftaran</button>
                <a class="btn-secondary" href="{{ route('ppdb.info') }}">Kembali ke informasi PPDB</a>
            </div>
        </form>
    </section>
@endsection
