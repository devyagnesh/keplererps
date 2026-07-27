<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesQuotationRequest;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesQuotation;
use App\Models\State;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\DocumentPrintService;
use App\Services\SalesQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sales quotation screens (M06).
 */
class SalesQuotationController extends Controller
{
    public function __construct(protected SalesQuotationService $service) {}

    public function index(): View
    {
        return view('admin.sales-quotations.index', [
            'statuses' => QuotationStatus::cases(),
            'customers' => $this->customers(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.sales-quotations.create', $this->lookups());
    }

    public function store(SalesQuotationRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Quotation saved as draft.',
                'data' => $record,
                'redirect' => route('admin.sales-quotations.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(SalesQuotation $salesQuotation): View
    {
        return view('admin.sales-quotations.edit', array_merge($this->lookups(), [
            'salesQuotation' => $this->service->find($salesQuotation->id),
        ]));
    }

    /**
     * Print-friendly quotation.
     */
    public function print(SalesQuotation $salesQuotation, DocumentPrintService $print, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return $print->respond($print->salesQuotation($salesQuotation->id), 'sales_quotation', $request->string('format')->toString() === 'pdf');
    }

    public function update(SalesQuotationRequest $request, SalesQuotation $salesQuotation): JsonResponse
    {
        try {
            $record = $this->service->update($salesQuotation->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Quotation updated.',
                'data' => $record,
                'redirect' => route('admin.sales-quotations.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(SalesQuotation $salesQuotation): JsonResponse
    {
        try {
            $this->service->delete($salesQuotation->id);

            return response()->json([
                'status' => true,
                'message' => 'Quotation deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function markSent(SalesQuotation $salesQuotation): JsonResponse
    {
        try {
            $record = $this->service->markSent($salesQuotation->id);

            return response()->json([
                'status' => true,
                'message' => 'Quotation marked as sent.',
                'data' => $record,
                'redirect' => route('admin.sales-quotations.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function accept(SalesQuotation $salesQuotation): JsonResponse
    {
        try {
            $record = $this->service->accept($salesQuotation->id);

            return response()->json([
                'status' => true,
                'message' => 'Quotation accepted.',
                'data' => $record,
                'redirect' => route('admin.sales-quotations.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function convert(SalesQuotation $salesQuotation): JsonResponse
    {
        try {
            $order = $this->service->convertToSalesOrder($salesQuotation->id);

            return response()->json([
                'status' => true,
                'message' => 'Quotation converted to sales order.',
                'data' => $order,
                'redirect' => route('admin.sales-orders.edit', $order),
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
