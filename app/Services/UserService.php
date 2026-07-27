<?php

namespace App\Services;

use App\Enums\DataScopeType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDataScope;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * User management business logic (M02).
 */
class UserService
{
    public function __construct(protected UserRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): User
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $roleIds = $data['role_ids'] ?? [];
            $scope = [
                'scope_type' => $data['scope_type'] ?? DataScopeType::All->value,
                'branch_ids' => $data['scope_branch_ids'] ?? null,
                'warehouse_ids' => $data['scope_warehouse_ids'] ?? null,
                'team_user_ids' => $data['scope_team_user_ids'] ?? null,
            ];
            unset(
                $data['role_ids'],
                $data['scope_type'],
                $data['scope_branch_ids'],
                $data['scope_warehouse_ids'],
                $data['scope_team_user_ids'],
                $data['password_confirmation']
            );

            $this->assertCanAssignRoles($actor, $roleIds);
            $data['username'] = strtolower(trim((string) $data['username']));
            $data['email'] = strtolower(trim((string) $data['email']));
            $data['mobile'] = preg_replace('/[\s\-]/', '', (string) $data['mobile']);
            $data['must_change_password'] = true;

            $user = $this->repository->create($data);
            $user->syncRoles($roleIds);
            $this->upsertScope($user, $scope);

            return $user->fresh(['roles', 'branch', 'dataScope']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $actor): User
    {
        return DB::transaction(function () use ($id, $data, $actor): User {
            $user = $this->repository->findById($id);

            if ($actor->id === $user->id) {
                unset($data['role_ids'], $data['scope_type'], $data['scope_branch_ids'], $data['scope_warehouse_ids'], $data['scope_team_user_ids']);
            }

            $roleIds = $data['role_ids'] ?? $user->roles->pluck('id')->all();
            $scope = [
                'scope_type' => $data['scope_type'] ?? $user->dataScope?->scope_type?->value ?? DataScopeType::All->value,
                'branch_ids' => $data['scope_branch_ids'] ?? $user->dataScope?->branch_ids,
                'warehouse_ids' => $data['scope_warehouse_ids'] ?? $user->dataScope?->warehouse_ids,
                'team_user_ids' => $data['scope_team_user_ids'] ?? $user->dataScope?->team_user_ids,
            ];
            unset(
                $data['role_ids'],
                $data['scope_type'],
                $data['scope_branch_ids'],
                $data['scope_warehouse_ids'],
                $data['scope_team_user_ids'],
                $data['password_confirmation'],
                $data['username']
            );

            if ($actor->id !== $user->id) {
                $this->assertCanAssignRoles($actor, $roleIds);
                $this->assertNotRemovingLastSuperAdmin($user, $roleIds, $data);
            }

            if (empty($data['password'])) {
                unset($data['password']);
            }

            if (isset($data['email'])) {
                $data['email'] = strtolower(trim((string) $data['email']));
            }
            if (isset($data['mobile'])) {
                $data['mobile'] = preg_replace('/[\s\-]/', '', (string) $data['mobile']);
            }

            $updated = $this->repository->update($id, $data);

            if ($actor->id !== $user->id) {
                $updated->syncRoles($roleIds);
                $this->upsertScope($updated, $scope);
            }

            $updated->forgetPermissionCache();

            return $updated->fresh(['roles', 'branch', 'dataScope']);
        });
    }

    public function delete(int $id, User $actor): bool
    {
        $user = $this->repository->findById($id);
        if ($actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->hasRole('super-admin') && $this->repository->countActiveSuperAdmins($user->id) < 1) {
            throw ValidationException::withMessages([
                'user' => 'The system must always have at least one active Super Admin.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Effective permissions view for a user.
     *
     * @return list<array{name: string, source: string}>
     */
    public function effectivePermissions(User $user): array
    {
        $user->loadMissing(['roles.permissions', 'permissions']);
        $items = [];

        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $items[$permission->name] = [
                    'name' => $permission->name,
                    'label' => $permission->label,
                    'source' => 'Role: '.$role->name,
                ];
            }
        }

        foreach ($user->permissions as $permission) {
            $items[$permission->name] = [
                'name' => $permission->name,
                'label' => $permission->label,
                'source' => $permission->pivot->is_deny ? 'Direct deny' : 'Direct grant',
            ];
        }

        ksort($items);

        return array_values($items);
    }

    /**
     * @param  list<int>  $roleIds
     */
    protected function assertCanAssignRoles(User $actor, array $roleIds): void
    {
        $superAdminRoleId = Role::query()->where('slug', 'super-admin')->value('id');
        if ($superAdminRoleId && in_array((int) $superAdminRoleId, array_map('intval', $roleIds), true) && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'role_ids' => 'Only Super Admin may assign the Super Admin role.',
            ]);
        }
    }

    /**
     * @param  list<int>  $roleIds
     * @param  array<string, mixed>  $data
     */
    protected function assertNotRemovingLastSuperAdmin(User $user, array $roleIds, array $data): void
    {
        $wasSuper = $user->hasRole('super-admin');
        $superAdminRoleId = (int) Role::query()->where('slug', 'super-admin')->value('id');
        $stillSuper = in_array($superAdminRoleId, array_map('intval', $roleIds), true);
        $willDeactivate = array_key_exists('is_active', $data) && ! $data['is_active'];

        if ($wasSuper && (! $stillSuper || $willDeactivate) && $this->repository->countActiveSuperAdmins($user->id) < 1) {
            throw ValidationException::withMessages([
                'role_ids' => 'The system must always have at least one active Super Admin.',
            ]);
        }
    }

    /**
     * @param  array{scope_type: string, branch_ids: mixed, warehouse_ids: mixed, team_user_ids: mixed}  $scope
     */
    protected function upsertScope(User $user, array $scope): void
    {
        UserDataScope::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'scope_type' => $scope['scope_type'],
                'branch_ids' => $scope['branch_ids'] ?: null,
                'warehouse_ids' => $scope['warehouse_ids'] ?: null,
                'team_user_ids' => $scope['team_user_ids'] ?: null,
            ]
        );
    }
}
