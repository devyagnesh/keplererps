<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductionEntry;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\ProductionEntryRepositoryInterface;

/**
 * Eloquent production entry repository (M09).
 */
class ProductionEntryRepository implements ProductionEntryRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): ProductionEntry
    {
        return ProductionEntry::query()
            ->with([
                'workOrder.item:id,item_code,item_name',
                'defectReason:id,code,name',
                'materials.item:id,item_code,item_name',
                'operator:id,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): ProductionEntry
    {
        return ProductionEntry::query()->create($data);
    }

    public function update(int $id, array $data): ProductionEntry
    {
        ProductionEntry::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) ProductionEntry::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = ProductionEntry::query()->with([
            'workOrder:id,document_no,item_id',
            'workOrder.item:id,item_code,item_name',
        ]);

        if (! empty($params['work_order_id'])) {
            $query->where('work_order_id', $params['work_order_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'work_order_id', 'good_quantity', 'rejected_quantity', 'posted_at', 'created_at'],
            ['document_no'],
            function (ProductionEntry $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'work_order' => $doc->workOrder?->document_no ?? '—',
                    'item' => $doc->workOrder?->item
                        ? $doc->workOrder->item->item_code.' — '.$doc->workOrder->item->item_name
                        : '—',
                    'good_quantity' => number_format((float) $doc->good_quantity, 4),
                    'rejected_quantity' => number_format((float) $doc->rejected_quantity, 4),
                    'status' => $doc->posted_at ? 'Posted' : 'Draft',
                    'action' => view('admin.production-entries.partials.actions', ['entry' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
