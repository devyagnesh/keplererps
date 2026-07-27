<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\PurchaseIndentStatus;
use App\Enums\RfqStatus;
use App\Models\Item;
use App\Models\PurchaseIndent;
use App\Models\PurchaseRfq;
use App\Models\PurchaseRfqItem;
use App\Models\PurchaseRfqQuote;
use App\Models\PurchaseRfqQuoteItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RFQ + comparative statement + award (M07).
 */
class PurchaseRfqService
{
    public function __construct(
        protected NumberingService $numbering,
        protected UomConversionService $uom,
        protected PurchaseOrderService $purchaseOrders
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, PurchaseRfq>
     */
    public function all()
    {
        return PurchaseRfq::query()
            ->with(['warehouse:id,code,name', 'items'])
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function find(int $id): PurchaseRfq
    {
        return PurchaseRfq::query()
            ->with(['warehouse', 'items.item', 'items.uom', 'quotes.supplier', 'quotes.items.rfqItem'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromIndent(int $indentId, array $data = []): PurchaseRfq
    {
        $indent = PurchaseIndent::query()->with('items.item')->findOrFail($indentId);
        if (! in_array($indent->status, [PurchaseIndentStatus::Approved, PurchaseIndentStatus::PartiallyOrdered], true)) {
            throw ValidationException::withMessages(['indent' => 'Only approved indents can raise an RFQ.']);
        }

        return DB::transaction(function () use ($indent, $data): PurchaseRfq {
            $rfq = PurchaseRfq::query()->create([
                'document_no' => $this->numbering->next(DocumentSeriesType::PurchaseRfq),
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(7)->toDateString(),
                'warehouse_id' => $indent->warehouse_id,
                'purchase_indent_id' => $indent->id,
                'status' => RfqStatus::Draft,
                'remarks' => $data['remarks'] ?? 'From indent '.$indent->document_no,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $sort = 0;
            foreach ($indent->items as $line) {
                if ($line->pendingQty() <= 0) {
                    continue;
                }
                $item = $line->item ?? Item::query()->findOrFail($line->item_id);
                $qty = $line->pendingQty();
                $uomId = (int) $line->uom_id;
                $baseQty = $this->safeBaseQty($item, $qty, $uomId);

                PurchaseRfqItem::query()->create([
                    'purchase_rfq_id' => $rfq->id,
                    'item_id' => $item->id,
                    'uom_id' => $uomId,
                    'quantity' => $qty,
                    'base_qty' => $baseQty,
                    'sort_order' => $sort++,
                ]);
            }

            if ($sort === 0) {
                throw ValidationException::withMessages(['items' => 'Indent has no pending quantity to RFQ.']);
            }

            return $this->find($rfq->id);
        });
    }

    public function markSent(int $id): PurchaseRfq
    {
        $rfq = PurchaseRfq::query()->findOrFail($id);
        if ($rfq->status !== RfqStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Only draft RFQs can be marked sent.']);
        }
        $rfq->forceFill(['status' => RfqStatus::Sent, 'updated_by' => Auth::id()])->save();

        return $this->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addQuote(int $rfqId, array $data): PurchaseRfqQuote
    {
        $rfq = $this->find($rfqId);
        if (in_array($rfq->status, [RfqStatus::Awarded, RfqStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['rfq' => 'Cannot quote an awarded or cancelled RFQ.']);
        }

        return DB::transaction(function () use ($rfq, $data): PurchaseRfqQuote {
            $quote = PurchaseRfqQuote::query()->updateOrCreate(
                [
                    'purchase_rfq_id' => $rfq->id,
                    'supplier_id' => (int) $data['supplier_id'],
                ],
                [
                    'quote_date' => $data['quote_date'] ?? now()->toDateString(),
                    'freight_amount' => round((float) ($data['freight_amount'] ?? 0), 2),
                    'lead_time_days' => isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );

            $rates = $data['rates'] ?? [];
            foreach ($rfq->items as $item) {
                $rate = (float) ($rates[$item->id] ?? $rates[(string) $item->id] ?? 0);
                PurchaseRfqQuoteItem::query()->updateOrCreate(
                    [
                        'purchase_rfq_quote_id' => $quote->id,
                        'purchase_rfq_item_id' => $item->id,
                    ],
                    [
                        'rate' => $rate,
                        'gst_rate' => (float) ($data['gst_rate'] ?? $item->item?->gst_rate ?? 18),
                    ]
                );
            }

            if ($rfq->status === RfqStatus::Sent || $rfq->status === RfqStatus::Draft) {
                $rfq->forceFill(['status' => RfqStatus::Quoted, 'updated_by' => Auth::id()])->save();
            }

            return $quote->fresh(['items', 'supplier']);
        });
    }

    /**
     * Comparative matrix rows: item × supplier rates.
     *
     * @return list<array<string, mixed>>
     */
    public function comparative(int $rfqId): array
    {
        $rfq = $this->find($rfqId);
        $rows = [];

        foreach ($rfq->items as $item) {
            $row = [
                'item_id' => $item->id,
                'item_code' => $item->item?->item_code,
                'item_name' => $item->item?->item_name,
                'quantity' => (float) $item->quantity,
                'quotes' => [],
                'lowest_supplier_id' => null,
                'lowest_rate' => null,
            ];

            $lowest = null;
            foreach ($rfq->quotes as $quote) {
                $qItem = $quote->items->firstWhere('purchase_rfq_item_id', $item->id);
                $rate = $qItem ? (float) $qItem->rate : null;
                $row['quotes'][] = [
                    'supplier_id' => $quote->supplier_id,
                    'supplier' => $quote->supplier?->party_name,
                    'rate' => $rate,
                    'lead_time_days' => $quote->lead_time_days,
                    'is_selected' => $quote->is_selected,
                ];
                if ($rate !== null && ($lowest === null || $rate < $lowest)) {
                    $lowest = $rate;
                    $row['lowest_rate'] = $rate;
                    $row['lowest_supplier_id'] = $quote->supplier_id;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Award a supplier quote and optionally create a PO.
     *
     * @param  array<string, mixed>  $data
     * @return array{rfq: PurchaseRfq, purchase_order_id: int|null}
     */
    public function award(int $rfqId, int $quoteId, array $data = []): array
    {
        return DB::transaction(function () use ($rfqId, $quoteId, $data): array {
            $rfq = PurchaseRfq::query()->with(['items.item', 'quotes.items'])->lockForUpdate()->findOrFail($rfqId);
            $quote = $rfq->quotes->firstWhere('id', $quoteId);
            if ($quote === null) {
                throw ValidationException::withMessages(['quote' => 'Quote not found on this RFQ.']);
            }

            $lowestId = null;
            $lowestTotal = null;
            foreach ($rfq->quotes as $candidate) {
                $total = $candidate->lineTotal();
                if ($lowestTotal === null || $total < $lowestTotal) {
                    $lowestTotal = $total;
                    $lowestId = $candidate->id;
                }
            }

            if ($lowestId !== $quote->id && empty($data['award_reason'])) {
                throw ValidationException::withMessages([
                    'award_reason' => 'Awarding a non-L1 quote requires a reason.',
                ]);
            }

            PurchaseRfqQuote::query()->where('purchase_rfq_id', $rfq->id)->update(['is_selected' => false]);
            $quote->forceFill([
                'is_selected' => true,
                'award_reason' => $data['award_reason'] ?? null,
            ])->save();

            $rfq->forceFill(['status' => RfqStatus::Awarded, 'updated_by' => Auth::id()])->save();

            $poId = null;
            if (($data['create_po'] ?? true) !== false) {
                $items = [];
                foreach ($rfq->items as $rfqItem) {
                    $qItem = $quote->items->firstWhere('purchase_rfq_item_id', $rfqItem->id);
                    $items[] = [
                        'item_id' => $rfqItem->item_id,
                        'uom_id' => $rfqItem->uom_id,
                        'quantity' => (float) $rfqItem->quantity,
                        'rate' => (float) ($qItem?->rate ?? 0),
                        'gst_rate' => (float) ($qItem?->gst_rate ?? 18),
                    ];
                }

                $leadDays = max(1, (int) ($quote->lead_time_days ?? 7));
                $po = $this->purchaseOrders->create([
                    'document_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->addDays($leadDays)->toDateString(),
                    'supplier_id' => $quote->supplier_id,
                    'warehouse_id' => $rfq->warehouse_id,
                    'purchase_indent_id' => $rfq->purchase_indent_id,
                    'purchase_rfq_id' => $rfq->id,
                    'remarks' => 'Awarded from RFQ '.$rfq->document_no,
                    'items' => $items,
                ]);
                $poId = $po->id;

                if ($rfq->purchase_indent_id) {
                    $this->bumpIndentOrdered($rfq->purchase_indent_id, $rfq);
                }
            }

            return ['rfq' => $this->find($rfqId), 'purchase_order_id' => $poId];
        });
    }

    protected function bumpIndentOrdered(int $indentId, PurchaseRfq $rfq): void
    {
        $indent = PurchaseIndent::query()->with('items')->lockForUpdate()->find($indentId);
        if ($indent === null) {
            return;
        }

        foreach ($rfq->items as $rfqItem) {
            $line = $indent->items->firstWhere('item_id', $rfqItem->item_id);
            if ($line === null) {
                continue;
            }
            $line->forceFill([
                'ordered_qty' => round((float) $line->ordered_qty + (float) $rfqItem->quantity, 4),
            ])->save();
        }

        $indent->refresh()->load('items');
        $pending = $indent->items->sum(fn ($l) => $l->pendingQty());
        $indent->forceFill([
            'status' => $pending > 0.0001
                ? PurchaseIndentStatus::PartiallyOrdered
                : PurchaseIndentStatus::Ordered,
        ])->save();
    }

    protected function safeBaseQty(Item $item, float $qty, int $uomId): float
    {
        try {
            return $this->uom->toStockQty($item, $qty, $uomId);
        } catch (\Throwable) {
            return $qty;
        }
    }
}
