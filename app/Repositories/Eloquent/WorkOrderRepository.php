<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkOrder;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\WorkOrderRepositoryInterface;

/**
 * Eloquent work order repository (M09).
 */
class WorkOrderRepository implements WorkOrderRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): WorkOrder
    {
        return WorkOrder::query()
            ->with([
                'item:id,item_code,item_name,is_manufacturable,stock_uom_id,tracking_type,standard_cost',
                'bom:id,bom_number,version,item_id,output_quantity,overhead_percent,rolled_total_cost,is_active',
                'salesOrder:id,document_no,status,expected_delivery_date',
                'sourceWarehouse:id,code,name',
                'targetWarehouse:id,code,name',
                'workCentre:id,code,name',
                'components.item:id,item_code,item_name',
                'components.uom:id,code,name',
                'operations',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): WorkOrder
    {
        return WorkOrder::query()->create($data);
    }

    public function update(int $id, array $data): WorkOrder
    {
        WorkOrder::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) WorkOrder::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = WorkOrder::query()->with([
            'item:id,item_code,item_name',
            'sourceWarehouse:id,code,name',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'item_id', 'planned_quantity', 'status', 'planned_end_date', 'created_at'],
            ['document_no'],
            function (WorkOrder $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'item' => $doc->item ? $doc->item->item_code.' — '.$doc->item->item_name : '—',
                    'planned_quantity' => number_format((float) $doc->planned_quantity, 4),
                    'good_quantity' => number_format((float) $doc->good_quantity, 4),
                    'status' => $doc->status->label(),
                    'planned_end_date' => $doc->planned_end_date?->format('Y-m-d'),
                    'action' => view('admin.work-orders.partials.actions', ['workOrder' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
