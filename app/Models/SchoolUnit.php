<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'level',
        'official_status',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'school_unit_id');
    }

    /**
     * Unit sekolah ini pernah dicakup oleh suatu periode PPDB.
     */
    public function periodUnits(): HasMany
    {
        return $this->hasMany(PpdbPeriodUnit::class, 'school_unit_id');
    }

    /**
     * Persyaratan dokumen yang berlaku untuk unit ini.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(PpdbRequirement::class, 'school_unit_id');
    }

    /**
     * Pendaftaran yang terjadi pada unit ini.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'school_unit_id');
    }
}
