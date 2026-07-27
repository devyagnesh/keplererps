<?php

namespace App\Repositories\Eloquent;

use App\Models\SalesInvoice;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\SalesInvoiceRepositoryInterface;

class SalesInvoiceRepository implements SalesInvoiceRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): SalesInvoice
    {
        return SalesInvoice::query()
            ->with([
                'salesOrder:id,document_no,status',
                'customer:id,party_code,party_name',
                'warehouse:id,code,name',
                'placeOfSupplyState:id,code,name',
                'items.item:id,item_code,item_name',
                'items.salesOrderItem',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): SalesInvoice
    {
        return SalesInvoice::query()->create($data);
    }

    public function update(int $id, array $data): SalesInvoice
    {
        SalesInvoice::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) SalesInvoice::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = SalesInvoice::query()->with([
            'customer:id,party_code,party_name',
            'salesOrder:id,document_no',
        ]);

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'sales_order_id', 'status', 'grand_total', 'created_at'],
            ['document_no'],
            function (SalesInvoice $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'sales_order' => $doc->salesOrder?->document_no ?? '—',
                    'customer' => $doc->customer ? $doc->customer->party_code.' — '.$doc->customer->party_name : '—',
                    'status' => $doc->status->label(),
                    'grand_total' => number_format((float) $doc->grand_total, 2),
                    'action' => view('admin.sales-invoices.partials.actions', ['invoice' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
