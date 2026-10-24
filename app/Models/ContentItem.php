<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Jenis konten yang dapat dikelola melalui CMS.
     *
     * Jenis halaman tunggal hanya boleh memiliki satu item agar halaman
     * publik tidak menampilkan versi yang saling bertentangan.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'home' => 'Beranda',
        'profil' => 'Profil Sekolah',
        'visi_misi' => 'Visi & Misi',
        'sambutan' => 'Sambutan Kepala Sekolah',
        'guru_staff' => 'Guru & Staff',
        'program' => 'Jurusan & Program',
        'fasilitas' => 'Fasilitas',
        'ekstrakurikuler' => 'Ekstrakurikuler',
        'prestasi' => 'Prestasi',
        'berita' => 'Berita',
        'pengumuman' => 'Pengumuman',
        'agenda' => 'Agenda & Kegiatan',
        'galeri' => 'Galeri & Video',
        'faq' => 'FAQ',
        'kontak' => 'Kontak',
    ];

    /**
     * Jenis konten yang hanya memiliki satu halaman.
     *
     * @var array<int, string>
     */
    public const SINGLE_PAGE_TYPES = [
        'home',
        'profil',
        'visi_misi',
        'sambutan',
        'kontak',
    ];

    protected $fillable = [
        'published_revision_id',
        'content_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'content_item_id');
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(ContentRevision::class, 'content_item_id')
            ->latestOfMany('revision_number');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(
            ContentRevision::class,
            'published_revision_id'
        );
    }

    /**
     * Konten yang tampil di website publik.
     *
     * Item harus memiliki revisi terbit dan tidak diarsipkan. Revisi draf
     * yang sedang disusun tidak menghapus versi yang sudah terbit.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_revision_id')
            ->where('status', '!=', ContentStatus::Archived->value)
            ->whereHas('publishedRevision', fn (Builder $revision) => $revision
                ->where('workflow_status', ContentStatus::Published->value));
    }

    public function isSinglePage(): bool
    {
        return in_array($this->content_type, self::SINGLE_PAGE_TYPES, true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->content_type] ?? $this->content_type;
    }
}
