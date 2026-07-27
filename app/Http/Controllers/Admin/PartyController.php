<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AddressType;
use App\Enums\GstType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyRequest;
use App\Models\Party;
use App\Models\State;
use App\Services\CompanyService;
use App\Services\PartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Party (customer/supplier) master CRUD (M01).
 */
class PartyController extends Controller
{
    public function __construct(
        protected PartyService $service,
        protected CompanyService $companyService
    ) {}

    /**
     * List parties.
     */
    public function index(): View
    {
        return view('admin.parties.index', [
            'partyTypes' => PartyType::cases(),
            'statuses' => PartyStatus::cases(),
        ]);
    }

    /**
     * DataTables JSON.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        return view('admin.parties.create', $this->formData());
    }

    /**
     * Store a party.
     */
    public function store(PartyRequest $request): JsonResponse
    {
        try {
            $party = $this->service->create($request->validated());
            $message = 'Party created successfully.';
            if ($party->getAttribute('duplicate_warning')) {
                $message .= ' Warning: a party with a similar name already exists ('.$party->getAttribute('duplicate_warning').').';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $party,
                'redirect' => route('admin.parties.index'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create party.',
            ], 500);
        }
    }

    /**
     * Edit form.
     */
    public function edit(Party $party): View
    {
        return view('admin.parties.edit', array_merge($this->formData(), [
            'party' => $party->load(['billingState', 'contacts', 'addresses.state']),
        ]));
    }

    /**
     * Update a party.
     */
    public function update(PartyRequest $request, Party $party): JsonResponse
    {
        try {
            $updated = $this->service->update($party->id, $request->validated());
            $message = 'Party updated successfully.';
            if ($updated->getAttribute('state_change_warning')) {
                $message .= ' Warning: changing state affects IGST vs CGST/SGST on future documents.';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $updated,
                'redirect' => route('admin.parties.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update party.',
            ], 500);
        }
    }

    /**
     * Soft-delete a party.
     */
    public function destroy(Party $party): JsonResponse
    {
        try {
            $this->service->delete($party->id);

            return response()->json([
                'status' => true,
                'message' => 'Party deleted successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Unable to delete party.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete party.',
            ], 500);
        }
    }

    /**
     * GSTIN helper for auto-fill (US-M01-03).
     */
    public function gstinLookup(Request $request): JsonResponse
    {
        $gstin = strtoupper(trim((string) $request->query('gstin', '')));
        if (strlen($gstin) !== 15) {
            return response()->json([
                'status' => false,
                'message' => 'GSTIN must be 15 characters.',
            ], 422);
        }

        $company = $this->companyService->getCompany();
        $hints = $this->service->resolveGstinHints($gstin, $company?->state_id);

        return response()->json([
            'status' => true,
            'message' => 'GSTIN resolved.',
            'data' => $hints,
        ]);
    }

    /**
     * Shared form option data.
     *
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'partyTypes' => PartyType::cases(),
            'gstTypes' => GstType::cases(),
            'statuses' => PartyStatus::cases(),
            'addressTypes' => AddressType::cases(),
        ];
    }
}
