<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Goods receipt note screens (M07).
 */
class GoodsReceiptController extends Controller
{
    public function __construct(protected GoodsReceiptService $service) {}

    public function index(): View
    {
        return view('admin.goods-receipts.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $purchaseOrderId = $request->integer('purchase_order_id') ?: null;

        return view('admin.goods-receipts.create', array_merge($this->lookups(), [
            'selectedPurchaseOrderId' => $purchaseOrderId,
            'pendingLines' => $purchaseOrderId
                ? $this->service->pendingLinesForPo($purchaseOrderId)
                : [],
        ]));
    }

    public function store(GoodsReceiptRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt saved as draft.',
                'data' => $record,
                'redirect' => route('admin.goods-receipts.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(GoodsReceipt $goodsReceipt): View
    {
        $grn = $this->service->find($goodsReceipt->id);

        return view('admin.goods-receipts.edit', array_merge($this->lookups(), [
            'goodsReceipt' => $grn,
            'selectedPurchaseOrderId' => $grn->purchase_order_id,
            'pendingLines' => [],
        ]));
    }

    public function update(GoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            $record = $this->service->update($goodsReceipt->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt updated.',
                'data' => $record,
                'redirect' => route('admin.goods-receipts.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            $this->service->delete($goodsReceipt->id);

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            $record = $this->service->post($goodsReceipt->id);

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt posted to stock ledger.',
                'data' => $record,
                'redirect' => route('admin.goods-receipts.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function pendingLines(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Pending PO lines loaded.',
                'data' => $this->service->pendingLinesForPo($purchaseOrder->id),
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
            'purchaseOrders' => PurchaseOrder::query()
                ->with('supplier:id,party_code,party_name')
                ->whereIn('status', [
                    PurchaseOrderStatus::Approved,
                    PurchaseOrderStatus::Sent,
                    PurchaseOrderStatus::PartiallyReceived,
                ])
                ->latest('id')
                ->limit(200)
                ->get(['id', 'document_no', 'supplier_id', 'document_date']),
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
