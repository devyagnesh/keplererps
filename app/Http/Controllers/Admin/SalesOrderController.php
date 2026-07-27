<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderRequest;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sales order screens (M06).
 */
class SalesOrderController extends Controller
{
    public function __construct(protected SalesOrderService $service) {}

    public function index(): View
    {
        return view('admin.sales-orders.index', [
            'statuses' => SalesOrderStatus::cases(),
            'customers' => $this->customers(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.sales-orders.create', $this->lookups());
    }

    public function store(SalesOrderRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales order saved as draft.',
                'data' => $record,
                'redirect' => route('admin.sales-orders.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(SalesOrder $salesOrder): View
    {
        return view('admin.sales-orders.edit', array_merge($this->lookups(), [
            'salesOrder' => $this->service->find($salesOrder->id),
        ]));
    }

    public function update(SalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        try {
            $record = $this->service->update($salesOrder->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Sales order updated.',
                'data' => $record,
                'redirect' => route('admin.sales-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        try {
            $this->service->delete($salesOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales order deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function confirm(SalesOrder $salesOrder): JsonResponse
    {
        try {
            $record = $this->service->confirm($salesOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales order confirmed. Stock committed.',
                'data' => $record,
                'redirect' => route('admin.sales-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(SalesOrder $salesOrder): JsonResponse
    {
        try {
            $record = $this->service->cancel($salesOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Sales order cancelled.',
                'data' => $record,
                'redirect' => route('admin.sales-orders.edit', $record),
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
            'customers' => $this->customers(),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::query()
                ->where('is_sellable', true)
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'stock_uom_id', 'gst_rate', 'selling_price']),
            'uoms' => Uom::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Party>
     */
    protected function customers()
    {
        return Party::query()
            ->whereIn('party_type', [PartyType::Customer, PartyType::Both])
            ->where('status', PartyStatus::Active)
            ->orderBy('party_name')
            ->get(['id', 'party_code', 'party_name']);
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
