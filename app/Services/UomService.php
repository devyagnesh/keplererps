<?php

namespace App\Services;

use App\Repositories\Interfaces\UomRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * UOM master business logic.
 */
class UomService
{
    public function __construct(protected UomRepositoryInterface $repository) {}

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

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->findById($id);
        if ($record->has_transactions) {
            throw ValidationException::withMessages([
                'uom' => 'This UOM is used on transactions and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }
}
