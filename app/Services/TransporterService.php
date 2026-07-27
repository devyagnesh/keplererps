<?php

namespace App\Services;

use App\Repositories\Interfaces\TransporterRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Transporter master business logic.
 */
class TransporterService
{
    public function __construct(protected TransporterRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): mixed
    {
        return $this->repository->create($this->normalize($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): mixed
    {
        return $this->repository->update($id, $this->normalize($data));
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->findById($id);
        if ($record->has_transactions) {
            throw ValidationException::withMessages([
                'transporter' => 'This transporter is used on documents and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        $data['code'] = strtoupper(trim((string) $data['code']));
        if (! empty($data['gstin'])) {
            $data['gstin'] = strtoupper((string) $data['gstin']);
        } else {
            $data['gstin'] = null;
        }
        if (! empty($data['email'])) {
            $data['email'] = strtolower(trim((string) $data['email']));
        }

        return $data;
    }
}
