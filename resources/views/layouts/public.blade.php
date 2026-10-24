<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    <meta name="description" content="@yield('description', 'Website resmi sekolah dan informasi pendaftaran PPDB online.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white">
    @include('layouts.partials.navigation')

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-ink-200 bg-ink-50">
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-base font-semibold text-ink-900">{{ config('app.name') }}</p>
                    <p class="mt-2 text-sm text-ink-600">
                        {{ config('dmr.school_name', 'Nama sekolah masih menunggu konfirmasi resmi.') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Profil</p>
                    <ul class="mt-3 space-y-2 text-sm text-ink-600">
                        <li><a class="hover:text-primary-700" href="{{ route('profil') }}">Profil Sekolah</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('visi-misi') }}">Visi & Misi</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('sambutan') }}">Sambutan Kepala Sekolah</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('guru-staff') }}">Guru & Staff</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('program') }}">Jurusan & Program</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Informasi</p>
                    <ul class="mt-3 space-y-2 text-sm text-ink-600">
                        <li><a class="hover:text-primary-700" href="{{ route('berita') }}">Berita</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('pengumuman') }}">Pengumuman</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('agenda') }}">Agenda & Kegiatan</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('prestasi') }}">Prestasi</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('fasilitas') }}">Fasilitas</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('ekstrakurikuler') }}">Ekstrakurikuler</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('galeri') }}">Galeri & Video</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('faq') }}">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Layanan</p>
                    <ul class="mt-3 space-y-2 text-sm text-ink-600">
                        <li><a class="hover:text-primary-700" href="{{ route('ppdb.info') }}">Informasi PPDB</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('ppdb.register') }}">Daftar PPDB</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('ppdb.status') }}">Cek Status Pendaftaran</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('kontak') }}">Kontak</a></li>
                        <li><a class="hover:text-primary-700" href="{{ route('login') }}">Portal Internal</a></li>
                    </ul>
                </div>
            </div>

            <p class="mt-8 border-t border-ink-200 pt-6 text-xs text-ink-500">
                &copy; {{ now()->year }} {{ config('app.name') }}. Seluruh nama unit, program, dan kontak pada
                situs ini masih berupa placeholder sampai disahkan sekolah.
            </p>
        </div>
    </footer>
</body>
</html>
