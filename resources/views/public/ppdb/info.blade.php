@extends('layouts.public')

@section('title', 'Informasi PPDB — '.config('app.name'))

@section('description', 'Informasi resmi penerimaan peserta didik baru, termasuk periode, persyaratan dokumen, dan cara memeriksa status pendaftaran.')

@section('content')
    <section class="border-b border-ink-200 bg-primary-950 text-white">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-wide text-primary-200 uppercase">Penerimaan Peserta Didik Baru</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Informasi PPDB</h1>
            <p class="mt-4 max-w-2xl text-base text-primary-100">
                Pendaftaran dilakukan secara online. Isi formulir, unggah dokumen yang diminta, lalu periksa status
                pendaftaran memakai nomor pendaftaran dan kode akses pribadi.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a class="btn-primary" href="{{ route('ppdb.register') }}">Daftar Sekarang</a>
                <a class="btn-secondary" href="{{ route('ppdb.status') }}">Cek Status Pendaftaran</a>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-semibold text-ink-900">Tahapan Pendaftaran</h2>
                <ol class="mt-6 space-y-4">
                    @foreach ([
                        ['Isi formulir pendaftaran', 'Pilih jenjang dan program, lalu lengkapi data calon siswa dan data kontak orang tua atau wali.'],
                        ['Unggah dokumen', 'Unggah berkas sesuai persyaratan yang berlaku. Berkas yang dikembalikan dapat diunggah ulang.'],
                        ['Lakukan pembayaran', 'Pembayaran mengikuti metode yang ditetapkan sekolah. Nominal dan metode resmi belum dipublikasikan.'],
                        ['Pemeriksaan dokumen', 'Petugas memeriksa kelengkapan dan validitas berkas yang diunggah.'],
                        ['Pengumuman hasil', 'Panitia PPDB menetapkan hasil seleksi dan mengumumkannya melalui halaman status.'],
                    ] as $index => $step)
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-700 text-sm font-semibold text-white">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="text-base font-semibold text-ink-900">{{ $step[0] }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $step[1] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-base font-semibold text-ink-900">Periode Aktif</h2>

                    @if ($period)
                        <p class="mt-2 text-sm font-medium text-ink-800">{{ $period->name }}</p>
                        <dl class="mt-3 space-y-1 text-sm text-ink-600">
                            <div class="flex justify-between gap-4">
                                <dt>Opens</dt>
                                <dd class="text-right">{{ $period->registration_start?->format('d M Y') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Closes</dt>
                                <dd class="text-right">{{ $period->registration_end?->format('d M Y') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Biaya</dt>
                                <dd class="text-right">
                                    @if ($period->registration_fee !== null)
                                        Rp {{ number_format((float) $period->registration_fee, 0, ',', '.') }}
                                    @else
                                        <span class="text-ink-400">Belum ditetapkan</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @else
                        <p class="mt-2 text-sm text-ink-600">
                            Pendaftaran sedang tidak dibuka. Jadwal resmi masih menunggu pengumuman sekolah.
                        </p>
                    @endif
                </div>

                <div class="card p-5">
                    <h2 class="text-base font-semibold text-ink-900">Jenjang yang Menerima</h2>

                    @if ($units->isEmpty())
                        <p class="mt-2 text-sm text-ink-600">Belum ada jenjang yang dibuka untuk pendaftaran.</p>
                    @else
                        <ul class="mt-3 space-y-2 text-sm text-ink-700">
                            @foreach ($units as $unit)
                                <li class="flex items-center justify-between gap-3">
                                    <span>{{ $unit->name }}</span>
                                    <span class="text-xs text-ink-400">{{ $unit->level }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    <section class="border-t border-ink-200 bg-ink-50">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-ink-900">Persyaratan Dokumen</h2>
            <p class="mt-2 max-w-3xl text-sm text-ink-600">
                Daftar dokumen wajib berbeda menurut jenjang dan program. Daftar ini diisi oleh Admin berdasarkan
                ketentuan sekolah.
            </p>

            @if ($requirements->isEmpty())
                <p class="mt-6 rounded-control border border-ink-200 bg-white px-4 py-3 text-sm text-ink-600">
                    Daftar persyaratan dokumen belum ditetapkan. Silakan cek kembali setelah sekolah mengumumkannya.
                </p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-full border-collapse bg-white text-sm">
                        <caption class="sr-only">Daftar persyaratan dokumen PPDB</caption>
                        <thead>
                            <tr class="border-b border-ink-200 text-left text-xs tracking-wide text-ink-500 uppercase">
                                <th scope="col" class="px-4 py-3">Dokumen</th>
                                <th scope="col" class="px-4 py-3">Jenjang</th>
                                <th scope="col" class="px-4 py-3">Program</th>
                                <th scope="col" class="px-4 py-3">Wajib</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($requirements as $requirement)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $requirement->name }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $requirement->schoolUnit?->name }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $requirement->program?->name ?? 'Semua program' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $requirement->is_required ? 'Ya' : 'Tidak' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-8 rounded-control border border-ink-200 bg-white p-5 text-sm text-ink-600">
                <p class="font-semibold text-ink-800">Informasi yang belum tersedia</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Nominal resmi biaya pendaftaran.</li>
                    <li>Provider dan metode pembayaran yang diaktifkan.</li>
                    <li>Tanggal pengumuman hasil seleksi.</li>
                    <li>Daftar dokumen wajib final untuk setiap jenjang.</li>
                </ul>
                <p class="mt-3 text-xs text-ink-500">
                    Nilai di atas menunggu keputusan sekolah dan tidak diisi dengan data karangan.
                </p>
            </div>
        </div>
    </section>
@endsection
