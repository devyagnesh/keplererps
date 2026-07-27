<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\GstType;
use App\Enums\LeadStatus;
use App\Enums\PartyType;
use App\Models\CrmFollowUp;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Party;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lead business logic: capture, qualify, convert to a party (M05).
 */
class LeadService
{
    public function __construct(
        protected LeadRepositoryInterface $repository,
        protected NumberingService $numbering,
        protected PartyService $parties,
        protected OpportunityService $opportunities,
        protected CrmFollowUpService $followUps
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Lead
    {
        return $this->repository->findById($id);
    }

    /**
     * @return array<string, int>
     */
    public function pipelineCounts(): array
    {
        return $this->repository->countsByStatus();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead
    {
        return DB::transaction(function () use ($data): Lead {
            $data['lead_no'] = $this->numbering->next(DocumentSeriesType::Lead);
            $data['status'] = LeadStatus::New->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $lead = $this->repository->create($data);

            return $this->repository->findById($lead->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Lead
    {
        $lead = $this->repository->findById($id);
        $this->assertOpen($lead);

        unset($data['lead_no'], $data['status'], $data['converted_party_id'], $data['converted_at']);
        $data['updated_by'] = Auth::id();

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $lead = $this->repository->findById($id);

        if ($lead->status === LeadStatus::Converted) {
            throw ValidationException::withMessages([
                'lead' => 'A converted lead cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Move a lead along the pipeline without converting it.
     */
    public function changeStatus(int $id, LeadStatus $status, ?string $lostReason = null): Lead
    {
        $lead = $this->repository->findById($id);

        if ($lead->status === LeadStatus::Converted) {
            throw ValidationException::withMessages([
                'lead' => 'A converted lead can no longer change status.',
            ]);
        }

        if ($status === LeadStatus::Converted) {
            throw ValidationException::withMessages([
                'status' => 'Use the convert action to mark a lead as converted.',
            ]);
        }

        if ($status === LeadStatus::Lost && ($lostReason === null || trim($lostReason) === '')) {
            throw ValidationException::withMessages([
                'lost_reason' => 'A reason is required when marking a lead lost.',
            ]);
        }

        $lead->forceFill([
            'status' => $status,
            'lost_reason' => $status === LeadStatus::Lost ? $lostReason : null,
            'updated_by' => Auth::id(),
        ])->save();

        return $this->repository->findById($id);
    }

    /**
     * Log a follow-up against the lead.
     *
     * @param  array<string, mixed>  $data
     */
    public function logFollowUp(int $id, array $data): CrmFollowUp
    {
        $lead = $this->repository->findById($id);
        $this->assertOpen($lead);

        $followUp = $this->followUps->log($lead, $data);

        if ($lead->status === LeadStatus::New) {
            $lead->forceFill(['status' => LeadStatus::Contacted])->save();
        }

        return $followUp;
    }

    /**
     * Convert a qualified lead into a customer party, optionally opening an opportunity.
     *
     * @param  array<string, mixed>  $data  Party details that the lead does not carry.
     * @return array{lead: Lead, party: Party, opportunity: Opportunity|null}
     */
    public function convert(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data): array {
            $lead = $this->repository->findById($id);
            $this->assertOpen($lead);

            $party = $this->parties->create([
                'party_name' => $lead->company_name,
                'party_type' => PartyType::Customer->value,
                'gst_type' => $data['gst_type'] ?? GstType::Unregistered->value,
                'gstin' => $data['gstin'] ?? null,
                'pan' => $data['pan'] ?? null,
                'billing_line1' => $data['billing_line1'],
                'billing_line2' => $data['billing_line2'] ?? null,
                'billing_city' => $data['billing_city'] ?? $lead->city,
                'billing_state_id' => $data['billing_state_id'],
                'billing_pin_code' => $data['billing_pin_code'],
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_days' => $data['credit_days'] ?? null,
                'assigned_user_id' => $lead->assigned_user_id,
                'contacts' => [[
                    'name' => $lead->contact_person,
                    'mobile' => $lead->mobile,
                    'email' => $lead->email,
                    'is_primary' => true,
                ]],
            ]);

            $lead->forceFill([
                'status' => LeadStatus::Converted,
                'converted_party_id' => $party->id,
                'converted_at' => now(),
                'updated_by' => Auth::id(),
            ])->save();

            $opportunity = null;
            if (! empty($data['create_opportunity'])) {
                $opportunity = $this->opportunities->create([
                    'opportunity_date' => now()->toDateString(),
                    'title' => $data['opportunity_title'] ?? ('Opportunity for '.$lead->company_name),
                    'lead_id' => $lead->id,
                    'party_id' => $party->id,
                    'expected_value' => $data['expected_value'] ?? (float) $lead->estimated_value,
                    'expected_close_date' => $data['expected_close_date'] ?? null,
                    'assigned_user_id' => $lead->assigned_user_id,
                    'remarks' => $lead->requirement,
                ]);
            }

            return [
                'lead' => $this->repository->findById($id),
                'party' => $party,
                'opportunity' => $opportunity,
            ];
        });
    }

    protected function assertOpen(Lead $lead): void
    {
        if (! $lead->status->isOpen()) {
            throw ValidationException::withMessages([
                'lead' => 'This lead is closed and can no longer be changed.',
            ]);
        }
    }

    /**
     * Find likely duplicate leads by email / mobile (US-M05-02).
     *
     * @param  array{email?: string|null, mobile?: string|null, company_name?: string|null}  $data
     * @return list<array<string, mixed>>
     */
    public function findDuplicates(array $data, ?int $excludeId = null): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $mobile = preg_replace('/\D+/', '', (string) ($data['mobile'] ?? '')) ?: '';
        $company = trim((string) ($data['company_name'] ?? ''));

        if ($email === '' && $mobile === '' && $company === '') {
            return [];
        }

        return Lead::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($email, $mobile, $company): void {
                if ($email !== '') {
                    $q->orWhere('email', $email);
                }
                if ($mobile !== '') {
                    $q->orWhere('mobile', 'like', '%'.$mobile.'%');
                }
                if ($company !== '') {
                    $q->orWhere('company_name', $company);
                }
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'lead_no', 'company_name', 'contact_person', 'email', 'mobile', 'status'])
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'lead_no' => $lead->lead_no,
                'company_name' => $lead->company_name,
                'contact_name' => $lead->contact_person,
                'email' => $lead->email,
                'mobile' => $lead->mobile,
                'status' => $lead->status?->value ?? $lead->status,
            ])
            ->all();
    }

    /**
     * Open leads / opportunities with overdue next follow-up dates.
     *
     * @return list<array<string, mixed>>
     */
    public function overdueFollowUps(?string $asOf = null): array
    {
        $asOf ??= now()->toDateString();

        $leads = Lead::query()
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<', $asOf)
            ->whereIn('status', [
                LeadStatus::New->value,
                LeadStatus::Contacted->value,
                LeadStatus::Qualified->value,
            ])
            ->orderBy('next_follow_up_date')
            ->limit(100)
            ->get(['id', 'lead_no', 'company_name', 'contact_person', 'next_follow_up_date', 'assigned_user_id']);

        return $leads->map(fn (Lead $lead): array => [
            'type' => 'lead',
            'id' => $lead->id,
            'document_no' => $lead->lead_no,
            'name' => $lead->company_name ?: $lead->contact_person,
            'next_follow_up_date' => $lead->next_follow_up_date?->toDateString(),
            'assigned_user_id' => $lead->assigned_user_id,
        ])->all();
    }
}
