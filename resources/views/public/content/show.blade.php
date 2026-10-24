@extends('layouts.public')

@section('title', $entry->title.' - '.config('app.name'))
@section('description', $entry->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $entry->body), 150))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => $typeLabel,
        'title' => $entry->title,
        'description' => $entry->excerpt,
    ])

    <article class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm text-ink-500">
            Diterbitkan {{ $entry->published_at?->format('j F Y') ?? 'tanpa tanggal' }}
            @if ($entry->author)
                oleh {{ $entry->author->name }}
            @endif
        </p>

        <div class="mt-6">
            @include('public.partials.content-body', ['text' => $entry->body])
        </div>

        <a class="btn-secondary mt-10" href="{{ route($type) }}">&larr; Kembali ke {{ $typeLabel }}</a>
    </article>
@endsection
