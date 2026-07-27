<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Role and permission helpers for authenticatable models.
 */
trait HasRolesAndPermissions
{
    /**
     * Roles assigned to the model.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    /**
     * Direct permission overrides (grants or denies).
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions')
            ->withPivot('is_deny');
    }

    /**
     * Whether the user holds a permission after role grants and direct overrides.
     */
    public function hasPermissionTo(string $permission): bool
    {
        return $this->getEffectivePermissionNames()->contains($permission);
    }

    /**
     * Whether the user holds any of the given permissions.
     *
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $effective = $this->getEffectivePermissionNames();

        foreach ($permissions as $permission) {
            if ($effective->contains($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user holds any of the given roles by slug.
     *
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->roles->contains(fn (Role $role) => in_array($role->slug, $roles, true));
    }

    /**
     * Effective permission names (role grants minus denies, plus direct grants).
     *
     * @return Collection<int, string>
     */
    public function getEffectivePermissionNames(): Collection
    {
        $cacheKey = 'user_permissions_'.$this->getKey();

        // Cache plain string lists — serializing Collection instances can yield
        // __PHP_Incomplete_Class after deploys / classmap changes (sidebar crash).
        $names = Cache::get($cacheKey);

        if (! is_array($names)) {
            $names = $this->resolveEffectivePermissionNameList();
            Cache::put($cacheKey, $names, now()->addMinutes(10));
        }

        return collect($names);
    }

    /**
     * Build the effective permission name list from roles and direct overrides.
     *
     * @return list<string>
     */
    protected function resolveEffectivePermissionNameList(): array
    {
        $this->loadMissing(['roles.permissions', 'permissions']);

        $fromRoles = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique();

        $denied = $this->permissions
            ->where('pivot.is_deny', true)
            ->pluck('name');

        $granted = $this->permissions
            ->where('pivot.is_deny', false)
            ->pluck('name');

        return $fromRoles
            ->merge($granted)
            ->unique()
            ->reject(fn (string $name) => $denied->contains($name))
            ->values()
            ->all();
    }

    /**
     * Clear cached effective permissions for this user.
     */
    public function forgetPermissionCache(): void
    {
        Cache::forget('user_permissions_'.$this->getKey());
    }

    /**
     * Sync roles and clear permission cache.
     *
     * @param  list<int>  $roleIds
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->forgetPermissionCache();
    }
}
