<?php

namespace App\Services;

use App\Models\HsnCode;
use App\Repositories\Interfaces\HsnCodeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * HSN / SAC master business logic.
 */
class HsnCodeService
{
    public function __construct(protected HsnCodeRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    /** @return Collection<int, HsnCode> */
    public function activeOptions(): Collection
    {
        return $this->repository->activeOptions();
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): HsnCode
    {
        $data['code'] = preg_replace('/\D+/', '', (string) $data['code']) ?? '';
        $data['code_type'] = strtolower((string) ($data['code_type'] ?? 'hsn'));

        return $this->repository->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): HsnCode
    {
        $data['code'] = preg_replace('/\D+/', '', (string) $data['code']) ?? '';
        $data['code_type'] = strtolower((string) ($data['code_type'] ?? 'hsn'));

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $hsn = $this->repository->findById($id);

        if ($hsn->items()->exists()) {
            throw ValidationException::withMessages([
                'hsn_code' => 'This HSN/SAC code is used by items and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }
}
