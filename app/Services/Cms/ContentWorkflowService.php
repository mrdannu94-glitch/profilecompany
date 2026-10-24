<?php

namespace App\Services\Cms;

use App\Enums\ContentStatus;
use App\Exceptions\Cms\ContentWorkflowException;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alur kerja konten CMS: draft -> review -> publish.
 *
 * Seluruh transisi berjalan dalam transaksi dan mencatat pelaku. Revisi yang
 * sudah terbit tidak diubah di tempat; penyuntingan membuat revisi baru
 * berstatus draf sehingga versi publik tetap tersedia sampai revisi baru
 * disetujui dan diterbitkan.
 */
class ContentWorkflowService
{
    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Buat konten baru beserta revisi draf pertamanya.
     *
     * @param  array{content_type: string, title: string, slug?: string|null, excerpt?: string|null, body?: string|null}  $data
     */
    public function create(User $author, array $data): ContentItem
    {
        return DB::transaction(function () use ($author, $data): ContentItem {
            $contentType = $data['content_type'];

            if (in_array($contentType, ContentItem::SINGLE_PAGE_TYPES, true)
                && ContentItem::query()->where('content_type', $contentType)->exists()) {
                throw new ContentWorkflowException('Halaman ini sudah ada. Sunting halaman tersebut alih-alih membuat yang baru.');
            }

            $item = ContentItem::create([
                'content_type' => $contentType,
                'status' => ContentStatus::Draft,
            ]);

            $revision = $item->revisions()->create([
                'author_id' => $author->getKey(),
                'revision_number' => 1,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'workflow_status' => ContentStatus::Draft,
            ]);

            $this->audit->log(
                action: 'cms.content.created',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $author,
                description: 'Draf konten dibuat.',
                context: [
                    'content_type' => $contentType,
                    'revision_id' => $revision->getKey(),
                    'slug' => $revision->slug,
                ],
            );

            return $item;
        });
    }

    /**
     * Simpan perubahan dari penulis.
     *
     * Revisi yang masih draf/perlu revisi diperbarui. Bila revisi terakhir
     * sudah terbit, dibuat revisi draf baru tanpa mengubah versi publik.
     *
     * @param  array{title: string, slug?: string|null, excerpt?: string|null, body?: string|null}  $data
     */
    public function update(ContentItem $item, User $actor, array $data): ContentRevision
    {
        return DB::transaction(function () use ($item, $actor, $data): ContentRevision {
            $latest = $item->latestRevision()->first();

            if (! $latest instanceof ContentRevision) {
                throw new ContentWorkflowException('Konten belum memiliki revisi.');
            }

            if ($latest->isEditable()) {
                $latest->fill([
                    'title' => $data['title'],
                    'slug' => $this->uniqueSlug($data['slug'] ?? $data['title'], $latest->getKey()),
                    'excerpt' => $data['excerpt'] ?? null,
                    'body' => $data['body'] ?? null,
                ])->save();

                $item->forceFill(['status' => $latest->workflow_status])->save();

                $this->audit->log(
                    action: 'cms.content.updated',
                    entityType: ContentItem::class,
                    entityId: $item->getKey(),
                    actor: $actor,
                    description: 'Draf konten diperbarui.',
                    context: ['revision_id' => $latest->getKey(), 'slug' => $latest->slug],
                );

                return $latest->refresh();
            }

            if ($latest->workflow_status !== ContentStatus::Published) {
                throw new ContentWorkflowException('Konten sedang ditinjau atau menunggu publikasi sehingga belum dapat diubah.');
            }

            $revision = $item->revisions()->create([
                'author_id' => $actor->getKey(),
                'revision_number' => $latest->revision_number + 1,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'workflow_status' => ContentStatus::Draft,
            ]);

            $item->forceFill(['status' => ContentStatus::Draft])->save();

            $this->audit->log(
                action: 'cms.content.revision_created',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $actor,
                description: 'Revisi draf baru dibuat dari konten terbit.',
                context: [
                    'revision_id' => $revision->getKey(),
                    'previous_revision_id' => $latest->getKey(),
                ],
            );

            return $revision;
        });
    }

    /**
     * Ajukan revisi terakhir untuk ditinjau.
     */
    public function submit(ContentItem $item, User $actor): ContentRevision
    {
        return DB::transaction(function () use ($item, $actor): ContentRevision {
            $latest = $this->requireLatest($item);

            if (! $latest->workflow_status->canBeSubmitted()) {
                throw new ContentWorkflowException('Hanya draf atau konten yang perlu revisi yang dapat diajukan.');
            }

            $latest->forceFill([
                'workflow_status' => ContentStatus::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $actor->getKey(),
            ])->save();

            $item->forceFill(['status' => ContentStatus::Submitted])->save();

            $this->audit->log(
                action: 'cms.content.submitted',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $actor,
                description: 'Konten diajukan untuk review.',
                context: ['revision_id' => $latest->getKey()],
            );

            return $latest->refresh();
        });
    }

    /**
     * Catat keputusan review: setujui atau minta revisi.
     */
    public function review(ContentItem $item, User $reviewer, bool $approve, ?string $note = null): ContentRevision
    {
        return DB::transaction(function () use ($item, $reviewer, $approve, $note): ContentRevision {
            $latest = $this->requireLatest($item);

            if ($latest->workflow_status !== ContentStatus::Submitted) {
                throw new ContentWorkflowException('Hanya konten berstatus menunggu review yang dapat diperiksa.');
            }

            if (! $approve && ($note === null || trim($note) === '')) {
                throw new ContentWorkflowException('Catatan revisi wajib diisi saat mengembalikan konten.');
            }

            $status = $approve ? ContentStatus::Approved : ContentStatus::NeedsRevision;

            $latest->forceFill([
                'workflow_status' => $status,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
                'review_note' => $note,
            ])->save();

            $latest->reviews()->create([
                'reviewer_id' => $reviewer->getKey(),
                'action' => $approve ? 'approve' : 'request_revision',
                'note' => $note,
                'reviewed_at' => now(),
            ]);

            $item->forceFill(['status' => $status])->save();

            $this->audit->log(
                action: $approve ? 'cms.content.approved' : 'cms.content.revision_requested',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $reviewer,
                description: $approve ? 'Konten disetujui untuk publikasi.' : 'Konten dikembalikan untuk revisi.',
                context: ['revision_id' => $latest->getKey(), 'note' => $note],
            );

            return $latest->refresh();
        });
    }

    /**
     * Terbitkan revisi yang sudah disetujui.
     */
    public function publish(ContentItem $item, User $publisher): ContentRevision
    {
        return DB::transaction(function () use ($item, $publisher): ContentRevision {
            $latest = $this->requireLatest($item);

            if (! $latest->workflow_status->canBePublished()) {
                throw new ContentWorkflowException('Konten harus disetujui sebelum dapat diterbitkan.');
            }

            $latest->forceFill([
                'workflow_status' => ContentStatus::Published,
                'published_at' => now(),
            ])->save();

            $latest->reviews()->create([
                'reviewer_id' => $publisher->getKey(),
                'action' => 'publish',
                'note' => null,
                'reviewed_at' => now(),
            ]);

            $item->forceFill([
                'status' => ContentStatus::Published,
                'published_revision_id' => $latest->getKey(),
            ])->save();

            $this->audit->log(
                action: 'cms.content.published',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $publisher,
                description: 'Konten diterbitkan ke website publik.',
                context: ['revision_id' => $latest->getKey(), 'slug' => $latest->slug],
            );

            return $latest->refresh();
        });
    }

    /**
     * Sembunyikan konten dari website publik tanpa menghapus data.
     */
    public function archive(ContentItem $item, User $actor): ContentItem
    {
        return DB::transaction(function () use ($item, $actor): ContentItem {
            $item->forceFill(['status' => ContentStatus::Archived])->save();

            $this->audit->log(
                action: 'cms.content.archived',
                entityType: ContentItem::class,
                entityId: $item->getKey(),
                actor: $actor,
                description: 'Konten diarsipkan.',
                context: ['published_revision_id' => $item->published_revision_id],
            );

            return $item->refresh();
        });
    }

    protected function requireLatest(ContentItem $item): ContentRevision
    {
        $latest = $item->latestRevision()->first();

        if (! $latest instanceof ContentRevision) {
            throw new ContentWorkflowException('Konten belum memiliki revisi.');
        }

        return $latest;
    }

    /**
     * Slug unik lintas revisi agar URL publik tidak bertabrakan.
     */
    protected function uniqueSlug(string $value, ?int $ignoreRevisionId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'konten';
        }

        $candidate = $base;
        $suffix = 2;

        while (ContentRevision::query()
            ->where('slug', $candidate)
            ->when($ignoreRevisionId !== null, fn ($query) => $query->whereKeyNot($ignoreRevisionId))
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
