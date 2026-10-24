<section class="border-b border-ink-200 bg-primary-950 text-white">
    <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <p class="text-xs font-semibold tracking-wide text-primary-200 uppercase">{{ $eyebrow ?? 'Website Sekolah' }}</p>
        <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h1>

        @isset($description)
            <p class="mt-4 max-w-2xl text-base text-primary-100">{{ $description }}</p>
        @endisset
    </div>
</section>
