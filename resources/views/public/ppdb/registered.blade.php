@extends('layouts.public')

@section('title', 'Pendaftaran Diterima — '.config('app.name'))

@section('content')
    <section class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold tracking-wide text-success-700 uppercase">Pendaftaran berhasil dibuat</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-900">Simpan nomor dan kode akses Anda</h1>

        <div class="mt-6 rounded-control border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700">
            <p class="font-semibold">Kode akses hanya ditampilkan satu kali.</p>
            <p class="mt-1">
                Simpan di tempat aman. Kode ini dipakai untuk memeriksa status pendaftaran dan mengunggah dokumen.
                Bila lupa, hubungi CS sekolah untuk pemulihan kode melalui prosedur verifikasi resmi.
            </p>
        </div>

        <dl class="mt-6 space-y-4">
            <div class="card p-5">
                <dt class="text-sm font-medium text-ink-500">Nomor pendaftaran</dt>
                <dd class="mt-1 font-mono text-xl font-semibold text-ink-900">{{ $registration->registration_number }}</dd>
            </div>

            <div class="card p-5">
                <dt class="text-sm font-medium text-ink-500">Kode akses</dt>
                <dd class="mt-1 font-mono text-xl font-semibold tracking-widest text-ink-900">{{ $accessCode }}</dd>
            </div>
        </dl>

        @if ($payment && $instruction)
            <div class="card mt-6 p-5">
                <h2 class="text-base font-semibold text-ink-900">Instruksi Pembayaran</h2>
                <dl class="mt-3 space-y-1 text-sm text-ink-700">
                    <div class="flex justify-between gap-4">
                        <dt>Nominal</dt>
                        <dd>Rp {{ number_format((float) $instruction['amount'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt>Metode</dt>
                        <dd class="text-right">{{ $instruction['method'] }}</dd>
                    </div>
                </dl>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-ink-600">
                    @foreach ($instruction['instructions'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-ink-500">
                    Unggahan bukti pembayaran tidak otomatis dianggap lunas. Petugas melakukan verifikasi manual
                    terhadap pembayaran yang masuk.
                </p>
            </div>
        @else
            <div class="card mt-6 p-5 text-sm text-ink-600">
                <p class="font-semibold text-ink-800">Pembayaran belum dapat dimulai.</p>
                <p class="mt-1">
                    Nominal dan metode pembayaran resmi belum ditetapkan sekolah. Instructions pembayaran akan tampil di
                    halaman status setelah ditetapkan.
                </p>
            </div>
        @endif

        <div class="mt-8 flex flex-wrap gap-3">
            <a class="btn-primary" href="{{ route('ppdb.status.show') }}">Lihat Status Pendaftaran</a>
            <a class="btn-secondary" href="{{ route('home') }}">Kembali ke beranda</a>
        </div>
    </section>
@endsection
