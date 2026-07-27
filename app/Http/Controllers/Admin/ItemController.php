<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemType;
use App\Enums\TrackingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\HsnCodeService;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Item master CRUD (M03).
 */
class ItemController extends Controller
{
    public function __construct(
        protected ItemService $service,
        protected HsnCodeService $hsnCodeService
    ) {}

    public function index(): View
    {
        return view('admin.items.index', [
            'itemTypes' => ItemType::cases(),
            'categories' => Category::query()
                ->where('category_type', 'item')
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.items.create', $this->formLookups());
    }

    public function store(ItemRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());
        $message = 'Item created successfully.';
        if ($record->getAttribute('duplicate_warning')) {
            $message .= ' Warning: similar name exists ('.$record->getAttribute('duplicate_warning').').';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $record,
            'redirect' => route('admin.items.index'),
        ], 201);
    }

    public function edit(Item $item): View
    {
        $item = $this->service->find($item->id);

        return view('admin.items.edit', array_merge($this->formLookups(), [
            'item' => $item,
        ]));
    }

    public function update(ItemRequest $request, Item $item): JsonResponse
    {
        $record = $this->service->update($item->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Item updated successfully.',
            'data' => $record,
            'redirect' => route('admin.items.index'),
        ]);
    }

    public function destroy(Item $item): JsonResponse
    {
        try {
            $this->service->delete($item->id);

            return response()->json(['status' => true, 'message' => 'Item deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formLookups(): array
    {
        return [
            'itemTypes' => ItemType::cases(),
            'trackingTypes' => TrackingType::cases(),
            'categories' => Category::query()
                ->where('category_type', 'item')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            'uoms' => Uom::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'hsnCodes' => $this->hsnCodeService->activeOptions(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'substituteItems' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_name')
                ->limit(500)
                ->get(['id', 'item_code', 'item_name']),
        ];
    }
}
