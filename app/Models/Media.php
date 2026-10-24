<?php

namespace App\Models;

use App\Enums\StorageVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    protected $fillable = [
        'uploaded_by',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'storage_visibility',
        'checksum',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'storage_visibility' => StorageVisibility::class,
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Revisi konten yang memakai media ini.
     */
    public function revisions(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentRevision::class,
            'content_revision_media',
            'media_id',
            'content_revision_id'
        )->withPivot('usage_type', 'sort_order', 'created_at');
    }

    /**
     * Dokumen PPDB yang memakai berkas privat ini.
     */
    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'media_id');
    }

    /**
     * Media privat tidak boleh disajikan lewat URL publik.
     */
    public function isPublic(): bool
    {
        return $this->storage_visibility === StorageVisibility::Public;
    }
}
