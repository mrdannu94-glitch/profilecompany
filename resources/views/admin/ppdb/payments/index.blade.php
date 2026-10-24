@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran PPDB')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Verifikasi Pembayaran</h1>
            <p class="mt-1 text-sm text-ink-600">
                Pembayaran manual dikonfirmasi setelah dana benar-benar diterima. Unggahan bukti dari calon siswa tidak
                otomatis dianggap lunas.
            </p>
        </div>

        <form class="card p-4" method="GET" action="{{ route('admin.ppdb.payments.index') }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="field-label" for="q">Nomor pendaftaran</label>
                    <input class="field-input" id="q" name="q" type="search" value="{{ request('q') }}">
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
                <button class="btn-primary" type="submit">Terapkan</button>
                <a class="btn-secondary" href="{{ route('admin.ppdb.payments.index') }}">Reset</a>
            </div>
        </form>

        <div class="card overflow-hidden">
            @if ($payments->isEmpty())
                <p class="px-6 py-8 text-sm text-ink-600">Belum ada percobaan pembayaran.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full text-sm">
                        <caption class="sr-only">Daftar percobaan pembayaran PPDB</caption>
                        <thead>
                            <tr class="border-b border-ink-200 bg-ink-50 text-left text-xs tracking-wide text-ink-500 uppercase">
                                <th scope="col" class="px-4 py-3">Nomor</th>
                                <th scope="col" class="px-4 py-3">Nominal</th>
                                <th scope="col" class="px-4 py-3">Percobaan</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a class="font-mono text-primary-700 hover:underline"
                                            href="{{ route('admin.ppdb.registrations.show', $payment->registration) }}">
                                            {{ $payment->registration?->registration_number }}
                                        </a>
                                        <p class="text-xs text-ink-500">{{ $payment->registration?->candidate_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-ink-700">
                                        Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        <span class="text-xs text-ink-400">{{ $payment->currency }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">#{{ $payment->attempt_number }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$payment->status" /></td>
                                    <td class="px-4 py-3">
                                        @if (! $payment->status->isFinal())
                                            <form class="flex flex-wrap items-center gap-2" method="POST"
                                                action="{{ route('admin.ppdb.payments.confirm', $payment) }}">
                                                @csrf
                                                <input class="field-input max-w-[12rem]" name="note" type="text" maxlength="200"
                                                    placeholder="Catatan verifikasi">
                                                <button class="btn-primary" type="submit">Konfirmasi</button>
                                            </form>

                                            <form class="mt-2 flex flex-wrap items-center gap-2" method="POST"
                                                action="{{ route('admin.ppdb.payments.fail', $payment) }}">
                                                @csrf
                                                <input class="field-input max-w-[12rem]" name="note" type="text" maxlength="200"
                                                    placeholder="Alasan gagal (wajib)">
                                                <button class="btn-secondary" type="submit">Tandai Gagal</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-ink-500">Selesai diproses</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-200 px-4 py-3">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
