<?php

namespace App\Enums;

/**
 * Visibilitas penyimpanan media.
 *
 * `private` berarti berkas hanya dapat diakses melalui endpoint yang
 * memeriksa otorisasi, bukan melalui URL publik.
 */
enum StorageVisibility: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Publik',
            self::Private => 'Privat',
        };
    }

    public function isPrivate(): bool
    {
        return $this === self::Private;
    }
}
