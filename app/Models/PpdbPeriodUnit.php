<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris penghubung periode PPDB dan unit sekolah.
 *
 * Kunci primer tabel ini berupa kombinasi (ppdb_period_id, school_unit_id).
 * Laravel belum mendukung kunci primer komposit secara bawaan, sehingga
 * kunci gabungan ditangani manual di bawah: `getKey()` mengembalikan
 * gabungan kedua kolom, dan query simpan/hapus dibatasi pada kedua kolom.
 */
class PpdbPeriodUnit extends Model
{
    /**
     * Kunci primer tabel ini berupa kombinasi (ppdb_period_id, school_unit_id).
     *
     * Laravel memerlukan nama kunci primer yang tidak null untuk
     * `delete()`, sehingga `ppdb_period_id` tetap dipakai sebagai nama
     * kunci. Kunci gabungan yang sebenarnya ditangani oleh `getKey()` dan
     * `setKeysForSaveQuery()`.
     */
    protected $primaryKey = 'ppdb_period_id';

    /**
     * Tabel ini tidak memakai auto increment.
     */
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ppdb_period_id',
        'school_unit_id',
    ];

    protected function casts(): array
    {
        return [
            'ppdb_period_id' => 'integer',
            'school_unit_id' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id');
    }

    public function schoolUnit(): BelongsTo
    {
        return $this->belongsTo(SchoolUnit::class, 'school_unit_id');
    }

    /**
     * Kunci gabungan kedua kolom.
     */
    public function getKey(): string
    {
        return $this->getAttribute('ppdb_period_id').':'.$this->getAttribute('school_unit_id');
    }

    /**
     * Nama rute tidak dipakai untuk model ini, tetapi tetap mengembalikan
     * kunci gabungan agar tidak terjadi galat saat dipanggil.
     */
    public function getRouteKeyName(): string
    {
        return 'ppdb_period_id';
    }

    /**
     * Cari baris berdasarkan kombinasi kedua kolom kunci.
     */
    public static function findByPair(int $ppdbPeriodId, int $schoolUnitId): ?static
    {
        return static::query()
            ->where('ppdb_period_id', $ppdbPeriodId)
            ->where('school_unit_id', $schoolUnitId)
            ->first();
    }

    /**
     * Hubungkan periode dengan unit sekolah secara idempoten.
     */
    public static function link(int $ppdbPeriodId, int $schoolUnitId): ?static
    {
        return static::query()->firstOrCreate([
            'ppdb_period_id' => $ppdbPeriodId,
            'school_unit_id' => $schoolUnitId,
        ]);
    }

    /**
     * Batasi query ke kombinasi kedua kolom kunci.
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('ppdb_period_id', '=', $this->original['ppdb_period_id'] ?? $this->getAttribute('ppdb_period_id'))
            ->where('school_unit_id', '=', $this->original['school_unit_id'] ?? $this->getAttribute('school_unit_id'));
    }

    /**
     * Batasi query penghapusan ke kombinasi kedua kolom kunci.
     */
    protected function setKeysForSelectQuery($query)
    {
        return $this->setKeysForSaveQuery($query);
    }

    /**
     * Batasi query pembaruan ke kombinasi kedua kolom kunci.
     */
    public function scopeForPair(Builder $query, int $ppdbPeriodId, int $schoolUnitId): Builder
    {
        return $query
            ->where('ppdb_period_id', $ppdbPeriodId)
            ->where('school_unit_id', $schoolUnitId);
    }
}
