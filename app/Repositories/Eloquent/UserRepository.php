<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent user repository.
 */
class UserRepository implements UserRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): User
    {
        return User::query()->with(['roles', 'branch', 'dataScope'])->findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findById($id);
        $user->update($data);

        return $user->fresh(['roles', 'branch', 'dataScope']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function countActiveSuperAdmins(?int $exceptUserId = null): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('slug', 'super-admin'))
            ->when($exceptUserId !== null, fn (Builder $q) => $q->where('id', '!=', $exceptUserId))
            ->count();
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            User::query()->with(['roles:id,name', 'branch:id,name']),
            ['id', 'name', 'username', 'email', 'is_active', 'created_at'],
            ['name', 'username', 'email', 'mobile'],
            function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => e($user->name),
                    'username' => e((string) $user->username),
                    'email' => e($user->email),
                    'roles' => $user->roles->pluck('name')->implode(', ') ?: '—',
                    'branch' => $user->branch?->name ?? '—',
                    'is_active' => $user->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.users.partials.actions', ['user' => $user])->render(),
                ];
            },
            $params
        );
    }
}
