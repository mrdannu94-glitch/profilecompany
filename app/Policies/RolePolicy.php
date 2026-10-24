<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Melihat daftar role.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Melihat detail role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Menambah role baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Mengubah role beserta permissionnya.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('manage_roles');
    }

    /**
     * Menghapus role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('manage_roles');
    }
}
