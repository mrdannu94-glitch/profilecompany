<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Portal Internal') — {{ config('app.name') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-ink-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a class="flex items-center gap-3" href="{{ route('dashboard') }}">
                    <span class="flex h-9 w-9 items-center justify-center rounded-control bg-primary-700 text-xs font-bold text-white">
                        DMR
                    </span>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold text-ink-900">Portal Internal</span>
                        <span class="block text-xs text-ink-500">{{ config('app.name') }}</span>
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-ink-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-ink-500">
                            {{ auth()->user()->roles->pluck('display_name')->join(', ') ?: 'Tanpa role' }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:px-8">
            <aside class="lg:w-64 lg:shrink-0">
                <nav class="card p-3" aria-label="Navigasi portal internal">
                    <p class="px-2 py-1 text-xs font-semibold tracking-wide text-ink-500 uppercase">Menu</p>

                    <ul class="mt-2 space-y-1">
                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('dashboard') }}"
                            >Dasbor</a>
                        </li>

                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.ppdb.periods*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('admin.ppdb.periods.index') }}"
                            >Periode PPDB</a>
                        </li>

                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.ppdb.requirements*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('admin.ppdb.requirements.index') }}"
                            >Persyaratan Dokumen</a>
                        </li>

                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.ppdb.registrations*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('admin.ppdb.registrations.index') }}"
                            >Pendaftaran</a>
                        </li>

                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.ppdb.documents*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('admin.ppdb.documents.index') }}"
                            >Dokumen Masuk</a>
                        </li>

                        <li>
                            <a
                                class="block rounded-control px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.ppdb.payments*') ? 'bg-primary-50 text-primary-800' : 'text-ink-700 hover:bg-ink-100' }}"
                                href="{{ route('admin.ppdb.payments.index') }}"
                            >Verifikasi Pembayaran</a>
                        </li>

                        <li>
                            <span class="block rounded-control px-3 py-2 text-sm text-ink-400">Konten CMS (segera)</span>
                        </li>

                        <li>
                            <span class="block rounded-control px-3 py-2 text-sm text-ink-400">Notifikasi (segera)</span>
                        </li>

                        <li>
                            <span class="block rounded-control px-3 py-2 text-sm text-ink-400">Audit Log (segera)</span>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="min-w-0 flex-1">
                @include('layouts.partials.flash')

                @yield('content')
            </main>
        </div>

        <footer class="border-t border-ink-200 bg-white">
            <div class="mx-auto w-full max-w-7xl px-4 py-4 text-xs text-ink-500 sm:px-6 lg:px-8">
                Portal internal {{ config('app.name') }}. Akses dibatasi sesuai role dan permission.
            </div>
        </footer>
    </div>
</body>
</html>
