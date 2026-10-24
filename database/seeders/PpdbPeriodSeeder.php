<?php

namespace Database\Seeders;

use App\Enums\PpdbPeriodStatus;
use App\Models\PpdbPeriod;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder periode PPDB placeholder.
 *
 * Periode resmi beserta tanggal dan nominal biaya ditetapkan sekolah. Periode
 * placeholder ini dibuat dengan status `draft` supaya pendaftaran belum
 * menerima calon siswa. Admin dapat menyalin dan mengaturnya sendiri melalui
 * portal `/portal/ppdb/periode`.
 */
class PpdbPeriodSeeder extends Seeder
{
    /**
     * Kode periode placeholder. Berbeda dengan periode resmi.
     */
    public const CODE = 'PPDB-PLACEHOLDER';

    public function run(): void
    {
        $creator = User::query()->orderBy('id')->first();

        if ($creator === null) {
            return;
        }

        $period = PpdbPeriod::firstOrNew(['code' => self::CODE]);

        $period->fill([
            'name' => 'PPDB placeholder (belum resmi)',
            'registration_start' => now()->startOfMonth()->toDateString(),
            'registration_end' => now()->endOfMonth()->toDateString(),
            'status' => PpdbPeriodStatus::Draft,
            'registration_fee' => null,
            'currency' => 'IDR',
            'payment_policy' => [
                'method' => 'Menunggu penetapan sekolah',
                'instructions' => [
                    'Metode pembayaran resmi belum ditetapkan sekolah. Hubungi CS sekolah untuk informasi pembayaran.',
                ],
            ],
        ]);

        $period->created_by = $creator->id;
        $period->save();

        $period->schoolUnits()->sync(
            SchoolUnit::query()->where('is_active', true)->pluck('id')->all()
        );
    }
}
