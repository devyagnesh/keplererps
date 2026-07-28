<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\PriceList;
use App\Services\PriceListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Customer / default selling price lists (M06).
 */
class PriceListController extends Controller
{
    public function __construct(protected PriceListService $service) {}

    public function index(): View
    {
        return view('admin.price-lists.index', [
            'lists' => $this->service->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.price-lists.form', $this->lookups());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $list = $this->service->create($data);

            return response()->json([
                'status' => true,
                'message' => 'Price list created.',
                'data' => $list,
                'redirect' => route('admin.price-lists.edit', $list),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(PriceList $priceList): View
    {
        return view('admin.price-lists.form', array_merge($this->lookups(), [
            'priceList' => $this->service->find($priceList->id),
        ]));
    }

    public function update(Request $request, PriceList $priceList): JsonResponse
    {
        $data = $this->validated($request, $priceList->id);

        try {
            $list = $this->service->update($priceList->id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Price list updated.',
                'data' => $list,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        $this->service->delete($priceList->id);

        return response()->json(['status' => true, 'message' => 'Price list deleted.']);
    }

    public function resolveRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => ['nullable', 'integer'],
            'item_id' => ['required', 'integer'],
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $rate = $this->service->resolveRate(
            $validated['party_id'] ?? null,
            (int) $validated['item_id'],
            (float) ($validated['qty'] ?? 1)
        );

        return response()->json([
            'status' => true,
            'message' => 'Rate resolved.',
            'data' => ['rate' => $rate],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('price_lists', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'party_ids' => ['nullable', 'array'],
            'party_ids.*' => ['integer'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.min_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'items' => Item::query()->where('is_active', true)->where('is_sellable', true)->orderBy('item_code')->limit(500)->get(['id', 'item_code', 'item_name', 'selling_price']),
            'parties' => Party::query()->where('status', PartyStatus::Active)->orderBy('party_code')->limit(500)->get(['id', 'party_code', 'party_name', 'party_type']),
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
