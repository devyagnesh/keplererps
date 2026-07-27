<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BomIssueMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BomRequest;
use App\Models\Bom;
use App\Models\Item;
use App\Models\ManufacturingOperation;
use App\Models\Party;
use App\Models\Uom;
use App\Models\WorkCentre;
use App\Services\BomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bill of Materials CRUD and costing (M04).
 */
class BomController extends Controller
{
    public function __construct(protected BomService $service) {}

    public function index(): View
    {
        return view('admin.boms.index', [
            'items' => Item::query()
                ->where('is_manufacturable', true)
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.boms.create', $this->lookups());
    }

    public function store(BomRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'BOM created successfully.',
                'data' => $record,
                'redirect' => route('admin.boms.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function edit(Bom $bom): View
    {
        return view('admin.boms.edit', array_merge($this->lookups(), [
            'bom' => $this->service->find($bom->id),
        ]));
    }

    public function update(BomRequest $request, Bom $bom): JsonResponse
    {
        try {
            $record = $this->service->update($bom->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'BOM updated successfully.',
                'data' => $record,
                'redirect' => route('admin.boms.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Bom $bom): JsonResponse
    {
        $this->service->delete($bom->id);

        return response()->json([
            'status' => true,
            'message' => 'BOM deleted successfully.',
        ]);
    }

    public function newVersion(Bom $bom): JsonResponse
    {
        try {
            $record = $this->service->createNewVersion($bom->id);

            return response()->json([
                'status' => true,
                'message' => 'New BOM version created.',
                'data' => $record,
                'redirect' => route('admin.boms.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function explode(Request $request, Bom $bom): JsonResponse
    {
        $validated = $request->validate([
            'order_quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $lines = $this->service->explodeRequirements($bom->id, (float) $validated['order_quantity']);

            return response()->json([
                'status' => true,
                'message' => 'Material requirements calculated.',
                'data' => $lines,
            ]);
        } catch (ValidationException $e) {
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
            'finishedItems' => Item::query()
                ->where('is_manufacturable', true)
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'stock_uom_id', 'standard_cost']),
            'componentItems' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'stock_uom_id', 'standard_cost']),
            'uoms' => Uom::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'manufacturingOperations' => ManufacturingOperation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'workCentres' => WorkCentre::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'machine_rate_per_hour', 'labour_rate_per_hour']),
            'vendors' => Party::query()
                ->where('is_active', true)
                ->whereIn('party_type', [\App\Enums\PartyType::Supplier, \App\Enums\PartyType::Both])
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
            'issueMethods' => BomIssueMethod::cases(),
        ];
    }
}
