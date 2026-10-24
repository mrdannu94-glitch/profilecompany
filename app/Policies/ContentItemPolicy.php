<?php

namespace App\Policies;

use App\Models\ContentItem;
use App\Models\User;

/**
 * Otorisasi pengelolaan konten CMS.
 *
 * Guru dan staff hanya dapat mengubah draf miliknya sendiri. Keputusan
 * review dan publikasi dipisahkan melalui permission tersendiri sehingga
 * penulis tidak dapat menerbitkan kontennya tanpa persetujuan.
 */
class ContentItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission([
            'manage_content',
            'create_content_draft',
            'review_content',
            'publish_content',
        ]);
    }

    public function view(User $user, ContentItem $item): bool
    {
        return $user->hasPermission('manage_content')
            || $item->revisions()->where('author_id', $user->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(['manage_content', 'create_content_draft']);
    }

    public function update(User $user, ContentItem $item): bool
    {
        if ($user->hasPermission('manage_content')) {
            return true;
        }

        return $user->hasPermission('create_content_draft')
            && $item->latestRevision()->where('author_id', $user->getKey())->exists();
    }

    public function submit(User $user, ContentItem $item): bool
    {
        return $user->hasPermission('submit_content_review')
            && ($user->hasPermission('manage_content')
                || $item->latestRevision()->where('author_id', $user->getKey())->exists());
    }

    public function review(User $user, ContentItem $item): bool
    {
        return $user->hasPermission('review_content');
    }

    public function publish(User $user, ContentItem $item): bool
    {
        return $user->hasPermission('publish_content');
    }

    public function archive(User $user, ContentItem $item): bool
    {
        return $user->hasPermission(['manage_content', 'publish_content']);
    }
}
