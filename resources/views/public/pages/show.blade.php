@extends('layouts.public')

@section('title', $title.' - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Profil',
        'title' => $title,
        'description' => $description,
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($page)
            <article class="card p-6 sm:p-8">
                <h2 class="text-2xl font-semibold text-ink-900">{{ $page->title }}</h2>

                @if (filled($page->excerpt))
                    <p class="mt-3 text-base leading-relaxed text-ink-600">{{ $page->excerpt }}</p>
                @endif

                <div class="mt-6">
                    @include('public.partials.content-body', ['text' => $page->body])
                </div>
            </article>
        @else
            <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
                Konten halaman ini belum diterbitkan. Administrator dapat menambahkannya melalui CMS.
            </p>
        @endif

        @isset($units)
            <div class="mt-10">
                <h2 class="text-xl font-semibold text-ink-900">Unit Sekolah</h2>

                @if ($units->isEmpty())
                    <p class="mt-4 rounded-control border border-ink-200 bg-ink-50 px-4 py-4 text-sm text-ink-600">
                        Data unit sekolah belum tersedia.
                    </p>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($units as $unit)
                            <article class="card p-5">
                                <p class="text-xs font-semibold tracking-wide text-primary-700 uppercase">{{ $unit->level }}</p>
                                <h3 class="mt-1 text-lg font-semibold text-ink-900">{{ $unit->name }}</h3>
                                <p class="mt-2 text-sm text-ink-600">{{ $unit->official_status ?: 'Status resmi belum dikonfirmasi.' }}</p>
                                @if (filled($unit->address))
                                    <p class="mt-2 text-sm text-ink-600">{{ $unit->address }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        @endisset
    </section>
@endsection
