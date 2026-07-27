<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\StockBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock ledger inquiry screen.
 */
class StockLedgerController extends Controller
{
    public function __construct(protected StockBalanceService $service) {}

    public function index(): View
    {
        return view('admin.stock-ledger.index', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('item_name')->limit(500)->get(['id', 'item_code', 'item_name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->ledgerDataTable($request->all()));
    }
}
