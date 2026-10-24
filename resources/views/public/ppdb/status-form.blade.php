@extends('layouts.public')

@section('title', 'Cek Status Pendaftaran — '.config('app.name'))

@section('content')
    <section class="mx-auto w-full max-w-xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold tracking-wide text-primary-700 uppercase">Status Pendaftaran</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-900">Cek status pendaftaran</h1>
        <p class="mt-3 text-sm text-ink-600">
            Masukkan nomor pendaftaran dan kode akses yang Anda terima. Kode akses tidak dapat ditampilkan kembali
            oleh sistem.
        </p>

        @if ($errors->any())
            <div class="mt-6 rounded-control border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="card mt-8 space-y-4 p-6" method="POST" action="{{ route('ppdb.status.check') }}">
            @csrf

            <div>
                <label class="field-label" for="registration_number">Nomor pendaftaran</label>
                <input class="field-input font-mono" id="registration_number" name="registration_number" type="text"
                    required value="{{ old('registration_number') }}" autocomplete="off">
            </div>

            <div>
                <label class="field-label" for="access_code">Kode akses</label>
                <input class="field-input font-mono tracking-widest" id="access_code" name="access_code" type="text"
                    required autocomplete="off">
            </div>

            <button class="btn-primary w-full" type="submit">Periksa Status</button>
        </form>

        <p class="mt-6 text-sm text-ink-600">
            Belum mendaftar?
            <a class="font-medium text-primary-700 hover:underline" href="{{ route('ppdb.register') }}">Isi formulir pendaftaran</a>.
        </p>
    </section>
@endsection
