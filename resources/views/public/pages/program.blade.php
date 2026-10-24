@extends('layouts.public')

@section('title', 'Jurusan & Program - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Akademik',
        'title' => 'Jurusan & Program',
        'description' => 'Program yang tersedia pada setiap unit sekolah. Nama program masih berupa placeholder sampai dikonfirmasi sekolah.',
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($units->isEmpty())
            <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
                Data unit dan program belum tersedia.
            </p>
        @else
            <div class="space-y-8">
                @foreach ($units as $unit)
                    <div class="card p-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="text-xl font-semibold text-ink-900">{{ $unit->name }}</h2>
                            <span class="text-xs font-semibold tracking-wide text-primary-700 uppercase">{{ $unit->level }}</span>
                        </div>

                        @if ($unit->programs->isEmpty())
                            <p class="mt-3 text-sm text-ink-600">Program untuk unit ini belum didaftarkan.</p>
                        @else
                            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($unit->programs as $program)
                                    <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                                        <p class="text-sm font-semibold text-ink-900">{{ $program->name }}</p>
                                        <p class="mt-1 text-sm text-ink-600">
                                            {{ $program->description ?: 'Deskripsi program belum tersedia.' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($entries->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-2xl font-semibold text-ink-900">Informasi Program dari CMS</h2>
                <div class="mt-4">
                    @include('public.partials.entries', ['entries' => $entries])
                </div>
            </div>
        @endif
    </section>
@endsection
