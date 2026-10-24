<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\ContentRevisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentRevision extends Model
{
    /** @use HasFactory<ContentRevisionFactory> */
    use HasFactory;

    protected $fillable = [
        'content_item_id',
        'author_id',
        'revision_number',
        'title',
        'slug',
        'excerpt',
        'body',
        'workflow_status',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'reviewed_by',
        'review_note',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'workflow_status' => ContentStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Revisi yang sudah terbit.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('workflow_status', ContentStatus::Published);
    }

    /**
     * Revisi ini masih dapat diubah penulisnya.
     */
    public function isEditable(): bool
    {
        return $this->workflow_status instanceof ContentStatus
            && $this->workflow_status->isEditable();
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            ContentRevisionReview::class,
            'content_revision_id'
        );
    }

    /**
     * Media yang dipakai revisi ini. Tabel pivot hanya memiliki
     * `created_at`, jadi `withTimestamps()` tidak digunakan di sini.
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'content_revision_media',
            'content_revision_id',
            'media_id'
        )->withPivot('usage_type', 'sort_order', 'created_at');
    }
}
