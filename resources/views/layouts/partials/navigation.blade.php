@php
    $publicLinks = [
        ['label' => 'Beranda', 'route' => 'home'],
        ['label' => 'Profil Sekolah', 'route' => 'profil'],
        ['label' => 'Visi & Misi', 'route' => 'visi-misi'],
        ['label' => 'Jurusan & Program', 'route' => 'program'],
        ['label' => 'Berita', 'route' => 'berita'],
        ['label' => 'Informasi PPDB', 'route' => 'ppdb.info'],
        ['label' => 'Kontak', 'route' => 'kontak'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-ink-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a class="flex items-center gap-3" href="{{ route('home') }}">
            <span class="flex h-10 w-10 items-center justify-center rounded-control bg-primary-700 text-sm font-bold text-white">
                DMR
            </span>
            <span class="leading-tight">
                <span class="block text-sm font-semibold text-ink-900">{{ config('app.name') }}</span>
                <span class="block text-xs text-ink-500">SMP · SMA · SMK</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
            @foreach ($publicLinks as $link)
                <a
                    class="rounded-control px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-ink-100 hover:text-ink-900"
                    href="{{ route($link['route']) }}"
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a class="btn-secondary hidden sm:inline-flex" href="{{ route('ppdb.status') }}">Cek Status</a>
            <a class="btn-primary" href="{{ route('ppdb.register') }}">Daftar PPDB</a>
            <a class="btn-secondary hidden sm:inline-flex" href="{{ route('login') }}">Portal Internal</a>
        </div>
    </div>

    <nav class="border-t border-ink-200 lg:hidden" aria-label="Navigasi utama seluler">
        <div class="mx-auto flex w-full max-w-6xl flex-wrap gap-1 px-4 py-2 sm:px-6">
            @foreach ($publicLinks as $link)
                <a
                    class="rounded-control px-3 py-1.5 text-sm text-ink-700 transition hover:bg-ink-100"
                    href="{{ route($link['route']) }}"
                >{{ $link['label'] }}</a>
            @endforeach
        </div>
    </nav>
</header>
