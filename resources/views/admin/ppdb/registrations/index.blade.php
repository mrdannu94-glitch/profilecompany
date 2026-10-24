@extends('layouts.app')

@section('title', 'Pendaftaran PPDB')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Pendaftaran PPDB</h1>
            <p class="mt-1 text-sm text-ink-600">
                Daftar pendaftaran yang masuk. Gunakan filter untuk menyaring berdasarkan periode, jenjang, status, atau
                nomor pendaftaran.
            </p>
        </div>

        <form class="card p-4" method="GET" action="{{ route('admin.ppdb.registrations.index') }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="field-label" for="q">Cari</label>
                    <input class="field-input" id="q" name="q" type="search" value="{{ request('q') }}"
                        placeholder="Nomor atau nama calon siswa">
                </div>

                <div>
                    <label class="field-label" for="period">Periode</label>
                    <select class="field-input" id="period" name="period">
                        <option value="">Semua periode</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected((int) request('period') === $period->id)>
                                {{ $period->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="unit">Jenjang</label>
                    <select class="field-input" id="unit" name="unit">
                        <option value="">Semua jenjang</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((int) request('unit') === $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="status">Status</label>
                    <select class="field-input" id="status" name="status">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button class="btn-primary" type="submit">Terapkan filter</button>
                <a class="btn-secondary" href="{{ route('admin.ppdb.registrations.index') }}">Reset</a>
            </div>
        </form>

        <div class="card overflow-hidden">
            @if ($registrations->isEmpty())
                <p class="px-6 py-8 text-sm text-ink-600">Belum ada pendaftaran yang cocok dengan filter.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full text-sm">
                        <caption class="sr-only">Daftar pendaftaran PPDB</caption>
                        <thead>
                            <tr class="border-b border-ink-200 bg-ink-50 text-left text-xs tracking-wide text-ink-500 uppercase">
                                <th scope="col" class="px-4 py-3">Nomor</th>
                                <th scope="col" class="px-4 py-3">Calon siswa</th>
                                <th scope="col" class="px-4 py-3">Jenjang</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Tanggal</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($registrations as $registration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-ink-800">{{ $registration->registration_number }}</td>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $registration->candidate_name }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $registration->schoolUnit?->name }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$registration->status" /></td>
                                    <td class="px-4 py-3 text-ink-600">{{ $registration->submitted_at?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="font-medium text-primary-700 hover:underline"
                                            href="{{ route('admin.ppdb.registrations.show', $registration) }}">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-200 px-4 py-3">
                    {{ $registrations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
