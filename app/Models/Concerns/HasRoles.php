<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Kemampuan memeriksa role dan permission milik pengguna.
 *
 * Role dan permission dibaca dari database, tidak pernah dari input pengguna.
 * Permission diwarisi melalui role: pengguna memiliki permission bila salah
 * satu role-nya terikat pada permission tersebut.
 */
trait HasRoles
{
    /**
     * Role yang dimiliki pengguna.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Kumpulan permission yang diwarisi dari seluruh role pengguna.
     *
     * @return Collection<int, Permission>
     */
    public function permissionCollection(): Collection
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->values();
    }

    /**
     * Nama-nama permission milik pengguna.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return $this->permissionCollection()->pluck('name')->values();
    }

    /**
     * Periksa apakah pengguna memiliki salah satu role yang diberikan.
     *
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $names = (array) $roles;

        if ($names === []) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role) => in_array($role->name, $names, true));
        }

        return $this->roles()->whereIn('name', $names)->exists();
    }

    /**
     * Periksa apakah pengguna memiliki seluruh role yang diberikan.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        if ($roles === []) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            $owned = $this->roles->pluck('name')->all();

            return array_diff($roles, $owned) === [];
        }

        $owned = $this->roles()->whereIn('name', $roles)->pluck('name')->all();

        return array_diff($roles, $owned) === [];
    }

    /**
     * Periksa apakah pengguna memiliki salah satu permission yang diberikan.
     *
     * @param  string|array<int, string>  $permissions
     */
    public function hasPermission(string|array $permissions): bool
    {
        $names = (array) $permissions;

        if ($names === []) {
            return false;
        }

        return $this->permissionNames()->intersect($names)->isNotEmpty();
    }

    /**
     * Periksa apakah pengguna memiliki seluruh permission yang diberikan.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($permissions === []) {
            return true;
        }

        $owned = $this->permissionNames()->all();

        return array_diff($permissions, $owned) === [];
    }

    /**
     * Batasi kueri pada pengguna yang memiliki role tertentu.
     *
     * @param  array<int, string>  $roles
     */
    public function scopeWithRole(Builder $query, array $roles): Builder
    {
        return $query->whereHas('roles', fn (Builder $builder) => $builder->whereIn('name', $roles));
    }

    /**
     * Batasi kueri pada pengguna yang memiliki permission tertentu.
     *
     * @param  array<int, string>  $permissions
     */
    public function scopeWithPermission(Builder $query, array $permissions): Builder
    {
        return $query->whereHas(
            'roles.permissions',
            fn (Builder $builder) => $builder->whereIn('name', $permissions)
        );
    }
}
