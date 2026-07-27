<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrmFollowUpRequest;
use App\Http\Requests\Admin\LeadConvertRequest;
use App\Http\Requests\Admin\LeadRequest;
use App\Models\Lead;
use App\Models\State;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Lead capture and conversion screens (M05).
 */
class LeadController extends Controller
{
    public function __construct(protected LeadService $service) {}

    public function index(): View
    {
        return view('admin.leads.index', [
            'counts' => $this->service->pipelineCounts(),
            'users' => $this->salesUsers(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.leads.create', $this->lookups());
    }

    public function import(Request $request, \App\Services\LeadImportService $imports): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $result = $imports->import($request->file('file'));

            return response()->json([
                'status' => true,
                'message' => "Imported {$result['imported']} lead(s); skipped {$result['skipped']}.",
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function store(LeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Lead captured.',
                'data' => $lead,
                'redirect' => route('admin.leads.edit', $lead),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(Lead $lead): View
    {
        return view('admin.leads.edit', array_merge($this->lookups(), [
            'lead' => $this->service->find($lead->id),
        ]));
    }

    public function update(LeadRequest $request, Lead $lead): JsonResponse
    {
        try {
            $record = $this->service->update($lead->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Lead updated.',
                'data' => $record,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(Lead $lead): JsonResponse
    {
        try {
            $this->service->delete($lead->id);

            return response()->json(['status' => true, 'message' => 'Lead deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Move the lead along the pipeline (contacted / qualified / lost).
     */
    public function status(Request $request, Lead $lead): JsonResponse
    {
        $status = LeadStatus::tryFrom((string) $request->string('status'));

        if ($status === null) {
            return response()->json(['status' => false, 'message' => 'Unknown lead status.'], 422);
        }

        try {
            $record = $this->service->changeStatus(
                $lead->id,
                $status,
                $request->string('lost_reason')->toString() ?: null
            );

            return response()->json([
                'status' => true,
                'message' => 'Lead marked as '.$status->label().'.',
                'data' => $record,
                'redirect' => route('admin.leads.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function followUp(CrmFollowUpRequest $request, Lead $lead): JsonResponse
    {
        try {
            $followUp = $this->service->logFollowUp($lead->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Follow-up logged.',
                'data' => $followUp,
                'redirect' => route('admin.leads.edit', $lead),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function convert(LeadConvertRequest $request, Lead $lead): JsonResponse
    {
        try {
            $result = $this->service->convert($lead->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Lead converted to customer '.$result['party']->party_code.'.',
                'data' => [
                    'party_id' => $result['party']->id,
                    'party_code' => $result['party']->party_code,
                    'opportunity_id' => $result['opportunity']?->id,
                ],
                'redirect' => $result['opportunity'] !== null
                    ? route('admin.opportunities.edit', $result['opportunity'])
                    : route('admin.parties.edit', $result['party']),
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
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'users' => $this->salesUsers(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function salesUsers(): \Illuminate\Support\Collection
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
