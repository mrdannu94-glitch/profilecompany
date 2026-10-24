@extends('layouts.public')

@section('title', $typeLabel.' - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Informasi',
        'title' => $typeLabel,
        'description' => 'Konten berikut hanya menampilkan tulisan yang sudah disetujui dan diterbitkan.',
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($entries->isEmpty())
            <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
                Belum ada {{ strtolower($typeLabel) }} yang diterbitkan.
            </p>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($entries as $entry)
                    <article class="card flex flex-col p-5">
                        <p class="text-xs font-medium text-ink-500">
                            {{ $entry->published_at?->format('j F Y') ?? 'Tanggal belum tersedia' }}
                        </p>

                        <h2 class="mt-1 text-lg font-semibold text-ink-900">
                            <a class="hover:text-primary-700" href="{{ route($type.'.show', $entry->slug) }}">{{ $entry->title }}</a>
                        </h2>

                        @if (filled($entry->excerpt))
                            <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $entry->excerpt }}</p>
                        @elseif (filled($entry->body))
                            <p class="mt-2 text-sm leading-relaxed text-ink-600">
                                {{ \Illuminate\Support\Str::limit(strip_tags($entry->body), 180) }}
                            </p>
                        @endif

                        <a class="mt-4 text-sm font-semibold text-primary-700 hover:text-primary-800" href="{{ route($type.'.show', $entry->slug) }}">
                            Baca selengkapnya
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
