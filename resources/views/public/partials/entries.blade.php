@if ($entries->isEmpty())
    <p class="rounded-control border border-ink-200 bg-ink-50 px-4 py-6 text-sm text-ink-600">
        {{ $empty ?? 'Belum ada konten yang diterbitkan untuk bagian ini.' }}
    </p>
@else
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($entries as $entry)
            <article class="card p-5">
                <p class="text-xs font-medium text-ink-500">
                    {{ $entry->published_at?->format('j F Y') ?? 'Tanggal belum tersedia' }}
                </p>
                <h2 class="mt-1 text-lg font-semibold text-ink-900">{{ $entry->title }}</h2>

                @if (filled($entry->excerpt))
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $entry->excerpt }}</p>
                @elseif (filled($entry->body))
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">
                        {{ \Illuminate\Support\Str::limit(strip_tags($entry->body), 180) }}
                    </p>
                @endif
            </article>
        @endforeach
    </div>
@endif
