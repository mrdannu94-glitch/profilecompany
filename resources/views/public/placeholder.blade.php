@extends('layouts.public')

@section('title', $title.' — '.config('app.name'))

@section('content')
    <section class="mx-auto w-full max-w-3xl px-4 py-20 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold tracking-wide text-primary-700 uppercase">Halaman sedang disiapkan</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-900">{{ $title }}</h1>

        <p class="mt-4 text-base leading-relaxed text-ink-600">
            Konten halaman ini dibangun pada {{ $phase }} sesuai rencana implementasi. Halaman hanya diisi dengan data
            resmi sekolah setelah dikonfirmasi, sehingga tidak ada data yang dikarang di sini.
        </p>

        <div class="card mt-8 p-6">
            <h2 class="text-base font-semibold text-ink-900">Alternatif sementara</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink-600">
                <li>
                    <a class="font-medium text-primary-700 hover:underline" href="{{ route('home') }}">Kembali ke beranda</a>
                    untuk ringkasan jenjang sekolah.
                </li>
                <li>
                    <a class="font-medium text-primary-700 hover:underline" href="{{ route('ppdb.info') }}">Informasi PPDB</a>
                    untuk penjelasan umum proses pendaftaran.
                </li>
            </ul>
        </div>
    </section>
@endsection
