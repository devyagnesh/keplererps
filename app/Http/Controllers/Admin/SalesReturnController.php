<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SalesInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReturnRequest;
use App\Models\Batch;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Warehouse;
use App\Services\SalesReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sales return (credit note) screens.
 */
class SalesReturnController extends Controller
{
    public function __construct(protected SalesReturnService $service) {}

    public function index(): View
    {
        return view('admin.sales-returns.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $invoiceId = $request->integer('sales_invoice_id') ?: null;

        return view('admin.sales-returns.create', array_merge($this->lookups(), [
            'selectedSalesInvoiceId' => $invoiceId,
            'returnableLines' => $invoiceId
                ? $this->service->returnableLinesForInvoice($invoiceId)
                : [],
        ]));
    }

    public function store(SalesReturnRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales return saved as draft.',
                'data' => $record,
                'redirect' => route('admin.sales-returns.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(SalesReturn $salesReturn): View
    {
        $return = $this->service->find($salesReturn->id);

        return view('admin.sales-returns.edit', array_merge($this->lookups(), [
            'salesReturn' => $return,
            'selectedSalesInvoiceId' => $return->sales_invoice_id,
            'returnableLines' => [],
        ]));
    }

    public function update(SalesReturnRequest $request, SalesReturn $salesReturn): JsonResponse
    {
        try {
            $record = $this->service->update($salesReturn->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales return updated.',
                'data' => $record,
                'redirect' => route('admin.sales-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(SalesReturn $salesReturn): JsonResponse
    {
        try {
            $this->service->delete($salesReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales return deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(SalesReturn $salesReturn): JsonResponse
    {
        try {
            $record = $this->service->post($salesReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales return posted to stock ledger.',
                'data' => $record,
                'redirect' => route('admin.sales-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(SalesReturn $salesReturn): JsonResponse
    {
        try {
            $record = $this->service->cancel($salesReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales return cancelled.',
                'data' => $record,
                'redirect' => route('admin.sales-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function returnableLines(SalesInvoice $salesInvoice): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Returnable invoice lines loaded.',
                'data' => $this->service->returnableLinesForInvoice($salesInvoice->id),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'salesInvoices' => SalesInvoice::query()
                ->with('customer:id,party_code,party_name')
                ->where('status', '!=', SalesInvoiceStatus::Cancelled)
                ->latest('id')
                ->limit(200)
                ->get(['id', 'document_no', 'customer_id', 'document_date']),
            'warehouses' => Warehouse::query()
                ->where('is_leaf', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'batches' => Batch::query()
                ->where('is_active', true)
                ->orderBy('batch_no')
                ->limit(500)
                ->get(['id', 'item_id', 'batch_no']),
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
