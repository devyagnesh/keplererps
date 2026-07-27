<?php

namespace App\Services;

use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Category master business logic.
 */
class CategoryService
{
    public function __construct(protected CategoryRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): mixed
    {
        $data['code'] = strtoupper(trim((string) $data['code']));

        return $this->repository->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): mixed
    {
        $data['code'] = strtoupper(trim((string) $data['code']));
        if (! empty($data['parent_id']) && (int) $data['parent_id'] === $id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be its own parent.',
            ]);
        }

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->findById($id);
        if ($record->has_transactions) {
            throw ValidationException::withMessages([
                'category' => 'This category is referenced by transactions and cannot be deleted.',
            ]);
        }
        if ($this->repository->hasChildren($id)) {
            throw ValidationException::withMessages([
                'category' => 'A parent category cannot be deleted while children exist.',
            ]);
        }

        return $this->repository->delete($id);
    }
}
