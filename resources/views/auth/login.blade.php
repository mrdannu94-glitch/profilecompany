@extends('layouts.guest')

@section('title', 'Masuk Portal Internal')

@section('content')
    <div class="card p-6 sm:p-8">
        <h1 class="text-xl font-semibold text-ink-900">Masuk Portal Internal</h1>
        <p class="mt-1 text-sm text-ink-600">
            Gunakan akun yang diberikan administrator sekolah. Calon siswa tidak memerlukan akun; status pendaftaran
            dicek melalui nomor pendaftaran dan kode akses.
        </p>

        @if ($errors->any())
            <div class="mt-4 rounded-control border border-danger-100 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="field-label" for="email">Email</label>
                <input
                    class="field-input"
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div>
                <label class="field-label" for="password">Kata sandi</label>
                <input
                    class="field-input"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="flex items-center gap-2">
                <input
                    class="h-4 w-4 rounded border-ink-300 text-primary-700 focus:ring-primary-500"
                    id="remember"
                    name="remember"
                    type="checkbox"
                    value="1"
                >
                <label class="text-sm text-ink-700" for="remember">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn-primary w-full">Masuk</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-ink-600">
        <a class="font-medium text-primary-700 hover:underline" href="{{ route('home') }}">Kembali ke website sekolah</a>
    </p>
@endsection
