@extends('layouts.app')

@section('title', 'Konten CMS')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Konten CMS</h1>
                <p class="mt-1 text-sm text-ink-600">
                    Kelola konten website dengan alur draf, review, lalu publikasi.
                </p>
            </div>

            @can('create', App\Models\ContentItem::class)
                <a class="btn-primary" href="{{ route('admin.cms.create') }}">Buat Konten</a>
            @endcan
        </div>

        <form class="card flex flex-wrap items-end gap-3 p-4" method="GET" action="{{ route('admin.cms.index') }}">
            <div>
                <label class="field-label" for="type">Jenis Konten</label>
                <select class="field-input" id="type" name="type">
                    <option value="">Semua jenis</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="status">Status</label>
                <select class="field-input" id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <button class="btn-secondary" type="submit">Terapkan</button>
        </form>

        @if ($items->isEmpty())
            <p class="card p-6 text-sm text-ink-600">Belum ada konten yang cocok dengan filter ini.</p>
        @else
            <div class="card overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-200 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold tracking-wide text-ink-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Judul</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Diperbarui</th>
                            <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-200 bg-white">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-ink-900">{{ $item->latestRevision?->title ?? '(tanpa judul)' }}</p>
                                    @if ($item->latestRevision)
                                        <p class="text-xs text-ink-500">Revisi {{ $item->latestRevision->revision_number }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->typeLabel() }}</td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$item->status" />
                                </td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->updated_at->diffForHumans() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('update', $item)
                                            <a class="text-sm font-semibold text-primary-700 hover:text-primary-800" href="{{ route('admin.cms.edit', $item) }}">Sunting</a>
                                        @endcan

                                        @can('review', $item)
                                            @if ($item->latestRevision?->workflow_status === App\Enums\ContentStatus::Submitted)
                                                <a class="text-sm font-semibold text-primary-700 hover:text-primary-800" href="{{ route('admin.cms.review', $item) }}">Review</a>
                                            @endif
                                        @endcan

                                        @if ($item->publishedRevision)
                                            @php
                                                $publicRoute = match ($item->content_type) {
                                                    'home' => 'home',
                                                    'profil' => 'profil',
                                                    'visi_misi' => 'visi-misi',
                                                    'sambutan' => 'sambutan',
                                                    'guru_staff' => 'guru-staff',
                                                    'program' => 'program',
                                                    'fasilitas' => 'fasilitas',
                                                    'ekstrakurikuler' => 'ekstrakurikuler',
                                                    'prestasi' => 'prestasi',
                                                    'berita', 'pengumuman', 'agenda', 'galeri' => null,
                                                    'faq' => 'faq',
                                                    'kontak' => 'kontak',
                                                    default => 'home',
                                                };
                                            @endphp

                                            @if ($publicRoute)
                                                <a class="text-sm font-semibold text-ink-600 hover:text-ink-900" href="{{ route($publicRoute) }}" target="_blank" rel="noopener">Lihat</a>
                                            @else
                                                <a class="text-sm font-semibold text-ink-600 hover:text-ink-900" href="{{ route($item->content_type.'.show', $item->publishedRevision->slug) }}" target="_blank" rel="noopener">Lihat</a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $items->links() }}</div>
        @endif
    </div>
@endsection
