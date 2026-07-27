<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\DocumentPrintService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Purchase order screens (M07).
 */
class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $service) {}

    public function index(): View
    {
        return view('admin.purchase-orders.index', [
            'statuses' => PurchaseOrderStatus::cases(),
            'suppliers' => Party::query()
                ->whereIn('party_type', [PartyType::Supplier, PartyType::Both])
                ->where('status', PartyStatus::Active)
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.purchase-orders.create', $this->lookups());
    }

    public function store(PurchaseOrderRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase order saved as draft.',
                'data' => $record,
                'redirect' => route('admin.purchase-orders.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        return view('admin.purchase-orders.edit', array_merge($this->lookups(), [
            'purchaseOrder' => $this->service->find($purchaseOrder->id),
        ]));
    }

    /**
     * Print-friendly purchase order.
     */
    public function print(PurchaseOrder $purchaseOrder, DocumentPrintService $print, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return $print->respond($print->purchaseOrder($purchaseOrder->id), 'purchase_order', $request->string('format')->toString() === 'pdf');
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $record = $this->service->update($purchaseOrder->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Purchase order updated.',
                'data' => $record,
                'redirect' => route('admin.purchase-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $this->service->delete($purchaseOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase order deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function approve(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $record = $this->service->approve($purchaseOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase order approved.',
                'data' => $record,
                'redirect' => route('admin.purchase-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function markSent(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $record = $this->service->markSent($purchaseOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Purchase order marked as sent.',
                'data' => $record,
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
            'suppliers' => Party::query()
                ->whereIn('party_type', [PartyType::Supplier, PartyType::Both])
                ->where('status', PartyStatus::Active)
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'items' => Item::query()
                ->where('is_purchasable', true)
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'stock_uom_id', 'gst_rate', 'standard_cost']),
            'uoms' => Uom::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
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
