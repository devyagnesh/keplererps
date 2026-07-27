<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent role repository.
 */
class RoleRepository implements RoleRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Role
    {
        return Role::query()->with('permissions')->findOrFail($id);
    }

    public function create(array $data): Role
    {
        return Role::query()->create($data);
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->findById($id);
        $role->update($data);

        return $role->fresh('permissions');
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function activeOptions(): Collection
    {
        return Role::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            Role::query()->withCount('permissions'),
            ['id', 'name', 'slug', 'level', 'is_active', 'created_at'],
            ['name', 'slug'],
            function (Role $role): array {
                return [
                    'id' => $role->id,
                    'name' => e($role->name),
                    'slug' => $role->slug,
                    'permissions_count' => $role->permissions_count,
                    'is_system' => $role->is_system
                        ? '<span class="badge bg-warning-transparent">System</span>'
                        : '<span class="badge bg-light text-muted">Custom</span>',
                    'is_active' => $role->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.roles.partials.actions', ['role' => $role])->render(),
                ];
            },
            $params
        );
    }
}
