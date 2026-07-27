<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GrnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseReturnRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Purchase return (debit note) screens.
 */
class PurchaseReturnController extends Controller
{
    public function __construct(protected PurchaseReturnService $service) {}

    public function index(): View
    {
        return view('admin.purchase-returns.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $goodsReceiptId = $request->integer('goods_receipt_id') ?: null;

        return view('admin.purchase-returns.create', array_merge($this->lookups(), [
            'selectedGoodsReceiptId' => $goodsReceiptId,
            'returnableLines' => $goodsReceiptId
                ? $this->service->returnableLinesForGrn($goodsReceiptId)
                : [],
        ]));
    }

    public function store(PurchaseReturnRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase return saved as draft.',
                'data' => $record,
                'redirect' => route('admin.purchase-returns.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(PurchaseReturn $purchaseReturn): View
    {
        $return = $this->service->find($purchaseReturn->id);

        return view('admin.purchase-returns.edit', array_merge($this->lookups(), [
            'purchaseReturn' => $return,
            'selectedGoodsReceiptId' => $return->goods_receipt_id,
            'returnableLines' => [],
        ]));
    }

    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): JsonResponse
    {
        try {
            $record = $this->service->update($purchaseReturn->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase return updated.',
                'data' => $record,
                'redirect' => route('admin.purchase-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse
    {
        try {
            $this->service->delete($purchaseReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase return deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(PurchaseReturn $purchaseReturn): JsonResponse
    {
        try {
            $record = $this->service->post($purchaseReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase return posted to stock ledger.',
                'data' => $record,
                'redirect' => route('admin.purchase-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(PurchaseReturn $purchaseReturn): JsonResponse
    {
        try {
            $record = $this->service->cancel($purchaseReturn->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase return cancelled.',
                'data' => $record,
                'redirect' => route('admin.purchase-returns.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function returnableLines(GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Returnable GRN lines loaded.',
                'data' => $this->service->returnableLinesForGrn($goodsReceipt->id),
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
            'goodsReceipts' => GoodsReceipt::query()
                ->with('supplier:id,party_code,party_name')
                ->where('status', GrnStatus::Posted)
                ->latest('id')
                ->limit(200)
                ->get(['id', 'document_no', 'supplier_id', 'document_date']),
            'warehouses' => $this->service->issuableWarehouses(),
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
