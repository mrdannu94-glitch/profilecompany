<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationDocument extends Model
{
    protected $fillable = [
        'registration_id',
        'ppdb_requirement_id',
        'media_id',
        'version_number',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'reviewed_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'status' => DocumentStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Persyaratan dokumen yang dipenuhi oleh versi ini.
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(PpdbRequirement::class, 'ppdb_requirement_id');
    }

    /**
     * Metadata berkas privat yang disimpan pada disk privat.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Petugas yang memeriksa dokumen ini.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Versi terbaru untuk satu persyaratan pada satu pendaftaran.
     */
    public function scopeLatestForRequirement(Builder $query, int $registrationId, int $requirementId): Builder
    {
        return $query
            ->where('registration_id', $registrationId)
            ->where('ppdb_requirement_id', $requirementId)
            ->orderByDesc('version_number');
    }

    /**
     * Dokumen yang menunggu pemeriksaan petugas.
     */
    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->whereIn('status', [DocumentStatus::Uploaded, DocumentStatus::UnderReview]);
    }
}
