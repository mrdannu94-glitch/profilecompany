<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbRequirement extends Model
{
    /**
     * Nilai sentinel `program_scope_id` untuk persyaratan umum.
     */
    public const SCOPE_GENERAL = 0;

    protected $fillable = [
        'ppdb_period_id',
        'school_unit_id',
        'program_id',
        'requirement_code',
        'name',
        'description',
        'accepted_mime_types',
        'max_file_size',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'accepted_mime_types' => 'array',
            'max_file_size' => 'integer',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'program_scope_id' => 'integer',
        ];
    }

    /**
     * Jaga sentinel scope tetap sesuai dengan program yang dipilih.
     */
    protected static function booted(): void
    {
        static::saving(function (self $requirement): void {
            $requirement->program_scope_id = $requirement->program_id ?? self::SCOPE_GENERAL;
        });
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
     * Program yang dicakup persyaratan ini. Null berarti persyaratan umum
     * untuk unit sekolah dan periode terkait.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Versi dokumen yang dikirim untuk memenuhi persyaratan ini.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'ppdb_requirement_id');
    }

    /**
     * Persyaratan yang berlaku untuk unit dan program tertentu.
     */
    public function scopeForScope(Builder $query, int $schoolUnitId, ?int $programId = null): Builder
    {
        return $query
            ->where('school_unit_id', $schoolUnitId)
            ->where(function (Builder $inner) use ($programId): void {
                $inner->whereNull('program_id');

                if ($programId !== null) {
                    $inner->orWhere('program_id', $programId);
                }
            });
    }

    /**
     * Persyaratan wajib yang masih aktif.
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_required', true);
    }
}
