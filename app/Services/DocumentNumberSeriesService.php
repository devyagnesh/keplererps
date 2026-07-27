<?php

namespace App\Services;

use App\Models\DocumentNumberSeries;
use App\Repositories\Interfaces\DocumentNumberSeriesRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Document number series configuration CRUD.
 */
class DocumentNumberSeriesService
{
    public function __construct(
        protected DocumentNumberSeriesRepositoryInterface $repository,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): DocumentNumberSeries
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): DocumentNumberSeries
    {
        $data = $this->normalize($data);
        $data['next_number'] = $data['start_number'];

        return $this->repository->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): DocumentNumberSeries
    {
        $data = $this->normalize($data);
        unset($data['next_number']);

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $series = $this->repository->findById($id);
        if ((int) $series->next_number > (int) $series->start_number) {
            throw ValidationException::withMessages([
                'series' => 'This series has already issued numbers and cannot be deleted. Deactivate it instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    public function preview(int $id): string
    {
        $series = $this->repository->findById($id);

        return $series->formatNumber((int) $series->next_number, $series->financialYear?->code);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        $data['prefix'] = strtoupper(trim((string) $data['prefix']));
        $data['suffix'] = isset($data['suffix']) && $data['suffix'] !== ''
            ? trim((string) $data['suffix'])
            : null;
        $data['separator'] = $data['separator'] ?? '-';
        $data['include_fy_code'] = (bool) ($data['include_fy_code'] ?? false);
        $data['reset_yearly'] = (bool) ($data['reset_yearly'] ?? true);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['financial_year_id'] = ! empty($data['financial_year_id']) ? (int) $data['financial_year_id'] : null;
        $data['branch_id'] = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;

        return $data;
    }
}
