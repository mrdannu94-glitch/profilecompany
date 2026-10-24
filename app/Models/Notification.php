<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi internal portal.
 *
 * Notifikasi ini memakai tabel `notifications` milik aplikasi, bukan tabel
 * notifikasi bawaan Laravel, karena tidakifiable_type/id tidak tersedia
 * pada skema yang dipakai. Isi notifikasi tidak boleh memuat kode akses
 * calon siswa atau dokumen sensitif.
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Penerima notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Notifikasi yang belum dibaca.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
