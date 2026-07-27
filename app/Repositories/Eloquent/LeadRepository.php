<?php

namespace App\Repositories\Eloquent;

use App\Models\Lead;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\LeadRepositoryInterface;

/**
 * Eloquent lead repository.
 */
class LeadRepository implements LeadRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Lead
    {
        return Lead::query()
            ->with([
                'state:id,code,name',
                'assignedUser:id,name',
                'convertedParty:id,party_code,party_name',
                'followUps.createdBy:id,name',
                'opportunities:id,lead_id,opportunity_no,stage,expected_value',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): Lead
    {
        return Lead::query()->create($data);
    }

    public function update(int $id, array $data): Lead
    {
        $record = Lead::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) Lead::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = Lead::query()->with(['assignedUser:id,name']);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['source'])) {
            $query->where('source', $params['source']);
        }
        if (! empty($params['assigned_user_id'])) {
            $query->where('assigned_user_id', (int) $params['assigned_user_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('lead_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('lead_date', '<=', $params['date_to']);
        }
        if (! empty($params['due_only'])) {
            $query->whereNotNull('next_follow_up_date')
                ->whereDate('next_follow_up_date', '<=', now()->toDateString());
        }

        return $this->buildDataTable(
            $query,
            ['id', 'lead_no', 'lead_date', 'company_name', 'estimated_value', 'next_follow_up_date', 'status', 'created_at'],
            ['lead_no', 'company_name', 'contact_person', 'mobile', 'email'],
            function (Lead $lead): array {
                return [
                    'id' => $lead->id,
                    'lead_no' => $lead->lead_no,
                    'lead_date' => $lead->lead_date?->format('Y-m-d'),
                    'company_name' => e($lead->company_name),
                    'contact' => e($lead->contact_person.' · '.$lead->mobile),
                    'source' => $lead->source->label(),
                    'estimated_value' => number_format((float) $lead->estimated_value, 2, '.', ''),
                    'next_follow_up_date' => $lead->next_follow_up_date?->format('Y-m-d') ?? '—',
                    'owner' => e($lead->assignedUser?->name ?? '—'),
                    'status' => '<span class="badge '.$lead->status->badgeClass().'">'.$lead->status->label().'</span>',
                    'action' => view('admin.leads.partials.actions', ['lead' => $lead])->render(),
                ];
            },
            $params
        );
    }

    public function countsByStatus(): array
    {
        return Lead::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
