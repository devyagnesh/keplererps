<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GrnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseBillApproveRequest;
use App\Http\Requests\Admin\PurchaseBillRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseBill;
use App\Services\PurchaseBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Supplier purchase bill screens with three-way match (M07 / US-M07-04).
 */
class PurchaseBillController extends Controller
{
    public function __construct(protected PurchaseBillService $service) {}

    public function index(): View
    {
        return view('admin.purchase-bills.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $goodsReceiptId = $request->integer('goods_receipt_id') ?: null;

        return view('admin.purchase-bills.create', array_merge($this->lookups(), [
            'selectedGoodsReceiptId' => $goodsReceiptId,
            'billableLines' => $goodsReceiptId
                ? $this->service->billableLinesForGrn($goodsReceiptId)
                : [],
        ]));
    }

    public function store(PurchaseBillRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase bill saved as draft.',
                'data' => $record,
                'redirect' => route('admin.purchase-bills.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(PurchaseBill $purchaseBill): View
    {
        $bill = $this->service->find($purchaseBill->id);

        return view('admin.purchase-bills.edit', array_merge($this->lookups(), [
            'purchaseBill' => $bill,
            'selectedGoodsReceiptId' => $bill->goods_receipt_id,
            'billableLines' => [],
        ]));
    }

    public function update(PurchaseBillRequest $request, PurchaseBill $purchaseBill): JsonResponse
    {
        try {
            $record = $this->service->update($purchaseBill->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase bill updated.',
                'data' => $record,
                'redirect' => route('admin.purchase-bills.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PurchaseBill $purchaseBill): JsonResponse
    {
        try {
            $this->service->delete($purchaseBill->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase bill deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function approve(PurchaseBillApproveRequest $request, PurchaseBill $purchaseBill): JsonResponse
    {
        try {
            $record = $this->service->approve(
                $purchaseBill->id,
                $request->validated()['mismatch_reason'] ?? null
            );

            return response()->json([
                'status' => true,
                'message' => 'Purchase bill approved.',
                'data' => $record,
                'redirect' => route('admin.purchase-bills.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(PurchaseBill $purchaseBill): JsonResponse
    {
        try {
            $record = $this->service->cancel($purchaseBill->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase bill cancelled.',
                'data' => $record,
                'redirect' => route('admin.purchase-bills.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function billableLines(GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Billable GRN lines loaded.',
                'data' => $this->service->billableLinesForGrn($goodsReceipt->id),
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
            'rateTolerance' => $this->service->rateTolerancePercent(),
            'qtyTolerance' => $this->service->qtyTolerancePercent(),
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
