<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\OpportunityStage;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

/**
 * CRM conversion funnel aggregates (US-M05-04).
 */
class CrmFunnelReportService
{
    /**
     * @return array{leads: list<array<string, mixed>>, opportunities: list<array<string, mixed>>, conversion_rate: float}
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $leadsQuery = Lead::query();
        $oppsQuery = Opportunity::query();

        if ($from) {
            $leadsQuery->whereDate('created_at', '>=', $from);
            $oppsQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $leadsQuery->whereDate('created_at', '<=', $to);
            $oppsQuery->whereDate('created_at', '<=', $to);
        }

        $leadRows = $leadsQuery
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $oppRows = $oppsQuery
            ->select('stage', DB::raw('count(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $leads = [];
        foreach (LeadStatus::cases() as $status) {
            $leads[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'total' => (int) ($leadRows[$status->value] ?? 0),
            ];
        }

        $opportunities = [];
        foreach (OpportunityStage::cases() as $stage) {
            $opportunities[] = [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'total' => (int) ($oppRows[$stage->value] ?? 0),
            ];
        }

        $totalLeads = array_sum(array_column($leads, 'total'));
        $converted = (int) ($leadRows[LeadStatus::Converted->value] ?? 0);

        return [
            'leads' => $leads,
            'opportunities' => $opportunities,
            'conversion_rate' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 2) : 0.0,
        ];
    }
}
