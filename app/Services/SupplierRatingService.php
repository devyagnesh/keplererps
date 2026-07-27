<?php

namespace App\Services;

use App\Enums\InspectionStatus;
use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\SupplierRating;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Supplier performance rating from OTIF and incoming QC (US-M07-05).
 */
class SupplierRatingService
{
    /**
     * Recompute ratings for all suppliers in a period.
     *
     * @return list<SupplierRating>
     */
    public function recompute(?string $from = null, ?string $to = null): array
    {
        $from ??= now()->subMonths(3)->startOfMonth()->toDateString();
        $to ??= now()->toDateString();

        $suppliers = Party::query()
            ->whereIn('party_type', [PartyType::Supplier->value, PartyType::Both->value])
            ->where('is_active', true)
            ->get(['id']);

        $ratings = [];
        foreach ($suppliers as $supplier) {
            $ratings[] = $this->recomputeParty($supplier->id, $from, $to);
        }

        return $ratings;
    }

    public function recomputeParty(int $partyId, string $from, string $to): SupplierRating
    {
        $pos = PurchaseOrder::query()
            ->where('supplier_id', $partyId)
            ->whereIn('status', [
                PurchaseOrderStatus::Approved->value,
                PurchaseOrderStatus::Sent->value,
                PurchaseOrderStatus::PartiallyReceived->value,
                PurchaseOrderStatus::Received->value,
                PurchaseOrderStatus::Closed->value,
            ])
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->get(['id', 'document_date', 'expected_delivery_date']);

        $poCount = $pos->count();
        $onTime = 0;

        foreach ($pos as $po) {
            $expected = $po->expected_delivery_date?->toDateString()
                ?? $po->document_date?->copy()->addDays(7)->toDateString();
            $firstGrn = GoodsReceipt::query()
                ->where('purchase_order_id', $po->id)
                ->where('status', 'posted')
                ->orderBy('document_date')
                ->value('document_date');

            if ($firstGrn !== null && Carbon::parse($firstGrn)->toDateString() <= $expected) {
                $onTime++;
            }
        }

        $qcFails = QcInspection::query()
            ->where('status', InspectionStatus::Completed->value)
            ->where('overall_result', 'fail')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->whereHasMorph('source', [GoodsReceipt::class], function ($q) use ($partyId): void {
                $q->whereHas('purchaseOrder', fn ($po) => $po->where('supplier_id', $partyId));
            })
            ->count();

        $otif = $poCount > 0 ? round(($onTime / $poCount) * 100, 2) : 0.0;
        $quality = $poCount > 0 ? round(max(0, 100 - (($qcFails / max($poCount, 1)) * 100)), 2) : 100.0;
        $overall = round(($otif * 0.6) + ($quality * 0.4), 2);

        return SupplierRating::query()->updateOrCreate(
            [
                'party_id' => $partyId,
                'period_from' => $from,
                'period_to' => $to,
            ],
            [
                'po_count' => $poCount,
                'on_time_count' => $onTime,
                'qc_fail_count' => $qcFails,
                'otif_score' => $otif,
                'quality_score' => $quality,
                'overall_score' => $overall,
                'computed_at' => now(),
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, SupplierRating>
     */
    public function latest()
    {
        return SupplierRating::query()
            ->with('party:id,party_code,party_name')
            ->orderByDesc('period_to')
            ->orderByDesc('overall_score')
            ->limit(200)
            ->get();
    }
}
