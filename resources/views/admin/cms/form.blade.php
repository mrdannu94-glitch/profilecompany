@extends('layouts.app')

@section('title', $item ? 'Sunting Konten' : 'Buat Konten')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">
                    {{ $item ? $item->typeLabel() : 'Konten Baru' }}
                </p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $item ? 'Sunting Konten' : 'Buat Konten' }}
                </h1>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($item)
                    <a class="btn-secondary" href="{{ route('admin.cms.index') }}">Kembali</a>
                @else
                    <a class="btn-secondary" href="{{ route('admin.cms.index') }}">Batal</a>
                @endif
            </div>
        </div>

        @if ($item && $revision)
            <div class="card flex flex-wrap items-center gap-3 p-4">
                <x-status-badge :status="$revision->workflow_status" />
                <p class="text-sm text-ink-600">Revisi {{ $revision->revision_number }}</p>

                @if ($revision->published_at)
                    <p class="text-sm text-ink-600">Terbit {{ $revision->published_at->format('j F Y H:i') }}</p>
                @endif

                @if ($revision->review_note)
                    <p class="w-full rounded-control border border-warning-100 bg-warning-50 px-3 py-2 text-sm text-warning-700">
                        Catatan reviewer: {{ $revision->review_note }}
                    </p>
                @endif
            </div>
        @endif

        <form
            class="card space-y-5 p-6"
            method="POST"
            action="{{ $item ? route('admin.cms.update', $item) : route('admin.cms.store') }}"
        >
            @csrf
            @if ($item)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="content_type">Jenis Konten</label>

                    @if ($item)
                        <p class="rounded-control border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-700">
                            {{ $item->typeLabel() }}
                        </p>
                    @else
                        <select class="field-input" id="content_type" name="content_type" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('content_type', $selectedType) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div>
                    <label class="field-label" for="title">Judul</label>
                    <input class="field-input" id="title" name="title" type="text" maxlength="180" required
                        value="{{ old('title', $revision?->title) }}">
                </div>
            </div>

            <div>
                <label class="field-label" for="slug">Slug URL (opsional)</label>
                <input class="field-input" id="slug" name="slug" type="text" maxlength="180"
                    value="{{ old('slug', $revision?->slug) }}">
                <p class="mt-1 text-xs text-ink-500">Kosongkan untuk dibuat otomatis dari judul.</p>
            </div>

            <div>
                <label class="field-label" for="excerpt">Ringkasan</label>
                <textarea class="field-input" id="excerpt" name="excerpt" rows="3" maxlength="500">{{ old('excerpt', $revision?->excerpt) }}</textarea>
                <p class="mt-1 text-xs text-ink-500">Tampil pada daftar berita, kartu, dan meta deskripsi.</p>
            </div>

            <div>
                <label class="field-label" for="body">Isi Konten</label>
                <textarea class="field-input" id="body" name="body" rows="14" maxlength="20000">{{ old('body', $revision?->body) }}</textarea>
                <p class="mt-1 text-xs text-ink-500">Teks biasa. Baris baru ditampilkan sebagai paragraf.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button class="btn-primary" type="submit">Simpan</button>
            </div>
        </form>

        @if ($item && $revision)
            <div class="card space-y-4 p-6">
                <h2 class="text-base font-semibold text-ink-900">Alur Publikasi</h2>

                <div class="flex flex-wrap gap-2">
                    @if ($revision->workflow_status->canBeSubmitted())
                        @can('submit', $item)
                            <form method="POST" action="{{ route('admin.cms.submit', $item) }}">
                                @csrf
                                <button class="btn-secondary" type="submit">Ajukan untuk Review</button>
                            </form>
                        @endcan
                    @endif

                    @can('review', $item)
                        @if ($revision->workflow_status === App\Enums\ContentStatus::Submitted)
                            <a class="btn-primary" href="{{ route('admin.cms.review', $item) }}">Periksa Konten</a>
                        @endif
                    @endcan

                    @can('publish', $item)
                        @if ($revision->workflow_status->canBePublished())
                            <form method="POST" action="{{ route('admin.cms.publish', $item) }}">
                                @csrf
                                <button class="btn-primary" type="submit">Terbitkan ke Website</button>
                            </form>
                        @endif
                    @endcan

                    @can('archive', $item)
                        <form method="POST" action="{{ route('admin.cms.archive', $item) }}" onsubmit="return confirm('Arsipkan konten ini? Konten tidak akan tampil di website publik.')">
                            @csrf
                            <button class="btn-danger" type="submit">Arsipkan</button>
                        </form>
                    @endcan
                </div>

                <p class="text-sm text-ink-600">
                    @switch($revision->workflow_status)
                        @case(App\Enums\ContentStatus::Draft)
                            Draf dapat disunting lalu diajukan untuk review.
                            @break
                        @case(App\Enums\ContentStatus::Submitted)
                            Konten menunggu keputusan reviewer.
                            @break
                        @case(App\Enums\ContentStatus::NeedsRevision)
                            Perbaiki konten sesuai catatan reviewer, lalu ajukan kembali.
                            @break
                        @case(App\Enums\ContentStatus::Approved)
                            Konten sudah disetujui dan siap diterbitkan oleh pemegang permission publikasi.
                            @break
                        @case(App\Enums\ContentStatus::Published)
                            Konten sudah tampil di website. Menyimpan perubahan akan membuat revisi draf baru; versi lama tetap tampil sampai revisi baru diterbitkan.
                            @break
                        @case(App\Enums\ContentStatus::Archived)
                            Konten diarsipkan dan tidak tampil di website publik.
                            @break
                    @endswitch
                </p>
            </div>
        @endif

        @if (! empty($reviews) && $reviews->isNotEmpty())
            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Riwayat Revisi</h2>

                <ul class="mt-4 space-y-3">
                    @foreach ($reviews as $history)
                        <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-ink-900">Revisi {{ $history->revision_number }}</p>
                                <x-status-badge :status="$history->workflow_status" />
                            </div>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $history->title }} &middot;
                                {{ $history->author?->name ?? 'Penulis tidak diketahui' }}
                                @if ($history->published_at)
                                    &middot; terbit {{ $history->published_at->format('j F Y H:i') }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
