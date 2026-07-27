<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Warehouse;
use App\Services\StockBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock balance and valuation screens (US-M08-02).
 */
class StockBalanceController extends Controller
{
    public function __construct(protected StockBalanceService $service) {}

    public function index(): View
    {
        $summary = $this->service->valuationSummary(
            request()->integer('warehouse_id') ?: null,
            request()->integer('category_id') ?: null
        );

        return view('admin.stock-balances.index', [
            'warehouses' => Warehouse::query()->where('is_active', true)->where('is_leaf', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('category_type', 'item')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->balanceDataTable($request->all()));
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Valuation summary loaded.',
            'data' => $this->service->valuationSummary(
                $request->integer('warehouse_id') ?: null,
                $request->integer('category_id') ?: null
            ),
        ]);
    }

    /**
     * Available-to-promise figures used by the sales line pickers (US-M03-04).
     */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Availability loaded.',
            'data' => $this->service->availability(
                (int) $data['item_id'],
                isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null
            ),
        ]);
    }
}
