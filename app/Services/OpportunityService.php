<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\OpportunityStage;
use App\Models\CrmFollowUp;
use App\Models\Opportunity;
use App\Models\SalesQuotation;
use App\Repositories\Interfaces\OpportunityRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opportunity business logic: stage pipeline, quotation link, win/loss (M05).
 */
class OpportunityService
{
    public function __construct(
        protected OpportunityRepositoryInterface $repository,
        protected NumberingService $numbering,
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

    public function find(int $id): Opportunity
    {
        return $this->repository->findById($id);
    }

    /**
     * Pipeline board grouped by stage with per-stage totals.
     *
     * @return array{stages: array<string, array{label: string, count: int, value: float, weighted: float, opportunities: Collection<int, Opportunity>}>, total_value: float, total_weighted: float}
     */
    public function pipeline(?int $assignedUserId = null): array
    {
        $grouped = $this->repository->groupedByStage($assignedUserId);
        $stages = [];
        $totalValue = 0.0;
        $totalWeighted = 0.0;

        foreach (OpportunityStage::cases() as $stage) {
            /** @var Collection<int, Opportunity> $rows */
            $rows = $grouped->get($stage->value, new Collection);
            $value = round((float) $rows->sum(fn (Opportunity $row) => (float) $row->expected_value), 2);
            $weighted = round((float) $rows->sum(fn (Opportunity $row) => $row->weightedValue()), 2);

            $stages[$stage->value] = [
                'label' => $stage->label(),
                'count' => $rows->count(),
                'value' => $value,
                'weighted' => $weighted,
                'opportunities' => $rows,
            ];

            if ($stage->isOpen()) {
                $totalValue += $value;
                $totalWeighted += $weighted;
            }
        }

        return [
            'stages' => $stages,
            'total_value' => round($totalValue, 2),
            'total_weighted' => round($totalWeighted, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity
    {
        return DB::transaction(function () use ($data): Opportunity {
            $stage = OpportunityStage::tryFrom((string) ($data['stage'] ?? '')) ?? OpportunityStage::Qualification;

            if (empty($data['lead_id']) && empty($data['party_id'])) {
                throw ValidationException::withMessages([
                    'party_id' => 'An opportunity needs either a lead or a customer.',
                ]);
            }

            $data['opportunity_no'] = $this->numbering->next(DocumentSeriesType::Opportunity);
            $data['stage'] = $stage->value;
            $data['probability_percent'] = (int) ($data['probability_percent'] ?? $stage->defaultProbability());
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $opportunity = $this->repository->create($data);

            return $this->repository->findById($opportunity->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Opportunity
    {
        $opportunity = $this->repository->findById($id);
        $this->assertOpen($opportunity);

        unset($data['opportunity_no'], $data['stage'], $data['quotation_id'], $data['closed_at']);
        $data['updated_by'] = Auth::id();

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $opportunity = $this->repository->findById($id);

        if (! $opportunity->stage->isOpen()) {
            throw ValidationException::withMessages([
                'opportunity' => 'A closed opportunity cannot be deleted.',
            ]);
        }

        if ($opportunity->quotation_id !== null) {
            throw ValidationException::withMessages([
                'opportunity' => 'This opportunity is linked to a quotation and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Move an opportunity to another stage; won/lost close it.
     */
    public function moveToStage(int $id, OpportunityStage $stage, ?string $lostReason = null): Opportunity
    {
        return DB::transaction(function () use ($id, $stage, $lostReason): Opportunity {
            $opportunity = $this->repository->findById($id);
            $this->assertOpen($opportunity);

            if ($stage === OpportunityStage::Lost && ($lostReason === null || trim($lostReason) === '')) {
                throw ValidationException::withMessages([
                    'lost_reason' => 'A reason is required when marking an opportunity lost.',
                ]);
            }

            if ($stage === OpportunityStage::Won && $opportunity->party_id === null) {
                throw ValidationException::withMessages([
                    'opportunity' => 'Convert the lead to a customer before marking the opportunity won.',
                ]);
            }

            $opportunity->forceFill([
                'stage' => $stage,
                'probability_percent' => $stage->defaultProbability(),
                'lost_reason' => $stage === OpportunityStage::Lost ? $lostReason : null,
                'closed_at' => $stage->isOpen() ? null : now(),
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Link an existing quotation to the opportunity and advance it to Proposal.
     */
    public function attachQuotation(int $id, int $quotationId): Opportunity
    {
        return DB::transaction(function () use ($id, $quotationId): Opportunity {
            $opportunity = $this->repository->findById($id);
            $this->assertOpen($opportunity);

            $quotation = SalesQuotation::query()->findOrFail($quotationId);

            if ($opportunity->party_id !== null && (int) $quotation->customer_id !== (int) $opportunity->party_id) {
                throw ValidationException::withMessages([
                    'quotation_id' => 'The quotation belongs to a different customer.',
                ]);
            }

            $opportunity->forceFill([
                'quotation_id' => $quotation->id,
                'party_id' => $opportunity->party_id ?? $quotation->customer_id,
                'stage' => OpportunityStage::Proposal,
                'probability_percent' => OpportunityStage::Proposal->defaultProbability(),
                'expected_value' => round((float) $quotation->grand_total, 2),
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Log a follow-up against the opportunity.
     *
     * @param  array<string, mixed>  $data
     */
    public function logFollowUp(int $id, array $data): CrmFollowUp
    {
        $opportunity = $this->repository->findById($id);
        $this->assertOpen($opportunity);

        return $this->followUps->log($opportunity, $data);
    }

    protected function assertOpen(Opportunity $opportunity): void
    {
        if (! $opportunity->stage->isOpen()) {
            throw ValidationException::withMessages([
                'opportunity' => 'This opportunity is closed and can no longer be changed.',
            ]);
        }
    }
}
