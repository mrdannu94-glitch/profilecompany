<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRevisionReview extends Model
{
    protected $fillable = [
        'content_revision_id',
        'reviewer_id',
        'action',
        'note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(
            ContentRevision::class,
            'content_revision_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
