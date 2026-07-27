<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesInvoiceRequest;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\DocumentPrintService;
use App\Services\SalesInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sales invoice screens (M06).
 */
class SalesInvoiceController extends Controller
{
    public function __construct(protected SalesInvoiceService $service) {}

    public function index(): View
    {
        return view('admin.sales-invoices.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $challanId = $request->integer('delivery_challan_id') ?: null;
        $pendingLines = [];
        $selectedSalesOrderId = null;

        if ($challanId) {
            $pendingLines = $this->service->pendingLinesForChallan($challanId);
            $selectedSalesOrderId = \App\Models\DeliveryChallan::query()->find($challanId)?->sales_order_id;
        }

        return view('admin.sales-invoices.create', array_merge($this->lookups($challanId), [
            'selectedSalesOrderId' => $selectedSalesOrderId,
            'selectedDeliveryChallanId' => $challanId,
            'pendingLines' => $pendingLines,
        ]));
    }

    public function store(SalesInvoiceRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales invoice saved as draft.',
                'data' => $record,
                'redirect' => route('admin.sales-invoices.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(SalesInvoice $salesInvoice): View
    {
        $invoice = $this->service->find($salesInvoice->id);

        return view('admin.sales-invoices.edit', array_merge($this->lookups($invoice->delivery_challan_id), [
            'salesInvoice' => $invoice,
        ]));
    }

    public function update(SalesInvoiceRequest $request, SalesInvoice $salesInvoice): JsonResponse
    {
        try {
            $record = $this->service->update($salesInvoice->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales invoice updated.',
                'data' => $record,
                'redirect' => route('admin.sales-invoices.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(SalesInvoice $salesInvoice): JsonResponse
    {
        try {
            $this->service->delete($salesInvoice->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales invoice deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function confirm(SalesInvoice $salesInvoice): JsonResponse
    {
        try {
            $record = $this->service->confirm($salesInvoice->id);

            return response()->json([
                'status' => true,
                'message' => $record->delivery_challan_id
                    ? 'Invoice confirmed.'
                    : 'Invoice confirmed. Stock delivered.',
                'data' => $record,
                'redirect' => route('admin.sales-invoices.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Print-friendly tax invoice.
     */
    public function print(SalesInvoice $salesInvoice, DocumentPrintService $print, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return $print->respond($print->salesInvoice($salesInvoice->id), 'sales_invoice', $request->string('format')->toString() === 'pdf');
    }

    public function pendingLines(SalesOrder $salesOrder): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Pending lines loaded.',
                'data' => $this->service->pendingLinesForOrder($salesOrder->id),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function challanLines(\App\Models\DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Challan lines loaded.',
                'data' => $this->service->pendingLinesForChallan($deliveryChallan->id),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(?int $deliveryChallanId = null): array
    {
        return [
            'salesOrders' => SalesOrder::query()
                ->with('customer:id,party_code,party_name')
                ->whereIn('status', [
                    SalesOrderStatus::Confirmed->value,
                    SalesOrderStatus::PartiallyDelivered->value,
                    SalesOrderStatus::Delivered->value,
                ])
                ->orderByDesc('id')
                ->get(['id', 'document_no', 'customer_id', 'status']),
            'deliveryChallans' => \App\Models\DeliveryChallan::query()
                ->whereIn('status', [
                    \App\Enums\DeliveryChallanStatus::Dispatched->value,
                    \App\Enums\DeliveryChallanStatus::Delivered->value,
                ])
                ->when($deliveryChallanId, fn ($q) => $q->orWhere('id', $deliveryChallanId))
                ->orderByDesc('id')
                ->get(['id', 'document_no', 'sales_order_id', 'status']),
        ];
    }

    protected function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => collect($e->errors())->flatten()->first(),
            'errors' => $e->errors(),
        ], 422);
    }
}
