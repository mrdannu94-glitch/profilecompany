@extends('layouts.public')

@section('title', 'Kontak - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Hubungi Kami',
        'title' => 'Kontak',
        'description' => 'Alamat, telepon, dan email resmi akan ditampilkan setelah dikonfirmasi oleh sekolah.',
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-2">
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-ink-900">Informasi Kontak</h2>

                @if ($page)
                    <div class="mt-4">
                        @include('public.partials.content-body', ['text' => $page->body])
                    </div>
                @else
                    <p class="mt-4 text-sm text-ink-600">
                        Konten kontak belum diterbitkan melalui CMS. Data resmi tidak diisi dengan karangan.
                    </p>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="text-xl font-semibold text-ink-900">Kontak per Unit</h2>

                @if ($units->isEmpty())
                    <p class="mt-4 text-sm text-ink-600">Data unit sekolah belum tersedia.</p>
                @else
                    <ul class="mt-4 space-y-4">
                        @foreach ($units as $unit)
                            <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                                <p class="text-sm font-semibold text-ink-900">{{ $unit->name }}</p>

                                @if (filled($unit->address))
                                    <p class="mt-1 text-sm text-ink-600">{{ $unit->address }}</p>
                                @endif

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    @if (filled($unit->phone))
                                        <a class="text-primary-700 hover:text-primary-800" href="tel:{{ $unit->phone }}">{{ $unit->phone }}</a>
                                    @endif

                                    @if (filled($unit->email))
                                        <a class="text-primary-700 hover:text-primary-800" href="mailto:{{ $unit->email }}">{{ $unit->email }}</a>
                                    @endif
                                </div>

                                @if (blank($unit->address) && blank($unit->phone) && blank($unit->email))
                                    <p class="mt-1 text-sm text-ink-500">Data kontak unit ini belum diisi.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
@endsection
