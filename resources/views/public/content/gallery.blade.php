@extends('layouts.public')

@section('title', 'Galeri & Video - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Dokumentasi',
        'title' => 'Galeri & Video',
        'description' => 'Dokumentasi kegiatan sekolah yang sudah disetujui untuk dipublikasikan.',
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($entries->isEmpty())
            <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
                Belum ada dokumentasi yang diterbitkan.
            </p>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($entries as $entry)
                    @php
                        $publicMedia = $entry->media->filter(fn ($media) => $media->isPublic());
                    @endphp

                    <figure class="card overflow-hidden">
                        @if ($publicMedia->isNotEmpty())
                            @php
                                $media = $publicMedia->first();
                            @endphp
                            <img
                                class="h-48 w-full object-cover"
                                src="{{ \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->file_path) }}"
                                alt="{{ $entry->title }}"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-48 items-center justify-center bg-ink-100 text-sm text-ink-500">
                                Dokumentasi visual belum diunggah
                            </div>
                        @endif

                        <figcaption class="p-5">
                            <h2 class="text-base font-semibold text-ink-900">{{ $entry->title }}</h2>

                            @if (filled($entry->excerpt))
                                <p class="mt-1 text-sm text-ink-600">{{ $entry->excerpt }}</p>
                            @elseif (filled($entry->body))
                                <p class="mt-1 text-sm text-ink-600">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($entry->body), 160) }}
                                </p>
                            @endif

                            <p class="mt-2 text-xs text-ink-500">
                                {{ $entry->published_at?->format('j F Y') }}
                            </p>

                            @if (filled($entry->body))
                                <a class="mt-3 inline-block text-sm font-semibold text-primary-700 hover:text-primary-800" href="{{ route('galeri.show', $entry->slug) }}">
                                    Lihat detail
                                </a>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </section>
@endsection
