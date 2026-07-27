<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseIndent;
use App\Models\Warehouse;
use App\Services\PurchaseIndentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Purchase indent screens (M07).
 */
class PurchaseIndentController extends Controller
{
    public function __construct(protected PurchaseIndentService $service) {}

    public function index(): View
    {
        return view('admin.purchase-indents.index', [
            'indents' => $this->service->all(),
            'warehouses' => Warehouse::query()->where('is_active', true)->where('is_leaf', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'document_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
        ]);

        try {
            $indent = $this->service->createFromSuggestions($data);

            return response()->json([
                'status' => true,
                'message' => 'Purchase indent created.',
                'data' => $indent,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function show(PurchaseIndent $purchaseIndent): View
    {
        return view('admin.purchase-indents.show', [
            'indent' => $this->service->find($purchaseIndent->id),
        ]);
    }

    public function approve(PurchaseIndent $purchaseIndent): JsonResponse
    {
        try {
            $indent = $this->service->approve($purchaseIndent->id);

            return response()->json(['status' => true, 'message' => 'Indent approved.', 'data' => $indent]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function cancel(PurchaseIndent $purchaseIndent): JsonResponse
    {
        try {
            $indent = $this->service->cancel($purchaseIndent->id);

            return response()->json(['status' => true, 'message' => 'Indent cancelled.', 'data' => $indent]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function convert(Request $request, PurchaseIndent $purchaseIndent): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:parties,id'],
            'document_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rates' => ['nullable', 'array'],
            'rates.*' => ['numeric', 'min:0'],
        ]);

        try {
            $po = $this->service->convertToPurchaseOrder($purchaseIndent->id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Purchase order created from indent.',
                'data' => $po,
                'redirect' => route('admin.purchase-orders.edit', $po),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
