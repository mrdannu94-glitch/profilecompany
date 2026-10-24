<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Masuk') — {{ config('app.name') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-ink-50">
    <header class="border-b border-ink-200 bg-white">
        <div class="mx-auto flex w-full max-w-6xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <a class="flex items-center gap-3" href="{{ route('home') }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-control bg-primary-700 text-xs font-bold text-white">
                    DMR
                </span>
                <span class="text-sm font-semibold text-ink-900">{{ config('app.name') }}</span>
            </a>
        </div>
    </header>

    <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-ink-200 bg-white">
        <div class="mx-auto w-full max-w-6xl px-4 py-4 text-center text-xs text-ink-500 sm:px-6 lg:px-8">
            Akses portal internal dibatasi untuk staf dan petugas sekolah.
        </div>
    </footer>
</body>
</html>
