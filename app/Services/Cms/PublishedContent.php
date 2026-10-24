<?php

namespace App\Services\Cms;

use App\Enums\ContentStatus;
use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pembacaan konten yang sudah terbit untuk website publik.
 *
 * Hanya revisi yang benar-benar menjadi `published_revision_id` milik item
 * yang ditampilkan. Draf yang sedang disusun, konten yang menunggu review,
 * dan item yang diarsipkan tidak pernah bocor ke halaman publik.
 */
class PublishedContent
{
    public function single(string $type): ?ContentRevision
    {
        return $this->baseQuery($type)->first();
    }

    /**
     * @return Collection<int, ContentRevision>
     */
    public function items(string $type, int $limit = 0): Collection
    {
        $query = $this->baseQuery($type);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function find(string $type, string $slug): ?ContentRevision
    {
        return $this->baseQuery($type)->where('slug', $slug)->first();
    }

    /**
     * Sorotan lintas jenis untuk beranda.
     *
     * @param  array<int, string>  $types
     * @return Collection<int, ContentRevision>
     */
    public function latest(array $types, int $limit = 3): Collection
    {
        return ContentRevision::query()
            ->published()
            ->with(['contentItem', 'media'])
            ->whereHas('contentItem', fn (Builder $item) => $this->constrainPublishedItem($item, $types))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<ContentRevision>
     */
    protected function baseQuery(string $type): Builder
    {
        return ContentRevision::query()
            ->published()
            ->with(['contentItem', 'media'])
            ->whereHas('contentItem', fn (Builder $item) => $this->constrainPublishedItem($item, [$type]))
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array<int, string>  $types
     */
    protected function constrainPublishedItem(Builder $item, array $types): Builder
    {
        return $item
            ->whereIn('content_type', $types)
            ->whereNotNull('published_revision_id')
            ->where('status', '!=', ContentStatus::Archived->value)
            ->whereColumn('content_items.published_revision_id', 'content_revisions.id');
    }
}
