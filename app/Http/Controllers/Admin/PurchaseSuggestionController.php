<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\PurchaseSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Purchase suggestion screen from reorder levels (US-M07-01).
 */
class PurchaseSuggestionController extends Controller
{
    public function __construct(protected PurchaseSuggestionService $service) {}

    public function index(): View
    {
        return view('admin.purchase-suggestions.index', [
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return response()->json([
            'status' => true,
            'message' => 'Purchase suggestions loaded.',
            'data' => $this->service->suggestions($warehouseId),
        ]);
    }
}
