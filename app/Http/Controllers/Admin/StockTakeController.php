<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTake;
use App\Models\Warehouse;
use App\Services\StockTakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Physical stock-take screens (M08 / M17).
 */
class StockTakeController extends Controller
{
    public function __construct(protected StockTakeService $service) {}

    public function index(): View
    {
        $takes = StockTake::query()->with('warehouse:id,code,name')->latest('id')->limit(100)->get();

        return view('admin.stock-takes.index', ['takes' => $takes]);
    }

    public function create(): View
    {
        return view('admin.stock-takes.create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->where('is_leaf', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'document_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'seed' => ['sometimes', 'boolean'],
        ]);

        try {
            $take = $this->service->create($data);
            if (! empty($data['seed'])) {
                $take = $this->service->seedFromBalances($take->id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Stock take created.',
                'data' => $take,
                'redirect' => route('admin.stock-takes.edit', $take),
            ], 201);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }
    }

    public function edit(StockTake $stockTake): View
    {
        return view('admin.stock-takes.edit', [
            'stockTake' => $this->service->find($stockTake->id),
        ]);
    }

    public function saveLines(Request $request, StockTake $stockTake): JsonResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.batch_id' => ['nullable', 'integer'],
            'lines.*.counted_qty' => ['required', 'numeric'],
            'lines.*.scanned_code' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $take = $this->service->saveLines($stockTake->id, $data['lines']);

            return response()->json(['status' => true, 'message' => 'Count lines saved.', 'data' => $take]);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }
    }

    public function seed(StockTake $stockTake): JsonResponse
    {
        try {
            $take = $this->service->seedFromBalances($stockTake->id);

            return response()->json(['status' => true, 'message' => 'Balances seeded.', 'data' => $take]);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }
    }

    public function scan(Request $request, StockTake $stockTake): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:255']]);

        try {
            $take = $this->service->scanPackage($stockTake->id, $data['code']);

            return response()->json(['status' => true, 'message' => 'Package scanned into count.', 'data' => $take]);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }
    }

    public function post(StockTake $stockTake): JsonResponse
    {
        try {
            $take = $this->service->post($stockTake->id);

            return response()->json([
                'status' => true,
                'message' => 'Stock take posted.',
                'data' => $take,
                'redirect' => route('admin.stock-takes.index'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail($e);
        }
    }

    protected function fail(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => collect($e->errors())->flatten()->first(),
            'errors' => $e->errors(),
        ], 422);
    }
}
