<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OpeningStockRequest;
use App\Models\Item;
use App\Models\OpeningStock;
use App\Models\Warehouse;
use App\Services\OpeningStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Opening stock documents (M08).
 */
class OpeningStockController extends Controller
{
    public function __construct(protected OpeningStockService $service) {}

    public function index(): View
    {
        return view('admin.opening-stocks.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.opening-stocks.create', $this->lookups());
    }

    public function store(OpeningStockRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Opening stock saved as draft.',
            'data' => $record,
            'redirect' => route('admin.opening-stocks.edit', $record),
        ], 201);
    }

    public function edit(OpeningStock $openingStock): View
    {
        return view('admin.opening-stocks.edit', array_merge($this->lookups(), [
            'openingStock' => $this->service->find($openingStock->id),
        ]));
    }

    public function update(OpeningStockRequest $request, OpeningStock $openingStock): JsonResponse
    {
        try {
            $record = $this->service->update($openingStock->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Opening stock updated.',
                'data' => $record,
                'redirect' => route('admin.opening-stocks.edit', $record),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(OpeningStock $openingStock): JsonResponse
    {
        try {
            $this->service->delete($openingStock->id);

            return response()->json(['status' => true, 'message' => 'Opening stock deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function post(OpeningStock $openingStock): JsonResponse
    {
        try {
            $record = $this->service->post($openingStock->id);

            return response()->json([
                'status' => true,
                'message' => 'Opening stock posted to ledger.',
                'data' => $record,
                'redirect' => route('admin.opening-stocks.index'),
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
            'items' => Item::query()->where('is_active', true)->where('item_type', '!=', 'service')->orderBy('item_name')->limit(500)->get(['id', 'item_code', 'item_name', 'tracking_type', 'standard_cost']),
        ];
    }
}
