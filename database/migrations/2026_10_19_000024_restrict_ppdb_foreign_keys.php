<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah aturan penghapusan foreign key PPDB dari cascade menjadi restrict.
 *
 * ERD 4.16 melarang penghapusan cascade pada data transaksional
 * (pendaftaran, pembayaran, keputusan seleksi) karena satu akun pengguna
 * yang dihapus tidak boleh menghapus seluruh riwayat PPDB.
 *
 * Catatan: aturan `ON DELETE` pada SQLite ditetapkan saat tabel dibuat dan
 * tidak dapat diubah tanpa membangun ulang tabel. Karena itu migration ini
 * hanya dijalankan pada MySQL/MariaDB (lingkungan pengembangan dan
 * produksi). Perilaku pada SQLite tetap cascade dan hanya dipakai untuk
 * pengujian otomatis.
 */
return new class extends Migration
{
    /**
     * daftar foreign key yang aturan hapusannya diubah.
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private array $foreignKeys = [
        ['ppdb_periods', 'created_by', 'users'],
        ['ppdb_requirements', 'ppdb_period_id', 'ppdb_periods'],
        ['ppdb_requirements', 'school_unit_id', 'school_units'],
        ['ppdb_requirements', 'program_id', 'programs'],
        ['registrations', 'ppdb_period_id', 'ppdb_periods'],
        ['registrations', 'school_unit_id', 'school_units'],
        ['registrations', 'program_id', 'programs'],
        ['registration_documents', 'registration_id', 'registrations'],
        ['registration_documents', 'ppdb_requirement_id', 'ppdb_requirements'],
        ['registration_documents', 'media_id', 'media'],
        ['payments', 'registration_id', 'registrations'],
        ['payment_events', 'payment_id', 'payments'],
        ['selection_decisions', 'registration_id', 'registrations'],
        ['selection_decisions', 'decided_by', 'users'],
    ];

    public function up(): void
    {
        if (! $this->supportsAlterForeignKey()) {
            return;
        }

        foreach ($this->foreignKeys as [$table, $column, $referencedTable]) {
            $this->replaceForeignKey($table, $column, $referencedTable);
        }
    }

    public function down(): void
    {
        if (! $this->supportsAlterForeignKey()) {
            return;
        }

        foreach ($this->foreignKeys as [$table, $column, $referencedTable]) {
            $this->replaceForeignKey($table, $column, $referencedTable, 'cascade');
        }
    }

    /**
     * SQLite tidak dapat mengubah aturan foreign key tanpa build ulang tabel.
     */
    private function supportsAlterForeignKey(): bool
    {
        return DB::getDriverName() === 'mysql';
    }

    private function replaceForeignKey(string $table, string $column, string $referencedTable, string $onDelete = 'restrict'): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->dropForeign([$column]);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $onDelete): void {
            $foreign = $blueprint->foreign($column)->references('id')->on($referencedTable);

            if ($onDelete === 'restrict') {
                $foreign->restrictOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }
};
