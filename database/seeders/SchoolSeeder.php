<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SchoolUnit;
use Illuminate\Database\Seeder;

/**
 * Seeder unit sekolah dan program placeholder.
 *
 * Nama resmi unit dan program masih menunggu konfirmasi sekolah, sehingga
 * seluruh nilai di bawah ini diberi label placeholder dan dapat diubah
 * melalui portal admin.
 */
class SchoolSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, level: string}>
     */
    public const UNITS = [
        ['code' => 'SMP', 'name' => 'SMP DMR (placeholder)', 'level' => 'SMP'],
        ['code' => 'SMA', 'name' => 'SMA DMR (placeholder)', 'level' => 'SMA'],
        ['code' => 'SMK', 'name' => 'SMK DMR (placeholder)', 'level' => 'SMK'],
    ];

    /**
     * @var array<int, array{unit: string, code: string, name: string}>
     */
    public const PROGRAMS = [
        ['unit' => 'SMK', 'code' => 'OTO', 'name' => 'Otomotif'],
        ['unit' => 'SMK', 'code' => 'PPLG', 'name' => 'PPLG (Pengembangan Perangkat Lunak dan Gim)'],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $unit) {
            SchoolUnit::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'level' => $unit['level'],
                    'official_status' => 'Status resmi belum dikonfirmasi sekolah.',
                    'is_active' => true,
                ]
            );
        }

        foreach (self::PROGRAMS as $program) {
            $unit = SchoolUnit::where('code', $program['unit'])->first();

            if ($unit === null) {
                continue;
            }

            Program::updateOrCreate(
                ['school_unit_id' => $unit->id, 'code' => $program['code']],
                [
                    'name' => $program['name'],
                    'description' => 'Deskripsi resmi program menunggu konfirmasi sekolah.',
                    'is_active' => true,
                ]
            );
        }
    }
}
