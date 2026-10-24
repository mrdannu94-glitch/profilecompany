@extends('layouts.app')

@section('title', 'Detail Pendaftaran')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a class="text-sm text-ink-500 hover:text-ink-700" href="{{ route('admin.ppdb.registrations.index') }}">&larr; Kembali ke daftar</a>
                <h1 class="mt-2 text-2xl font-semibold text-ink-900">{{ $registration->candidate_name }}</h1>
                <p class="mt-1 font-mono text-sm text-ink-600">{{ $registration->registration_number }}</p>
            </div>

            <x-status-badge :status="$registration->status" />
        </div>

        @if (session('access_code'))
            <div class="rounded-control border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700" role="alert">
                <p class="font-semibold">Kode akses baru (ditampilkan sekali)</p>
                <p class="mt-1 font-mono text-lg tracking-widest">{{ session('access_code') }}</p>
                <p class="mt-1 text-xs">Kode lama sudah dibatalkan dan tidak dapat dibuka kembali.</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Data pendaftaran</h2>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Jenjang</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->schoolUnit?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Program</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->program?->name ?? 'Tidak ada' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Periode</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->period?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Tanggal lahir</dt>
                        <dd class="text-right font-medium text-ink-800">
                            {{ $registration->date_of_birth?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Orang tua/wali</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->parent_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Kontak</dt>
                        <dd class="text-right font-medium text-ink-800">{{ $registration->parent_phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Persetujuan data</dt>
                        <dd class="text-right font-medium text-ink-800">
                            {{ $registration->data_consent_at?->format('d M Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Pemulihan kode akses</h2>
                <p class="mt-2 text-sm text-ink-600">
                    Kode lama dibatalkan dan kode baru diterbitkan. Nilai kode lama tidak pernah ditampilkan kembali.
                </p>

                @can('resetAccessCode', $registration)
                    <form class="mt-4 space-y-3" method="POST"
                        action="{{ route('admin.ppdb.registrations.access-code', $registration) }}">
                        @csrf
                        <div>
                            <label class="field-label" for="note">Catatan pemulihan</label>
                            <input class="field-input" id="note" name="note" type="text" maxlength="200"
                                placeholder="Contoh: verifikasi identitas via CS">
                        </div>
                        <button class="btn-secondary" type="submit">Terbitkan Kode Baru</button>
                    </form>
                @else
                    <p class="mt-4 rounded-control border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-600">
                        Akun Anda tidak memiliki izin pemulihan kode akses.
                    </p>
                @endcan
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-ink-900">Dokumen</h2>

            @if ($registration->documents->isEmpty())
                <p class="mt-3 text-sm text-ink-600">Belum ada dokumen yang diunggah.</p>
            @else
                <ul class="mt-3 divide-y divide-ink-100">
                    @foreach ($registration->documents as $document)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-medium text-ink-900">
                                    {{ $document->requirement?->name }}
                                    <span class="text-xs text-ink-400">v{{ $document->version_number }}</span>
                                </p>
                                <p class="text-xs text-ink-500">
                                    Diperiksa {{ $document->reviewed_at?->format('d M Y H:i') ?? 'belum' }}
                                    @if ($document->reviewer) oleh {{ $document->reviewer->name }} @endif
                                </p>
                                @if ($document->review_note)
                                    <p class="mt-1 text-xs text-ink-600">Catatan: {{ $document->review_note }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <x-status-badge :status="$document->status" />
                                <a class="text-xs font-medium text-primary-700 hover:underline"
                                    href="{{ route('admin.ppdb.documents.download', $document) }}">Unduh</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($missingRequirements->isNotEmpty())
                <p class="mt-4 rounded-control border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700">
                    Persyaratan wajib yang belum memiliki versi disetujui:
                    {{ $missingRequirements->pluck('name')->join(', ') }}.
                </p>
            @endif
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-ink-900">Pembayaran</h2>

            @if ($registration->payments->isEmpty())
                <p class="mt-3 text-sm text-ink-600">Belum ada percobaan pembayaran.</p>
            @else
                <ul class="mt-3 divide-y divide-ink-100">
                    @foreach ($registration->payments as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <div>
                                <p class="font-medium text-ink-900">
                                    Percobaan {{ $payment->attempt_number }} ·
                                    Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{ $payment->provider }}
                                    @if ($payment->payment_method) · {{ $payment->payment_method }} @endif
                                    @if ($payment->failure_reason) · {{ $payment->failure_reason }} @endif
                                </p>
                            </div>
                            <x-status-badge :status="$payment->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @can('decide', $registration)
            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Hasil seleksi</h2>

                @if ($registration->currentDecision)
                    <p class="mt-2 text-sm text-ink-600">
                        Keputusan berlaku saat ini: {{ $registration->currentDecision->decision_status->label() }}
                        · {{ $registration->currentDecision->decided_at?->format('d M Y H:i') }}
                    </p>
                @endif

                <form class="mt-4 grid gap-3 sm:grid-cols-2" method="POST"
                    action="{{ route('admin.ppdb.selection.store', $registration) }}">
                    @csrf

                    <div>
                        <label class="field-label" for="decision_status">Status hasil</label>
                        <select class="field-input" id="decision_status" name="decision_status" required>
                            <option value="">Pilih status</option>
                            <option value="accepted">Diterima</option>
                            <option value="not_accepted">Tidak diterima</option>
                            <option value="waitlisted">Daftar tunggu</option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="note">Catatan</label>
                        <input class="field-input" id="note" name="note" type="text" maxlength="200">
                    </div>

                    <div class="sm:col-span-2">
                        <button class="btn-primary" type="submit">Tetapkan Hasil</button>
                    </div>
                </form>
            </div>
        @endcan
    </div>
@endsection
