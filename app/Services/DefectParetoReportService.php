<?php

namespace App\Services;

use App\Models\DefectReason;
use App\Models\ProductionEntry;
use App\Models\QcInspectionReading;
use Illuminate\Support\Facades\DB;

/**
 * Defect Pareto from production entries and failed QC readings (US-M10-05).
 */
class DefectParetoReportService
{
    /**
     * @return list<array{defect_reason_id: int|null, code: string, name: string, count: int, share: float}>
     */
    public function report(?string $from = null, ?string $to = null): array
    {
        $production = ProductionEntry::query()
            ->whereNotNull('defect_reason_id')
            ->when($from, fn ($q) => $q->whereDate('document_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('document_date', '<=', $to))
            ->select('defect_reason_id', DB::raw('count(*) as total'))
            ->groupBy('defect_reason_id')
            ->pluck('total', 'defect_reason_id');

        $qc = QcInspectionReading::query()
            ->whereNotNull('defect_reason_id')
            ->where('result', 'fail')
            ->when($from || $to, function ($q) use ($from, $to): void {
                $q->whereHas('inspection', function ($iq) use ($from, $to): void {
                    if ($from) {
                        $iq->whereDate('document_date', '>=', $from);
                    }
                    if ($to) {
                        $iq->whereDate('document_date', '<=', $to);
                    }
                });
            })
            ->select('defect_reason_id', DB::raw('count(*) as total'))
            ->groupBy('defect_reason_id')
            ->pluck('total', 'defect_reason_id');

        $counts = [];
        foreach ($production as $id => $total) {
            $counts[(int) $id] = ($counts[(int) $id] ?? 0) + (int) $total;
        }
        foreach ($qc as $id => $total) {
            $counts[(int) $id] = ($counts[(int) $id] ?? 0) + (int) $total;
        }

        arsort($counts);
        $grand = array_sum($counts) ?: 1;
        $reasons = DefectReason::query()->whereIn('id', array_keys($counts))->get()->keyBy('id');

        $rows = [];
        foreach ($counts as $id => $count) {
            $reason = $reasons->get($id);
            $rows[] = [
                'defect_reason_id' => $id,
                'code' => $reason?->code ?? (string) $id,
                'name' => $reason?->name ?? 'Unknown',
                'count' => $count,
                'share' => round(($count / $grand) * 100, 2),
            ];
        }

        return $rows;
    }
}
