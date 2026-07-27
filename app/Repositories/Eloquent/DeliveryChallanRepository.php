<?php

namespace App\Repositories\Eloquent;

use App\Models\DeliveryChallan;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\DeliveryChallanRepositoryInterface;

/**
 * Eloquent delivery challan repository (M12).
 */
class DeliveryChallanRepository implements DeliveryChallanRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): DeliveryChallan
    {
        return DeliveryChallan::query()
            ->with([
                'salesOrder:id,document_no,document_date,status,grand_total',
                'customer:id,party_code,party_name',
                'warehouse:id,code,name',
                'transporter:id,code,name,gstin',
                'items.item:id,item_code,item_name,tracking_type',
                'items.uom:id,code,name',
                'items.salesOrderItem',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): DeliveryChallan
    {
        return DeliveryChallan::query()->create($data);
    }

    public function update(int $id, array $data): DeliveryChallan
    {
        DeliveryChallan::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) DeliveryChallan::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = DeliveryChallan::query()->with([
            'customer:id,party_code,party_name',
            'salesOrder:id,document_no',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'sales_order_id', 'status', 'vehicle_number', 'created_at'],
            ['document_no', 'vehicle_number', 'lr_number'],
            function (DeliveryChallan $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'sales_order' => $doc->salesOrder?->document_no ?? '—',
                    'customer' => $doc->customer ? $doc->customer->party_code.' — '.$doc->customer->party_name : '—',
                    'vehicle_number' => $doc->vehicle_number ?: '—',
                    'status' => $doc->status->label(),
                    'action' => view('admin.delivery-challans.partials.actions', ['challan' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
