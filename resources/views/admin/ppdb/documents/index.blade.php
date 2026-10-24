@extends('layouts.app')

@section('title', 'Pemeriksaan Dokumen PPDB')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Pemeriksaan Dokumen</h1>
            <p class="mt-1 text-sm text-ink-600">
                Antrean berkas yang diunggah calon siswa. Berkas bersifat privat dan hanya dapat diunduh melalui
                halaman ini.
            </p>
        </div>

        <form class="card p-4" method="GET" action="{{ route('admin.ppdb.documents.index') }}">
            <div class="max-w-xs">
                <label class="field-label" for="status">Status</label>
                <select class="field-input" id="status" name="status">
                    <option value="queue" @selected(request('status', 'queue') === 'queue')>Menunggu pemeriksaan</option>
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 flex gap-3">
                <button class="btn-primary" type="submit">Terapkan</button>
                <a class="btn-secondary" href="{{ route('admin.ppdb.documents.index') }}">Reset</a>
            </div>
        </form>

        <div class="space-y-4">
            @forelse ($documents as $document)
                <article class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-ink-900">
                                {{ $document->requirement?->name ?? 'Persyaratan tidak dikenal' }}
                            </h2>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $document->registration?->candidate_name }}
                                <span class="font-mono text-xs text-ink-500">
                                    {{ $document->registration?->registration_number }}
                                </span>
                            </p>
                            <p class="mt-1 text-xs text-ink-500">
                                {{ $document->registration?->schoolUnit?->name }}
                                · Versi {{ $document->version_number }}
                                · diunggah {{ $document->uploaded_at?->format('d M Y H:i') }}
                            </p>
                            @if ($document->media)
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ $document->media->original_name }}
                                    ({{ $document->media->mime_type }},
                                    {{ round(($document->media->file_size ?? 0) / 1024) }} KB)
                                </p>
                            @endif
                        </div>

                        <x-status-badge :status="$document->status" />
                    </div>

                    @if ($document->review_note)
                        <p class="mt-3 rounded-control border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-700">
                            Catatan sebelumnya: {{ $document->review_note }}
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a class="btn-secondary"
                            href="{{ route('admin.ppdb.documents.download', $document) }}">Unduh Berkas</a>
                    </div>

                    <form class="mt-4 grid gap-3 sm:grid-cols-2" method="POST"
                        action="{{ route('admin.ppdb.documents.review', $document) }}">
                        @csrf

                        <div class="sm:col-span-2">
                            <label class="field-label" for="note-{{ $document->id }}">Catatan untuk calon siswa</label>
                            <input class="field-input" id="note-{{ $document->id }}" name="note" type="text" maxlength="500"
                                placeholder="Wajib diisi saat mengembalikan atau menolak dokumen">
                        </div>

                        <div class="flex flex-wrap gap-2 sm:col-span-2">
                            <button class="btn-primary" type="submit" name="action" value="approve">Setujui</button>
                            <button class="btn-secondary" type="submit" name="action" value="request_revision">
                                Kembalikan untuk Diperbaiki
                            </button>
                            <button class="btn-danger" type="submit" name="action" value="reject">Tolak</button>
                        </div>
                    </form>
                </article>
            @empty
                <p class="card px-6 py-8 text-sm text-ink-600">Tidak ada dokumen pada filter ini.</p>
            @endforelse
        </div>

        <div>{{ $documents->links() }}</div>
    </div>
@endsection
