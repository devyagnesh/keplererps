<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Role and permission matrix business logic (M02).
 */
class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $repository,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Role
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $permissionIds = $data['permission_ids'] ?? [];
            unset($data['permission_ids']);
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $role = $this->repository->create($data);
            $role->permissions()->sync($permissionIds);
            $this->forgetUsersPermissionCache($role);

            $names = Permission::query()->whereIn('id', $permissionIds)->pluck('name')->sort()->values()->all();
            $this->activityLog->log(
                event: 'role_created',
                description: "Role \"{$role->name}\" created.",
                subject: $role,
                properties: ['permissions_added' => $names, 'permissions_removed' => []],
                logName: 'roles'
            );

            return $role->fresh('permissions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Role
    {
        return DB::transaction(function () use ($id, $data): Role {
            $role = $this->repository->findById($id);
            if ($role->is_system) {
                unset($data['name'], $data['slug'], $data['is_system']);
            }

            $permissionIds = $data['permission_ids'] ?? [];
            unset($data['permission_ids']);

            if ($role->is_system && $role->slug === 'super-admin') {
                $allIds = Permission::query()->pluck('id')->all();
                if (count(array_diff($allIds, $permissionIds)) > 0) {
                    throw ValidationException::withMessages([
                        'permission_ids' => 'Super Admin permissions cannot be reduced.',
                    ]);
                }
                $permissionIds = $allIds;
            }

            $before = $role->permissions()->pluck('name')->sort()->values()->all();

            $updated = $this->repository->update($id, $data);
            $updated->permissions()->sync($permissionIds);
            $this->forgetUsersPermissionCache($updated);

            $after = Permission::query()->whereIn('id', $permissionIds)->pluck('name')->sort()->values()->all();
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            if ($added !== [] || $removed !== []) {
                $this->activityLog->log(
                    event: 'permissions_changed',
                    description: "Permissions changed on role \"{$updated->name}\".",
                    subject: $updated,
                    properties: [
                        'permissions_added' => $added,
                        'permissions_removed' => $removed,
                    ],
                    logName: 'roles'
                );
            }

            return $updated->fresh('permissions');
        });
    }

    public function delete(int $id): bool
    {
        $role = $this->repository->findById($id);
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'System roles cannot be deleted.',
            ]);
        }
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'This role is assigned to users and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Duplicate a role with "(Copy)" suffix.
     */
    public function copy(int $id): Role
    {
        $source = $this->repository->findById($id);

        return $this->create([
            'name' => $source->name.' (Copy)',
            'slug' => Str::slug($source->name.'-copy-'.Str::random(4)),
            'description' => $source->description,
            'level' => $source->level,
            'require_2fa' => $source->require_2fa,
            'simplified_ui' => $source->simplified_ui,
            'is_active' => true,
            'is_system' => false,
            'permission_ids' => $source->permissions->pluck('id')->all(),
        ]);
    }

    /**
     * Permissions grouped by module for the matrix UI.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Permission>>
     */
    public function permissionsGrouped()
    {
        return Permission::query()
            ->orderBy('module_group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module_group');
    }

    protected function forgetUsersPermissionCache(Role $role): void
    {
        $role->users()->each(function (User $user): void {
            $user->forgetPermissionCache();
        });
    }
}
