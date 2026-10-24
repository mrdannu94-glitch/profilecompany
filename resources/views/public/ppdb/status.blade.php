@extends('layouts.public')

@section('title', 'Status Pendaftaran — '.config('app.name'))

@section('content')
    <section class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold tracking-wide text-primary-700 uppercase">Status Pendaftaran</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-900">{{ $registration->candidate_name }}</h1>
                <p class="mt-1 font-mono text-sm text-ink-600">{{ $registration->registration_number }}</p>
            </div>

            <x-status-badge :status="$registration->status" />
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Ringkasan</h2>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Jenjang</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->schoolUnit?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Program</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->program?->name ?? 'Tidak ada program' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Periode</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->period?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Tanggal daftar</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->submitted_at?->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Pembayaran</h2>

                @if ($registration->activePayment)
                    <div class="mt-4 flex items-center justify-between gap-4">
                        <p class="text-sm text-ink-600">
                            Rp {{ number_format((float) $registration->activePayment->amount, 0, ',', '.') }}
                            <span class="text-xs text-ink-400">({{ $registration->activePayment->currency }})</span>
                        </p>
                        <x-status-badge :status="$registration->activePayment->status" />
                    </div>
                @else
                    <p class="mt-4 text-sm text-ink-600">
                        Belum ada percobaan pembayaran. Nominal resmi belum ditetapkan sekolah.
                    </p>
                @endif
            </div>
        </div>

        <div class="card mt-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-ink-900">Dokumen</h2>
                <p class="text-xs text-ink-500">Status dokumen diperiksa terpisah dari status pembayaran.</p>
            </div>

            @if ($documents->isEmpty() && $missingRequirements->isEmpty())
                <p class="mt-4 rounded-control border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-600">
                    Belum ada persyaratan dokumen yang berlaku untuk pendaftaran ini.
                </p>
            @else
                <ul class="mt-4 divide-y divide-ink-100">
                    @foreach ($documents as $document)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-medium text-ink-900">{{ $document->requirement?->name }}</p>
                                <p class="text-xs text-ink-500">
                                    Versi {{ $document->version_number }} ·
                                    diunggah {{ $document->uploaded_at?->format('d M Y H:i') }}
                                </p>
                                @if ($document->review_note)
                                    <p class="mt-1 text-xs text-ink-600">Catatan petugas: {{ $document->review_note }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <x-status-badge :status="$document->status" />
                                <a class="text-xs font-medium text-primary-700 hover:underline"
                                    href="{{ route('ppdb.documents.download', $document) }}">Unduh</a>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($missingRequirements->isNotEmpty())
                    <div class="mt-6 border-t border-ink-200 pt-6">
                        <h3 class="text-sm font-semibold text-ink-900">Dokumen yang perlu dikirim</h3>
                        <p class="mt-1 text-xs text-ink-500">
                            Unggah satu berkas per persyaratan. Berkas yang dikembalikan petugas dapat diunggah ulang.
                        </p>

                        <ul class="mt-4 space-y-4">
                            @foreach ($missingRequirements as $requirement)
                                <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                                    <p class="text-sm font-medium text-ink-900">{{ $requirement->name }}</p>
                                    @if ($requirement->description)
                                        <p class="mt-1 text-xs text-ink-600">{{ $requirement->description }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-ink-500">
                                        Format: {{ implode(', ', $requirement->accepted_mime_types ?? ['belum ditentukan']) }}
                                        · Maksimum {{ round(($requirement->max_file_size ?? 0) / 1024) }} KB
                                    </p>

                                    <form class="mt-3 flex flex-wrap items-center gap-3" method="POST"
                                        action="{{ route('ppdb.documents.store') }}" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="ppdb_requirement_id" value="{{ $requirement->id }}">

                                        <label class="sr-only" for="document-{{ $requirement->id }}">
                                            Berkas {{ $requirement->name }}
                                        </label>
                                        <input class="field-input max-w-xs" id="document-{{ $requirement->id }}" type="file"
                                            name="document" required>

                                        <button class="btn-secondary" type="submit">Unggah</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>

        @if ($registration->currentDecision)
            <div class="card mt-6 p-6">
                <h2 class="text-base font-semibold text-ink-900">Hasil Seleksi</h2>
                <div class="mt-3 flex items-center gap-3">
                    <x-status-badge :status="$registration->currentDecision->decision_status" />
                    <span class="text-sm text-ink-600">
                        ditetapkan {{ $registration->currentDecision->decided_at?->format('d M Y H:i') }}
                    </span>
                </div>
                @if ($registration->currentDecision->decision_note)
                    <p class="mt-2 text-sm text-ink-600">{{ $registration->currentDecision->decision_note }}</p>
                @endif
            </div>
        @endif

        <div class="mt-8 flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route('ppdb.status') }}">Ganti pendaftaran</a>
            <a class="btn-secondary" href="{{ route('home') }}">Kembali ke beranda</a>
        </div>
    </section>
@endsection
