<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackingUnitRequest;
use App\Models\Item;
use App\Models\PackingUnit;
use App\Models\Uom;
use App\Services\PackingUnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Packing unit master screens (M17).
 */
class PackingUnitController extends Controller
{
    public function __construct(protected PackingUnitService $service) {}

    public function index(): View
    {
        return view('admin.packing-units.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.packing-units.create', $this->lookups());
    }

    public function store(PackingUnitRequest $request): JsonResponse
    {
        try {
            $unit = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Packing unit created.',
                'data' => $unit,
                'redirect' => route('admin.packing-units.index'),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(PackingUnit $packingUnit): View
    {
        return view('admin.packing-units.edit', array_merge($this->lookups($packingUnit->id), [
            'unit' => $this->service->find($packingUnit->id),
        ]));
    }

    public function update(PackingUnitRequest $request, PackingUnit $packingUnit): JsonResponse
    {
        try {
            $unit = $this->service->update($packingUnit->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Packing unit updated.',
                'data' => $unit,
                'redirect' => route('admin.packing-units.index'),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PackingUnit $packingUnit): JsonResponse
    {
        try {
            $this->service->delete($packingUnit->id);

            return response()->json(['status' => true, 'message' => 'Packing unit deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(?int $excludeId = null): array
    {
        return [
            'items' => Item::query()->where('is_active', true)->orderBy('item_code')->get(['id', 'item_code', 'item_name', 'stock_uom_id']),
            'uoms' => Uom::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'parents' => PackingUnit::query()
                ->where('is_active', true)
                ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
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
