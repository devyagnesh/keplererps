<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseOrder;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\PurchaseOrderRepositoryInterface;

/**
 * Eloquent purchase order repository.
 */
class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with([
                'supplier:id,party_code,party_name,party_type',
                'warehouse:id,code,name',
                'items.item:id,item_code,item_name,stock_uom_id,gst_rate,tracking_type,is_purchasable,standard_cost',
                'items.uom:id,code,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::query()->create($data);
    }

    public function update(int $id, array $data): PurchaseOrder
    {
        $record = PurchaseOrder::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) PurchaseOrder::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = PurchaseOrder::query()->with([
            'supplier:id,party_code,party_name',
            'warehouse:id,code,name',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where('supplier_id', (int) $params['supplier_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'supplier_id', 'status', 'grand_total', 'created_at'],
            ['document_no', 'remarks'],
            function (PurchaseOrder $po): array {
                return [
                    'id' => $po->id,
                    'document_no' => $po->document_no,
                    'document_date' => $po->document_date?->format('Y-m-d'),
                    'supplier' => $po->supplier
                        ? $po->supplier->party_code.' — '.$po->supplier->party_name
                        : '—',
                    'warehouse' => $po->warehouse?->name ?? '—',
                    'status' => $po->status->label(),
                    'grand_total' => number_format((float) $po->grand_total, 2),
                    'action' => view('admin.purchase-orders.partials.actions', ['purchaseOrder' => $po])->render(),
                ];
            },
            $params
        );
    }
}
