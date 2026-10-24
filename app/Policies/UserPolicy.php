<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Melihat daftar pengguna.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_users');
    }

    /**
     * Melihat detail pengguna.
     */
    public function view(User $user, User $target): bool
    {
        return $user->hasPermission('manage_users') || $user->is($target);
    }

    /**
     * Menambah pengguna baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_users');
    }

    /**
     * Mengubah pengguna.
     */
    public function update(User $user, User $target): bool
    {
        return $user->hasPermission('manage_users');
    }

    /**
     * Menghapus pengguna.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission('manage_users') && ! $user->is($target);
    }

    /**
     * Mengubah role yang dimiliki pengguna.
     */
    public function assignRole(User $user, User $target): bool
    {
        return $user->hasPermission('manage_roles');
    }
}
