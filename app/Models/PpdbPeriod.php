<?php

namespace App\Models;

use App\Enums\PpdbPeriodStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbPeriod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'registration_start',
        'registration_end',
        'announcement_date',
        'status',
        'registration_fee',
        'currency',
        'payment_policy',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'registration_start' => 'date',
            'registration_end' => 'date',
            'announcement_date' => 'date',
            'registration_fee' => 'decimal:2',
            'payment_policy' => 'array',
            'status' => PpdbPeriodStatus::class,
        ];
    }

    /**
     * Admin yang membuat periode ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Unit sekolah yang dicakup oleh periode ini.
     */
    public function schoolUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolUnit::class,
            'ppdb_period_units',
            'ppdb_period_id',
            'school_unit_id'
        )->withTimestamps();
    }

    /**
     * Baris penghubung periode dan unit sekolah.
     */
    public function periodUnits(): HasMany
    {
        return $this->hasMany(PpdbPeriodUnit::class, 'ppdb_period_id');
    }

    /**
     * Persyaratan dokumen yang berlaku pada periode ini.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(PpdbRequirement::class, 'ppdb_period_id');
    }

    /**
     * Pendaftaran yang diterima pada periode ini.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'ppdb_period_id');
    }

    /**
     * Periode yang sedang menerima pendaftaran.
     */
    public function scopeAcceptingRegistration(Builder $query): Builder
    {
        return $query->where('status', PpdbPeriodStatus::Open);
    }

    /**
     * Periode yang sedang aktif pada tanggal tertentu.
     */
    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->where('status', PpdbPeriodStatus::Open)
            ->whereDate('registration_start', '<=', $date)
            ->whereDate('registration_end', '>=', $date);
    }
}
