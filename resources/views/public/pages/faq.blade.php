@extends('layouts.public')

@section('title', 'FAQ - '.config('app.name'))

@section('content')
    @include('public.partials.page-header', [
        'eyebrow' => 'Bantuan',
        'title' => 'Pertanyaan yang Sering Diajukan',
        'description' => 'Jawaban singkat untuk pertanyaan umum seputar sekolah dan penerimaan peserta didik baru.',
    ])

    <section class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($entries->isEmpty())
            <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
                Belum ada FAQ yang diterbitkan.
            </p>
        @else
            <div class="space-y-3">
                @foreach ($entries as $entry)
                    <details class="card p-5">
                        <summary class="cursor-pointer text-base font-semibold text-ink-900">
                            {{ $entry->title }}
                        </summary>

                        <div class="mt-2">
                            @include('public.partials.content-body', ['text' => $entry->body ?: $entry->excerpt])
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>
@endsection
