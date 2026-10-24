<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Melihat daftar permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Melihat detail permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Menambah permission baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Mengubah permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Menghapus permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermission('manage_roles');
    }
}
