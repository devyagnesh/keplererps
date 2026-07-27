<?php

namespace App\Repositories\Eloquent;

use App\Models\DocumentNumberSeries;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\DocumentNumberSeriesRepositoryInterface;

/**
 * Eloquent document number series repository.
 */
class DocumentNumberSeriesRepository implements DocumentNumberSeriesRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): DocumentNumberSeries
    {
        return DocumentNumberSeries::query()
            ->with(['financialYear:id,code', 'branch:id,code,name'])
            ->findOrFail($id);
    }

    public function create(array $data): DocumentNumberSeries
    {
        return DocumentNumberSeries::query()->create($data);
    }

    public function update(int $id, array $data): DocumentNumberSeries
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh(['financialYear', 'branch']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            DocumentNumberSeries::query()->with(['financialYear:id,code', 'branch:id,code,name']),
            ['id', 'document_type', 'prefix', 'next_number', 'is_active', 'created_at'],
            ['prefix', 'suffix'],
            function (DocumentNumberSeries $series): array {
                return [
                    'id' => $series->id,
                    'document_type' => $series->document_type->label(),
                    'prefix' => $series->prefix,
                    'fy' => $series->financialYear?->code ?? 'All',
                    'branch' => $series->branch?->code ?? 'All',
                    'next_number' => $series->formatNumber((int) $series->next_number, $series->financialYear?->code),
                    'is_active' => $series->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.document-series.partials.actions', ['series' => $series])->render(),
                ];
            },
            $params
        );
    }
}
