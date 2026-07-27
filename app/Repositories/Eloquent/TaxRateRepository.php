<?php

namespace App\Repositories\Eloquent;

use App\Models\TaxRate;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\TaxRateRepositoryInterface;

/**
 * Eloquent tax rate repository.
 */
class TaxRateRepository implements TaxRateRepositoryInterface
{
    use BuildsServerSideDataTable;

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): TaxRate
    {
        return TaxRate::query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): TaxRate
    {
        return TaxRate::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): TaxRate
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            TaxRate::query(),
            ['id', 'code', 'name', 'igst_rate', 'is_active', 'created_at'],
            ['code', 'name'],
            function (TaxRate $tax): array {
                return [
                    'id' => $tax->id,
                    'code' => $tax->code,
                    'name' => e($tax->name),
                    'cgst_rate' => $tax->cgst_rate,
                    'sgst_rate' => $tax->sgst_rate,
                    'igst_rate' => $tax->igst_rate,
                    'is_active' => $tax->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.tax-rates.partials.actions', ['taxRate' => $tax])->render(),
                ];
            },
            $params
        );
    }
}
