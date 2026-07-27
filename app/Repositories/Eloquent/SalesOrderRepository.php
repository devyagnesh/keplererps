<?php

namespace App\Repositories\Eloquent;

use App\Models\SalesOrder;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\SalesOrderRepositoryInterface;

class SalesOrderRepository implements SalesOrderRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): SalesOrder
    {
        return SalesOrder::query()
            ->with([
                'customer:id,party_code,party_name,party_type,status,billing_state_id,credit_limit,unlimited_credit',
                'warehouse:id,code,name',
                'placeOfSupplyState:id,code,name',
                'quotation:id,document_no,status',
                'items.item:id,item_code,item_name,selling_price,gst_rate,stock_uom_id,is_sellable',
                'items.uom:id,code,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): SalesOrder
    {
        return SalesOrder::query()->create($data);
    }

    public function update(int $id, array $data): SalesOrder
    {
        SalesOrder::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) SalesOrder::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = SalesOrder::query()->with(['customer:id,party_code,party_name', 'warehouse:id,code,name']);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'customer_id', 'status', 'grand_total', 'created_at'],
            ['document_no', 'customer_po_no'],
            function (SalesOrder $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'customer' => $doc->customer ? $doc->customer->party_code.' — '.$doc->customer->party_name : '—',
                    'warehouse' => $doc->warehouse?->name ?? '—',
                    'status' => $doc->status->label(),
                    'grand_total' => number_format((float) $doc->grand_total, 2),
                    'action' => view('admin.sales-orders.partials.actions', ['salesOrder' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
