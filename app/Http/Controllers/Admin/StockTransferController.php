<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockTransferRequest;
use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock transfer documents (M08).
 */
class StockTransferController extends Controller
{
    public function __construct(protected StockTransferService $service) {}

    public function index(): View
    {
        return view('admin.stock-transfers.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.stock-transfers.create', $this->lookups());
    }

    public function store(StockTransferRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Stock transfer saved as draft.',
                'data' => $record,
                'redirect' => route('admin.stock-transfers.edit', $record),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function edit(StockTransfer $stockTransfer): View
    {
        return view('admin.stock-transfers.edit', array_merge($this->lookups(), [
            'stockTransfer' => $this->service->find($stockTransfer->id),
        ]));
    }

    public function update(StockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $record = $this->service->update($stockTransfer->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Stock transfer updated.',
                'data' => $record,
                'redirect' => route('admin.stock-transfers.edit', $record),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $this->service->delete($stockTransfer->id);

            return response()->json(['status' => true, 'message' => 'Stock transfer deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function post(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $record = $this->service->post($stockTransfer->id);

            return response()->json([
                'status' => true,
                'message' => 'Stock transfer posted.',
                'data' => $record,
                'redirect' => route('admin.stock-transfers.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'warehouses' => Warehouse::query()->where('is_active', true)->where('is_leaf', true)->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::query()->where('is_active', true)->where('item_type', '!=', 'service')->orderBy('item_name')->limit(500)->get(['id', 'item_code', 'item_name', 'tracking_type']),
        ];
    }
}
