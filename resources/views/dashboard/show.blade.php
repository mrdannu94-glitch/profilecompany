@extends('layouts.app')

@section('title', 'Dasbor')

@section('content')
    @php
        $sectionLabels = [
            'umum' => 'Dasbor',
            'admin' => 'Dasbor Administrator',
            'cs_admin' => 'Dasbor CS Admin',
            'ppdb_officer' => 'Dasbor Petugas PPDB',
            'teacher' => 'Dasbor Guru',
            'staff' => 'Dasbor Staff',
            'student' => 'Dasbor Siswa',
        ];
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">{{ $sectionLabels[$section] ?? 'Dasbor' }}</h1>
            <p class="mt-1 text-sm text-ink-600">
                Selamat datang, {{ $user->name }}.
                @if ($user->roles->isNotEmpty())
                    Role aktif: {{ $user->roles->pluck('display_name')->join(', ') }}.
                @else
                    Akun ini belum memiliki role. Hubungi administrator sekolah.
                @endif
            </p>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-ink-900">Modul yang dapat diakses</h2>
            <p class="mt-1 text-sm text-ink-600">
                Daftar modul menampilkan modul yang diizinkan oleh permission milik akun Anda. Modul belum aktif pada
                fase ini dan akan diisi bertahap.
            </p>

            @if (count($modules) === 0)
                <p class="mt-4 rounded-control border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-600">
                    Belum ada modul yang dapat diakses oleh role Anda saat ini.
                </p>
            @else
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($modules as $module)
                        <li class="rounded-control border border-ink-200 bg-ink-50 p-4">
                            <p class="text-sm font-semibold text-ink-900">{{ $module['label'] }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ $module['description'] }}</p>
                            <p class="mt-2 text-xs text-ink-400">Permission: {{ $module['permission'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-ink-900">Permission aktif</h2>

            @php
                $permissionNames = $user->permissionNames();
            @endphp

            @if ($permissionNames->isEmpty())
                <p class="mt-2 text-sm text-ink-600">Akun ini belum memiliki permission apa pun.</p>
            @else
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($permissionNames as $permission)
                        <li class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-800">
                            {{ $permission }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
