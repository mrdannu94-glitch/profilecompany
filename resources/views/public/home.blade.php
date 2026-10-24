@extends('layouts.public')

@section('title', 'Beranda — '.config('app.name'))

@section('content')
    <section class="border-b border-ink-200 bg-primary-950 text-white">
        <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <p class="text-sm font-semibold tracking-wide text-primary-200 uppercase">Penerimaan Peserta Didik Baru</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl">
                {{ config('dmr.school_name') }}
            </h1>
            <p class="mt-4 max-w-2xl text-base text-primary-100">
                Informasi resmi jenjang SMP, SMA, dan SMK dalam satu yayasan. Halaman ini masih menggunakan data
                placeholder dan akan dilengkapi setelah sekolah mengonfirmasi identitas, program, dan kontak resmi.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a class="btn-primary" href="{{ route('ppdb.info') }}">Informasi PPDB</a>
                <a class="btn-secondary" href="{{ route('login') }}">Portal Internal</a>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-ink-900">Jenjang Sekolah</h2>
        <p class="mt-2 max-w-2xl text-sm text-ink-600">
            Daftar unit di bawah ini berasal dari data unit sekolah pada sistem. Nama dan program masih berupa
            placeholder sampai dikonfirmasi sekolah.
        </p>

        @if ($units->isEmpty())
            <p class="mt-6 rounded-control border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-600">
                Data unit sekolah belum tersedia. Jalankan <code>php artisan db:seed</code> untuk mengisi data awal.
            </p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($units as $unit)
                    <article class="card p-5">
                        <p class="text-xs font-semibold tracking-wide text-primary-700 uppercase">{{ $unit->level }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink-900">{{ $unit->name }}</h3>
                        <p class="mt-1 text-sm text-ink-500">Kode unit: {{ $unit->code }}</p>
                        <p class="mt-3 text-sm text-ink-600">
                            {{ $unit->official_status ?: 'Status resmi unit belum dikonfirmasi.' }}
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="border-t border-ink-200 bg-ink-50">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-2">
                <div>
                    <h2 class="text-2xl font-semibold text-ink-900">Pendaftaran PPDB Online</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">
                        Calon siswa mendaftar dengan mengisi formulir, mengunggah dokumen, lalu melakukan pembayaran
                        sesuai metode yang tersedia. Status pendaftaran diperiksa menggunakan nomor pendaftaran dan kode
                        akses pribadi. Kode akses hanya ditampilkan satu kali.
                    </p>
                    <a class="btn-primary mt-6" href="{{ route('ppdb.info') }}">Lihat Informasi PPDB</a>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-ink-900">Informasi yang belum tersedia</h3>
                    <ul class="mt-3 space-y-2 text-sm text-ink-600">
                        <li>Nominal biaya pendaftaran resmi.</li>
                        <li>Daftar dokumen wajib untuk setiap jenjang dan program.</li>
                        <li>Jadwal pengumuman hasil seleksi.</li>
                        <li>Alamat, nomor kontak, dan jam layanan sekolah.</li>
                    </ul>
                    <p class="mt-4 text-xs text-ink-500">
                        Nilai di atas menunggu keputusan sekolah dan tidak boleh diisi dengan data karangan.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
