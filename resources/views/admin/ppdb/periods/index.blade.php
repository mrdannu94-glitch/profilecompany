@extends('layouts.app')

@section('title', 'Periode PPDB')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Periode PPDB</h1>
                <p class="mt-1 text-sm text-ink-600">
                    Periode menentukan tanggal pendaftaran, unit yang dilayani, nominal biaya, dan persyaratan dokumen.
                </p>
            </div>

            <a class="btn-primary" href="{{ route('admin.ppdb.periods.create') }}">Tambah Periode</a>
        </div>

        <div class="card overflow-hidden">
            @if ($periods->isEmpty())
                <p class="px-6 py-8 text-sm text-ink-600">
                    Belum ada periode PPDB. Tambahkan periode sebelum membuka pendaftaran.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full text-sm">
                        <caption class="sr-only">Daftar periode PPDB</caption>
                        <thead>
                            <tr class="border-b border-ink-200 bg-ink-50 text-left text-xs tracking-wide text-ink-500 uppercase">
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Tanggal</th>
                                <th scope="col" class="px-4 py-3">Unit</th>
                                <th scope="col" class="px-4 py-3">Biaya</th>
                                <th scope="col" class="px-4 py-3">Pendaftaran</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($periods as $period)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink-900">{{ $period->name }}</p>
                                        <p class="font-mono text-xs text-ink-500">{{ $period->code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">
                                        {{ $period->registration_start?->format('d M Y') }} —
                                        {{ $period->registration_end?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">
                                        {{ $period->schoolUnits->pluck('name')->join(', ') ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">
                                        @if ($period->registration_fee !== null)
                                            Rp {{ number_format((float) $period->registration_fee, 0, ',', '.') }}
                                        @else
                                            <span class="text-ink-400">Belum ditetapkan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">{{ $period->registrations_count }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$period->status" /></td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="font-medium text-primary-700 hover:underline"
                                            href="{{ route('admin.ppdb.periods.edit', $period) }}">Ubah</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
