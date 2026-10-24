@extends('layouts.app')

@section('title', 'Review Konten')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ $item->typeLabel() }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Review Konten</h1>
            </div>

            <a class="btn-secondary" href="{{ route('admin.cms.index') }}">Kembali</a>
        </div>

        @if (! $revision)
            <p class="card p-6 text-sm text-ink-600">Konten ini belum memiliki revisi.</p>
        @else
            <article class="card p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <x-status-badge :status="$revision->workflow_status" />
                    <p class="text-sm text-ink-600">Revisi {{ $revision->revision_number }}</p>
                </div>

                <h2 class="mt-4 text-2xl font-semibold text-ink-900">{{ $revision->title }}</h2>

                <p class="mt-1 text-sm text-ink-500">
                    Penulis: {{ $revision->author?->name ?? 'Tidak diketahui' }}
                    @if ($revision->submitted_at)
                        &middot; diajukan {{ $revision->submitted_at->format('j F Y H:i') }}
                    @endif
                </p>

                @if (filled($revision->excerpt))
                    <p class="mt-4 text-base leading-relaxed text-ink-600">{{ $revision->excerpt }}</p>
                @endif

                <div class="mt-6 border-t border-ink-200 pt-6">
                    @include('public.partials.content-body', ['text' => $revision->body])
                </div>
            </article>

            @if ($revision->workflow_status === App\Enums\ContentStatus::Submitted)
                <form class="card space-y-4 p-6" method="POST" action="{{ route('admin.cms.decide', $item) }}">
                    @csrf

                    <h2 class="text-base font-semibold text-ink-900">Keputusan Review</h2>

                    <div>
                        <label class="field-label" for="action">Tindakan</label>
                        <select class="field-input" id="action" name="action" required>
                            <option value="approve" @selected(old('action') === 'approve')>Setujui untuk publikasi</option>
                            <option value="request_revision" @selected(old('action') === 'request_revision')>Kembalikan untuk revisi</option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="note">Catatan Review</label>
                        <textarea class="field-input" id="note" name="note" rows="4" maxlength="2000">{{ old('note') }}</textarea>
                        <p class="mt-1 text-xs text-ink-500">Wajib diisi bila konten dikembalikan untuk revisi.</p>
                    </div>

                    <button class="btn-primary" type="submit">Simpan Keputusan</button>
                </form>
            @endif

            @if ($revision->workflow_status === App\Enums\ContentStatus::Approved)
                @can('publish', $item)
                    <div class="card flex flex-wrap items-center justify-between gap-4 p-6">
                        <div>
                            <h2 class="text-base font-semibold text-ink-900">Siap Diterbitkan</h2>
                            <p class="mt-1 text-sm text-ink-600">Konten sudah disetujui dan dapat tampil di website publik.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.cms.publish', $item) }}">
                            @csrf
                            <button class="btn-primary" type="submit">Terbitkan</button>
                        </form>
                    </div>
                @endcan
            @endif

            @if ($revision->workflow_status === App\Enums\ContentStatus::Published)
                <p class="rounded-control border border-success-100 bg-success-50 px-4 py-3 text-sm text-success-700">
                    Revisi ini sudah terbit{{ $revision->published_at ? ' pada '.$revision->published_at->format('j F Y H:i') : '' }}.
                </p>
            @endif
        @endif

        @if ($item->revisions->isNotEmpty())
            <div class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">Riwayat Revisi</h2>

                <ul class="mt-4 space-y-4">
                    @foreach ($item->revisions->sortByDesc('revision_number') as $history)
                        <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-ink-900">Revisi {{ $history->revision_number }}</p>
                                <x-status-badge :status="$history->workflow_status" />
                            </div>

                            <p class="mt-1 text-sm text-ink-600">
                                {{ $history->title }}
                                @if ($history->reviewer)
                                    &middot; diperiksa {{ $history->reviewer->name }}
                                @endif
                                @if ($history->reviewed_at)
                                    &middot; {{ $history->reviewed_at->format('j F Y H:i') }}
                                @endif
                            </p>

                            @if ($history->review_note)
                                <p class="mt-2 text-sm text-ink-700">{{ $history->review_note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
