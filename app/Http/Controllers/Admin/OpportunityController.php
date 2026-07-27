<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\OpportunityStage;
use App\Enums\PartyStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrmFollowUpRequest;
use App\Http\Requests\Admin\OpportunityRequest;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Party;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Services\OpportunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Opportunity pipeline screens (M05).
 */
class OpportunityController extends Controller
{
    public function __construct(protected OpportunityService $service) {}

    public function index(): View
    {
        return view('admin.opportunities.index', ['users' => $this->salesUsers()]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    /**
     * Stage-wise pipeline board.
     */
    public function pipeline(Request $request): View
    {
        $ownerId = $request->integer('assigned_user_id') ?: null;

        return view('admin.opportunities.pipeline', [
            'pipeline' => $this->service->pipeline($ownerId),
            'users' => $this->salesUsers(),
            'selectedUserId' => $ownerId,
        ]);
    }

    public function create(): View
    {
        return view('admin.opportunities.create', $this->lookups());
    }

    public function store(OpportunityRequest $request): JsonResponse
    {
        try {
            $opportunity = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Opportunity created.',
                'data' => $opportunity,
                'redirect' => route('admin.opportunities.edit', $opportunity),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(Opportunity $opportunity): View
    {
        $record = $this->service->find($opportunity->id);

        return view('admin.opportunities.edit', array_merge($this->lookups(), [
            'opportunity' => $record,
            'quotations' => $this->quotationsFor($record),
        ]));
    }

    public function update(OpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $record = $this->service->update($opportunity->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Opportunity updated.',
                'data' => $record,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        try {
            $this->service->delete($opportunity->id);

            return response()->json(['status' => true, 'message' => 'Opportunity deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Move the opportunity to another pipeline stage.
     */
    public function stage(Request $request, Opportunity $opportunity): JsonResponse
    {
        $stage = OpportunityStage::tryFrom((string) $request->string('stage'));

        if ($stage === null) {
            return response()->json(['status' => false, 'message' => 'Unknown pipeline stage.'], 422);
        }

        try {
            $record = $this->service->moveToStage(
                $opportunity->id,
                $stage,
                $request->string('lost_reason')->toString() ?: null
            );

            return response()->json([
                'status' => true,
                'message' => 'Opportunity moved to '.$stage->label().'.',
                'data' => $record,
                'redirect' => route('admin.opportunities.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function attachQuotation(Request $request, Opportunity $opportunity): JsonResponse
    {
        $quotationId = $request->integer('quotation_id');

        if ($quotationId === 0) {
            return response()->json(['status' => false, 'message' => 'Select a quotation to link.'], 422);
        }

        try {
            $record = $this->service->attachQuotation($opportunity->id, $quotationId);

            return response()->json([
                'status' => true,
                'message' => 'Quotation linked to the opportunity.',
                'data' => $record,
                'redirect' => route('admin.opportunities.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function followUp(CrmFollowUpRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $followUp = $this->service->logFollowUp($opportunity->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Follow-up logged.',
                'data' => $followUp,
                'redirect' => route('admin.opportunities.edit', $opportunity),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Quotations the opportunity can be linked to.
     *
     * @return Collection<int, SalesQuotation>
     */
    protected function quotationsFor(Opportunity $opportunity): Collection
    {
        return SalesQuotation::query()
            ->when($opportunity->party_id, fn ($q) => $q->where('customer_id', $opportunity->party_id))
            ->whereIn('status', [QuotationStatus::Draft->value, QuotationStatus::Sent->value, QuotationStatus::Accepted->value])
            ->orderByDesc('document_date')
            ->limit(50)
            ->get(['id', 'document_no', 'document_date', 'grand_total']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'users' => $this->salesUsers(),
            'parties' => Party::query()
                ->where('status', PartyStatus::Active)
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
            'leads' => Lead::query()
                ->whereIn('status', [LeadStatus::Qualified->value, LeadStatus::Converted->value])
                ->orderByDesc('lead_date')
                ->limit(100)
                ->get(['id', 'lead_no', 'company_name']),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    protected function salesUsers(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
