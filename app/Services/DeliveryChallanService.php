<?php

namespace App\Services;

use App\Enums\DeliveryChallanStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\NotificationEvent;
use App\Enums\SalesOrderStatus;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Enums\TransportMode;
use App\Models\Batch;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\SystemSetting;
use App\Models\Transporter;
use App\Repositories\Interfaces\DeliveryChallanRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Delivery challan business logic (M12 / US-M12-02, US-M12-04 stock path).
 */
class DeliveryChallanService
{
    /** Default Indian e-way bill threshold (INR) when no setting is configured. */
    public const DEFAULT_EWAY_THRESHOLD = 50000.0;

    public function __construct(
        protected DeliveryChallanRepositoryInterface $repository,
        protected SalesOrderService $salesOrders,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
        protected ActivityLogService $activityLog,
        protected NotificationDispatchService $notifications,
        protected PackageLabelService $packages,
        protected GstGspService $gsp,
        protected WhatsAppService $whatsapp
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): DeliveryChallan
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliveryChallan
    {
        return DB::transaction(function () use ($data): DeliveryChallan {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $order = SalesOrder::query()->with('items.item')->findOrFail((int) $data['sales_order_id']);
            $this->assertOrderDispatchable($order, $data['document_date'] ?? null);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::DeliveryChallan);
            $data['customer_id'] = $order->customer_id;
            $data['warehouse_id'] = $order->warehouse_id;
            $data['status'] = DeliveryChallanStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['dispatch_value'] = 0;
            $data['eway_required'] = false;

            $this->normalizeTransportFields($data);
            $challan = $this->repository->create($data);
            $this->syncItems($challan, $order, $lines);
            $this->refreshHeaderTotals($challan->id);

            return $this->repository->findById($challan->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): DeliveryChallan
    {
        return DB::transaction(function () use ($id, $data): DeliveryChallan {
            $challan = $this->repository->findById($id);
            if (! $challan->status->isEditable()) {
                throw ValidationException::withMessages(['challan' => 'Only draft challans can be edited.']);
            }

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status'], $data['sales_order_id']);

            $order = SalesOrder::query()->with('items.item')->findOrFail($challan->sales_order_id);
            $this->assertOrderDispatchable($order, $data['document_date'] ?? $challan->document_date->toDateString());

            $data['updated_by'] = Auth::id();
            $this->normalizeTransportFields($data);
            $this->repository->update($id, $data);
            $this->syncItems($challan, $order, $lines);
            $this->refreshHeaderTotals($id);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $challan = $this->repository->findById($id);
        if (! $challan->status->isEditable()) {
            throw ValidationException::withMessages(['challan' => 'Only draft challans can be deleted.']);
        }

        return $this->repository->delete($id);
    }

    /**
     * Post stock OUT and mark dispatched (gate exit).
     */
    public function dispatch(int $id): DeliveryChallan
    {
        return DB::transaction(function () use ($id): DeliveryChallan {
            $challan = DeliveryChallan::query()
                ->with(['items.item', 'items.salesOrderItem', 'salesOrder.customer', 'customer'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($challan->status !== DeliveryChallanStatus::Draft) {
                throw ValidationException::withMessages(['challan' => 'Only draft challans can be dispatched.']);
            }
            if ($challan->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one dispatch line.']);
            }

            $this->assertEwayReady($challan);

            $order = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($challan->sales_order_id);

            foreach ($challan->items as $line) {
                $soLine = SalesOrderItem::query()->lockForUpdate()->findOrFail($line->sales_order_item_id);
                $qty = (float) $line->quantity;
                if ($qty - $soLine->pendingDeliveryQty() > 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => 'Dispatch quantity exceeds pending sales order quantity.',
                    ]);
                }

                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $challan->warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::Delivery,
                    'posting_at' => $challan->document_date->copy()->startOfDay(),
                    'qty_in' => 0,
                    'qty_out' => $qty,
                    'source' => $challan,
                    'remarks' => $challan->document_no,
                ]);

                $soLine->forceFill([
                    'delivered_qty' => round((float) $soLine->delivered_qty + $qty, 4),
                ])->save();

                $this->releaseCommitted($challan->warehouse_id, $line->item_id, $qty);
            }

            $challan->forceFill([
                'status' => DeliveryChallanStatus::Dispatched,
                'dispatched_at' => now(),
                'dispatched_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->packages->markDispatchedForChallan($challan);

            $order->refresh()->load('items');
            $this->salesOrders->refreshFulfillmentStatus($order);

            $this->activityLog->log(
                event: 'status_changed',
                description: "Delivery challan {$challan->document_no} dispatched.",
                subject: $challan,
                properties: ['new_status' => DeliveryChallanStatus::Dispatched->value],
                logName: 'dispatch'
            );

            $this->notifications->dispatch(
                NotificationEvent::DeliveryChallanDispatched,
                [
                    'document_no' => $challan->document_no,
                    'customer' => $challan->salesOrder?->customer?->party_name
                        ?? $challan->customer?->party_name
                        ?? '',
                    'vehicle_number' => (string) ($challan->vehicle_number ?? ''),
                    'transporter' => (string) ($challan->transporter?->name ?? $challan->transporter_gstin ?? ''),
                    'lr_number' => (string) ($challan->lr_number ?? ''),
                    'eway_bill_number' => (string) ($challan->eway_bill_number ?? ''),
                ],
                route('admin.delivery-challans.show', $challan)
            );

            $this->notifyCustomerDispatch($challan);

            return $this->repository->findById($id);
        });
    }

    /**
     * N13 — WhatsApp template to customer mobile when goods leave the gate.
     */
    protected function notifyCustomerDispatch(DeliveryChallan $challan): void
    {
        $customer = $challan->salesOrder?->customer ?? $challan->customer;
        if ($customer === null) {
            return;
        }

        $contact = $customer->contacts()
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('mobile')
            ->orderByDesc('is_primary')
            ->first()
            ?? $customer->contacts()->whereNotNull('mobile')->orderByDesc('is_primary')->first();

        $mobile = $contact?->mobile;
        if (! filled($mobile)) {
            return;
        }

        $this->whatsapp->sendTemplate(
            (string) $mobile,
            (string) app(SystemSettingService::class)->get('whatsapp_template_dispatch', 'goods_dispatched'),
            [
                (string) $challan->document_no,
                (string) ($challan->vehicle_number ?? '-'),
                (string) ($challan->transporter?->name ?? $challan->transporter_gstin ?? '-'),
                (string) ($challan->lr_number ?? '-'),
                (string) ($challan->eway_bill_number ?? '-'),
            ],
            'en',
            ['event' => NotificationEvent::GoodsDispatchedCustomer->value, 'challan_id' => $challan->id]
        );

        $this->notifications->dispatch(
            NotificationEvent::GoodsDispatchedCustomer,
            [
                'document_no' => $challan->document_no,
                'vehicle_number' => (string) ($challan->vehicle_number ?? ''),
                'eway_bill_number' => (string) ($challan->eway_bill_number ?? ''),
            ],
            route('admin.delivery-challans.show', $challan)
        );
    }

    /**
     * Submit e-way bill via GSP and persist the number (US-M12-02).
     *
     * @return array{challan: DeliveryChallan, status: string, eway_bill_number: string|null}
     */
    public function submitEwayBill(int $id): array
    {
        $payload = $this->ewayBillPayload($id);
        $result = $this->gsp->submitEwayBill($payload);

        $challan = DeliveryChallan::query()->findOrFail($id);
        \App\Models\EwaySubmissionLog::query()->create([
            'delivery_challan_id' => $challan->id,
            'status' => $result['status'],
            'eway_bill_number' => $result['eway_bill_number'],
            'payload' => $payload,
            'response' => $result['response'],
            'created_by' => Auth::id(),
        ]);

        if (filled($result['eway_bill_number'])) {
            $challan->forceFill([
                'eway_bill_number' => $result['eway_bill_number'],
                'updated_by' => Auth::id(),
            ])->save();
        }

        return [
            'challan' => $this->repository->findById($id),
            'status' => $result['status'],
            'eway_bill_number' => $result['eway_bill_number'],
        ];
    }

    /**
     * Mark Delivered when proof of delivery is uploaded (US-M12 POD).
     *
     * @param  array{pod: \Illuminate\Http\UploadedFile}  $data
     */
    public function markDelivered(int $id, array $data): DeliveryChallan
    {
        return DB::transaction(function () use ($id, $data): DeliveryChallan {
            $challan = DeliveryChallan::query()->lockForUpdate()->findOrFail($id);
            if ($challan->status !== DeliveryChallanStatus::Dispatched) {
                throw ValidationException::withMessages(['challan' => 'Only dispatched challans can receive POD.']);
            }

            $path = $data['pod']->store('pods/'.now()->format('Y'), 'local');

            $challan->forceFill([
                'status' => DeliveryChallanStatus::Delivered,
                'delivered_at' => now(),
                'pod_path' => $path,
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingLinesForOrder(int $salesOrderId): array
    {
        $order = SalesOrder::query()->with(['items.item:id,item_code,item_name,tracking_type'])->findOrFail($salesOrderId);
        $this->assertOrderDispatchable($order);

        $rows = [];
        foreach ($order->items as $line) {
            $pending = $line->pendingDeliveryQty();
            if ($pending <= 0) {
                continue;
            }
            $rows[] = [
                'sales_order_item_id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'tracking_type' => $line->item?->tracking_type?->value,
                'pending_qty' => $pending,
                'rate' => (float) $line->rate,
                'uom_id' => $line->uom_id,
                'description' => $line->description,
            ];
        }

        return $rows;
    }

    /**
     * Downloadable e-way bill payload for GSP / portal (US-M12-02).
     *
     * @return array<string, mixed>
     */
    public function ewayBillPayload(int $id): array
    {
        $challan = $this->repository->findById($id);
        if (! $challan->eway_required) {
            throw ValidationException::withMessages(['eway' => 'E-way bill is not required for this challan.']);
        }

        return [
            'document_no' => $challan->document_no,
            'document_date' => $challan->document_date?->format('Y-m-d'),
            'value' => (float) $challan->dispatch_value,
            'transport_mode' => $challan->transport_mode->value,
            'vehicle_number' => $challan->vehicle_number,
            'transporter_id' => $challan->transporter_id,
            'transporter_gstin' => $challan->transporter_gstin,
            'distance_km' => $challan->distance_km,
            'from_warehouse' => $challan->warehouse?->code,
            'to_customer' => $challan->customer?->party_code,
            'items' => $challan->items->map(fn (DeliveryChallanItem $line): array => [
                'item_code' => $line->item?->item_code,
                'quantity' => (float) $line->quantity,
                'rate' => (float) $line->rate,
            ])->all(),
        ];
    }

    /** @param  list<array<string, mixed>>  $lines */
    protected function syncItems(DeliveryChallan $challan, SalesOrder $order, array $lines): void
    {
        $challan->items()->delete();
        $soItems = $order->items->keyBy('id');
        $sortOrder = 0;

        foreach (array_values($lines) as $line) {
            if (empty($line['sales_order_item_id']) || empty($line['quantity'])) {
                continue;
            }
            $soLine = $soItems->get((int) $line['sales_order_item_id']);
            if ($soLine === null) {
                throw ValidationException::withMessages(['items' => 'Challan line does not belong to the sales order.']);
            }

            $qty = round((float) $line['quantity'], 4);
            if ($qty - $soLine->pendingDeliveryQty() > 0.0001) {
                throw ValidationException::withMessages(['items' => 'Quantity exceeds pending dispatch quantity.']);
            }

            $item = Item::query()->findOrFail($soLine->item_id);
            $batchId = isset($line['batch_id']) && $line['batch_id'] !== '' ? (int) $line['batch_id'] : null;

            foreach ($this->batchSplit($challan, $item, $batchId, $qty) as $split) {
                DeliveryChallanItem::query()->create([
                    'delivery_challan_id' => $challan->id,
                    'sales_order_item_id' => $soLine->id,
                    'item_id' => $soLine->item_id,
                    'uom_id' => $soLine->uom_id,
                    'batch_id' => $split['batch_id'],
                    'description' => $line['description'] ?? $soLine->description,
                    'quantity' => $split['quantity'],
                    'rate' => (float) $soLine->rate,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        if ($challan->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Add at least one dispatch line.']);
        }
    }

    /**
     * Resolve how a dispatch quantity maps onto batches.
     *
     * A batch chosen by the user is validated for expiry; when no batch is supplied for a
     * batch-tracked item the quantity is allocated FEFO and may split into several lines.
     *
     * @return list<array{batch_id: int|null, quantity: float}>
     *
     * @throws ValidationException
     */
    protected function batchSplit(DeliveryChallan $challan, Item $item, ?int $batchId, float $qty): array
    {
        if ($item->tracking_type !== TrackingType::Batch) {
            return [['batch_id' => null, 'quantity' => $qty]];
        }

        $asOf = Carbon::parse($challan->document_date ?? now());

        if ($batchId === null) {
            return $this->ledger->allocateFefo((int) $item->id, (int) $challan->warehouse_id, $qty, $asOf);
        }

        $batch = Batch::query()->find($batchId);
        if ($batch === null || (int) $batch->item_id !== (int) $item->id) {
            throw ValidationException::withMessages(['items' => "Selected batch does not belong to {$item->item_code}."]);
        }
        if ($this->ledger->batchIsExpired($item, $batch, $asOf)) {
            throw ValidationException::withMessages([
                'items' => "Batch {$batch->batch_no} of {$item->item_code} has expired and cannot be dispatched.",
            ]);
        }

        return [['batch_id' => $batchId, 'quantity' => $qty]];
    }

    protected function refreshHeaderTotals(int $id): void
    {
        $challan = DeliveryChallan::query()->with('items')->findOrFail($id);
        $value = round((float) $challan->items->sum(fn (DeliveryChallanItem $l) => (float) $l->quantity * (float) $l->rate), 2);
        $threshold = $this->ewayThreshold();
        $required = $value >= $threshold;

        $challan->forceFill([
            'dispatch_value' => $value,
            'eway_required' => $required,
        ])->save();
    }

    protected function assertEwayReady(DeliveryChallan $challan): void
    {
        $challan->refresh();
        if (! $challan->eway_required) {
            return;
        }
        $this->assertEwayFields($challan);
        if (empty($challan->eway_bill_number) || ! preg_match('/^\d{12}$/', (string) $challan->eway_bill_number)) {
            throw ValidationException::withMessages([
                'eway_bill_number' => 'A valid 12-digit e-way bill number is required before the vehicle leaves.',
            ]);
        }
    }

    protected function assertEwayFields(DeliveryChallan $challan): void
    {
        if ($challan->transport_mode === TransportMode::Road && empty($challan->vehicle_number)) {
            throw ValidationException::withMessages(['vehicle_number' => 'Vehicle number is required for road transport.']);
        }
        if ($challan->transporter_id === null && empty($challan->transporter_gstin)) {
            throw ValidationException::withMessages(['transporter_id' => 'Transporter or transporter GSTIN is required for e-way bill.']);
        }
        if ($challan->distance_km === null || $challan->distance_km < 1 || $challan->distance_km > 4000) {
            throw ValidationException::withMessages(['distance_km' => 'Distance (1–4000 km) is required for e-way bill.']);
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function normalizeTransportFields(array &$data): void
    {
        if (! empty($data['vehicle_number'])) {
            $data['vehicle_number'] = strtoupper(preg_replace('/\s+/', '', (string) $data['vehicle_number']) ?? '');
        }
        if (! empty($data['transporter_id'])) {
            $transporter = Transporter::query()->findOrFail((int) $data['transporter_id']);
            if (! $transporter->is_active) {
                throw ValidationException::withMessages(['transporter_id' => 'Transporter is inactive.']);
            }
            if (empty($data['transporter_gstin'])) {
                $data['transporter_gstin'] = $transporter->gstin;
            }
        }
        if (isset($data['gross_weight'], $data['net_weight'])
            && $data['gross_weight'] !== null
            && $data['net_weight'] !== null
            && (float) $data['gross_weight'] < (float) $data['net_weight']) {
            throw ValidationException::withMessages(['gross_weight' => 'Gross weight must be greater than or equal to net weight.']);
        }
        if (! empty($data['lr_date']) && ! empty($data['document_date']) && $data['lr_date'] < $data['document_date']) {
            throw ValidationException::withMessages(['lr_date' => 'LR date cannot be before the challan date.']);
        }
    }

    protected function assertOrderDispatchable(SalesOrder $order, ?string $documentDate = null): void
    {
        if (! in_array($order->status, [
            SalesOrderStatus::Confirmed,
            SalesOrderStatus::PartiallyDelivered,
            SalesOrderStatus::Delivered,
        ], true)) {
            throw ValidationException::withMessages([
                'sales_order_id' => 'Sales order must be confirmed or partially delivered.',
            ]);
        }
        if ($order->status === SalesOrderStatus::OnHold || $order->status === SalesOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['sales_order_id' => 'Sales order is not open for dispatch.']);
        }
        if ($documentDate !== null && $order->document_date && $documentDate < $order->document_date->toDateString()) {
            throw ValidationException::withMessages([
                'document_date' => 'Challan date cannot be before the sales order date.',
            ]);
        }
    }

    protected function releaseCommitted(int $warehouseId, int $itemId, float $qty): void
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', 0)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->forceFill([
            'committed_qty' => max(0, round((float) $balance->committed_qty - $qty, 4)),
        ])->save();
    }

    protected function ewayThreshold(): float
    {
        $setting = SystemSetting::query()->where('setting_key', 'eway_bill_threshold')->first();

        return $setting !== null ? (float) $setting->setting_value : self::DEFAULT_EWAY_THRESHOLD;
    }
}
