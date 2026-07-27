<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for branch master (M01).
 */
class BranchService
{
    public function __construct(
        protected BranchRepositoryInterface $repository
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Branch
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data): Branch {
            $data['code'] = strtoupper(trim((string) $data['code']));
            if (! empty($data['is_head_office'])) {
                $this->clearOtherHeadOffices();
            }

            return $this->repository->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Branch
    {
        return DB::transaction(function () use ($id, $data): Branch {
            $data['code'] = strtoupper(trim((string) $data['code']));
            if (! empty($data['is_head_office'])) {
                $this->clearOtherHeadOffices($id);
            }

            return $this->repository->update($id, $data);
        });
    }

    /**
     * Soft-delete a branch when it has no warehouses.
     *
     * @throws ValidationException
     */
    public function delete(int $id): bool
    {
        $branch = $this->repository->findById($id);

        if ($branch->warehouses()->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'This branch has warehouses and cannot be deleted. Deactivate it instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Ensure only one head office flag is set.
     */
    protected function clearOtherHeadOffices(?int $exceptId = null): void
    {
        Branch::query()
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('is_head_office', true)
            ->update(['is_head_office' => false]);
    }
}
