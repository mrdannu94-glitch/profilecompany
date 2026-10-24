<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'published_revision_id',
        'content_type',
        'status',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'content_item_id');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(
            ContentRevision::class,
            'published_revision_id'
        );
    }
}
