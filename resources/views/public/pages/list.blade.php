@extends('layouts.public')

@section('title', $title.' - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => $eyebrow ?? 'Informasi',
        'title' => $title,
        'description' => $description,
    ])

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @include('public.partials.entries', [
            'entries' => $entries,
            'empty' => 'Belum ada konten yang diterbitkan untuk bagian ini.',
        ])
    </section>
@endsection
