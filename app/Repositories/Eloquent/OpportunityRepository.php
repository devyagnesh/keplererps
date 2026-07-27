<?php

namespace App\Repositories\Eloquent;

use App\Models\Opportunity;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\OpportunityRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent opportunity repository.
 */
class OpportunityRepository implements OpportunityRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Opportunity
    {
        return Opportunity::query()
            ->with([
                'lead:id,lead_no,company_name,contact_person,mobile',
                'party:id,party_code,party_name',
                'assignedUser:id,name',
                'quotation:id,document_no,status,grand_total',
                'followUps.createdBy:id,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): Opportunity
    {
        return Opportunity::query()->create($data);
    }

    public function update(int $id, array $data): Opportunity
    {
        $record = Opportunity::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) Opportunity::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = Opportunity::query()->with(['party:id,party_code,party_name', 'assignedUser:id,name']);

        if (! empty($params['stage'])) {
            $query->where('stage', $params['stage']);
        }
        if (! empty($params['assigned_user_id'])) {
            $query->where('assigned_user_id', (int) $params['assigned_user_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('opportunity_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('opportunity_date', '<=', $params['date_to']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'opportunity_no', 'opportunity_date', 'title', 'expected_value', 'expected_close_date', 'stage', 'created_at'],
            ['opportunity_no', 'title', 'remarks'],
            function (Opportunity $opportunity): array {
                return [
                    'id' => $opportunity->id,
                    'opportunity_no' => $opportunity->opportunity_no,
                    'opportunity_date' => $opportunity->opportunity_date?->format('Y-m-d'),
                    'title' => e($opportunity->title),
                    'customer' => e($opportunity->party?->party_name ?? $opportunity->lead?->company_name ?? '—'),
                    'expected_value' => number_format((float) $opportunity->expected_value, 2, '.', ''),
                    'weighted_value' => number_format($opportunity->weightedValue(), 2, '.', ''),
                    'expected_close_date' => $opportunity->expected_close_date?->format('Y-m-d') ?? '—',
                    'owner' => e($opportunity->assignedUser?->name ?? '—'),
                    'stage' => '<span class="badge '.$opportunity->stage->badgeClass().'">'.$opportunity->stage->label().'</span>',
                    'action' => view('admin.opportunities.partials.actions', ['opportunity' => $opportunity])->render(),
                ];
            },
            $params
        );
    }

    public function groupedByStage(?int $assignedUserId = null): Collection
    {
        return Opportunity::query()
            ->with(['party:id,party_code,party_name', 'lead:id,company_name', 'assignedUser:id,name'])
            ->when($assignedUserId, fn ($q) => $q->where('assigned_user_id', $assignedUserId))
            ->orderByDesc('expected_value')
            ->get()
            ->groupBy(fn (Opportunity $opportunity): string => $opportunity->stage->value);
    }
}
