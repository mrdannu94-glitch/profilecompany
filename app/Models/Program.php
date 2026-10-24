<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_unit_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schoolUnit(): BelongsTo
    {
        return $this->belongsTo(SchoolUnit::class, 'school_unit_id');
    }

    /**
     * Persyaratan dokumen khusus untuk program ini.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(PpdbRequirement::class, 'program_id');
    }

    /**
     * Pendaftaran yang memilih program ini.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'program_id');
    }
}
