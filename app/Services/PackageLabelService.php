<?php

namespace App\Services;

use App\Enums\DeliveryChallanStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\PackageStatus;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\PackageLabel;
use App\Models\PackingUnit;
use App\Repositories\Interfaces\PackageLabelRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Package label generation and scan handling (M17).
 *
 * Packages are a physical view of a delivery challan; stock movement itself stays with the
 * challan's ledger post, so scanning verifies packed quantities rather than posting stock twice.
 */
class PackageLabelService
{
    /**
     * Payload prefix so a scanner can tell an ERP package label from other barcodes.
     */
    public const PAYLOAD_PREFIX = 'KEP';

    /**
     * Quantity comparison tolerance, matching the 4-decimal quantity columns.
     */
    public const QTY_TOLERANCE = 0.0001;

    public function __construct(
        protected PackageLabelRepositoryInterface $repository,
        protected NumberingService $numbering,
        protected UomConversionService $uom
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): PackageLabel
    {
        return $this->repository->findById($id);
    }

    /**
     * @return Collection<int, PackageLabel>
     */
    public function forChallan(int $deliveryChallanId): Collection
    {
        return $this->repository->forChallan($deliveryChallanId);
    }

    /**
     * Packed versus challan quantity for every line of a challan.
     *
     * @return list<array<string, mixed>>
     */
    public function packingSummary(int $deliveryChallanId): array
    {
        $challan = DeliveryChallan::query()
            ->with(['items.item:id,item_code,item_name', 'items.batch:id,batch_no'])
            ->findOrFail($deliveryChallanId);

        $packed = $this->repository->forChallan($deliveryChallanId)->groupBy('delivery_challan_item_id');
        $rows = [];

        foreach ($challan->items as $line) {
            /** @var Collection<int, PackageLabel> $packages */
            $packages = $packed->get($line->id, new Collection);
            $packedQty = round((float) $packages->sum(fn (PackageLabel $package) => (float) $package->quantity), 4);
            $challanQty = round((float) $line->quantity, 4);

            $rows[] = [
                'delivery_challan_item_id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'batch_id' => $line->batch_id,
                'batch_no' => $line->batch?->batch_no,
                'challan_qty' => $challanQty,
                'packed_qty' => $packedQty,
                'open_qty' => round(max(0, $challanQty - $packedQty), 4),
                'package_count' => $packages->count(),
                'verified_count' => $packages->where('status', '!=', PackageStatus::Packed)->count(),
            ];
        }

        return $rows;
    }

    /**
     * Print labels for a challan line, splitting the quantity across whole packages.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, PackageLabel>
     */
    public function generate(int $deliveryChallanId, array $data): Collection
    {
        return DB::transaction(function () use ($deliveryChallanId, $data): Collection {
            $challan = DeliveryChallan::query()->lockForUpdate()->findOrFail($deliveryChallanId);

            if (! in_array($challan->status, [DeliveryChallanStatus::Draft, DeliveryChallanStatus::Dispatched], true)) {
                throw ValidationException::withMessages([
                    'delivery_challan_id' => 'Labels can only be printed for draft or dispatched challans.',
                ]);
            }

            $line = DeliveryChallanItem::query()
                ->with('item:id,item_code,stock_uom_id')
                ->where('delivery_challan_id', $challan->id)
                ->findOrFail((int) $data['delivery_challan_item_id']);

            $unit = PackingUnit::query()->with('parent')->findOrFail((int) $data['packing_unit_id']);
            $this->assertUnitUsable($unit, (int) $line->item_id);

            $perPackage = round((float) ($data['quantity_per_package'] ?? $unit->baseQuantity()), 4);
            if ($perPackage <= 0) {
                throw ValidationException::withMessages([
                    'quantity_per_package' => 'Package contents must be greater than zero.',
                ]);
            }

            $count = (int) ($data['package_count'] ?? 1);
            if ($count < 1) {
                throw ValidationException::withMessages([
                    'package_count' => 'Print at least one label.',
                ]);
            }

            $this->assertWithinChallanQty($challan->id, $line, $perPackage * $count);

            $parentId = isset($data['parent_package_label_id']) ? (int) $data['parent_package_label_id'] : null;
            $secondaryQty = array_key_exists('secondary_quantity', $data)
                ? (float) $data['secondary_quantity']
                : null;

            if ($secondaryQty !== null) {
                $this->assertSecondaryQuantity((int) $line->item_id, $perPackage, $secondaryQty);
            }

            $labels = new Collection;
            for ($index = 0; $index < $count; $index++) {
                $labels->push($this->createLabel($challan, $line, $unit, $perPackage, $parentId, $secondaryQty));
            }

            return $labels;
        });
    }

    /**
     * Resolve a scanned code and, when asked, verify the package against its challan.
     *
     * @return array{package: PackageLabel, descendants: list<PackageLabel>, summary: list<array<string, mixed>>|null}
     */
    public function scan(string $code, bool $confirm = false): array
    {
        $labelNo = $this->labelNoFromPayload($code);
        $package = $this->repository->findByLabelNo($labelNo);

        if ($package === null) {
            throw ValidationException::withMessages([
                'code' => 'No package matches the scanned code.',
            ]);
        }

        if ($package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages([
                'code' => "Package {$package->label_no} was cancelled.",
            ]);
        }

        $descendants = $this->collectDescendants($package);
        $hasChildren = $descendants !== [];

        if ($confirm) {
            if ($hasChildren) {
                $package = $this->verifyPackageTree($package, $descendants);
            } else {
                $package = $this->verify($package);
            }
        }

        return [
            'package' => $this->repository->findById($package->id),
            'descendants' => $descendants,
            'summary' => $package->delivery_challan_id !== null
                ? $this->packingSummary((int) $package->delivery_challan_id)
                : null,
        ];
    }

    /**
     * Cancel a package that has not left the gate.
     */
    public function cancel(int $id): PackageLabel
    {
        $package = $this->repository->findById($id);

        if ($package->status === PackageStatus::Dispatched) {
            throw ValidationException::withMessages([
                'package' => 'A dispatched package cannot be cancelled.',
            ]);
        }

        $package->forceFill(['status' => PackageStatus::Cancelled])->save();

        return $this->repository->findById($id);
    }

    /**
     * Mark every verified package of a dispatched challan as gone.
     */
    public function markDispatchedForChallan(DeliveryChallan $challan): int
    {
        return PackageLabel::query()
            ->where('delivery_challan_id', $challan->id)
            ->whereIn('status', [PackageStatus::Packed->value, PackageStatus::Verified->value])
            ->update([
                'status' => PackageStatus::Dispatched->value,
                'dispatched_at' => now(),
            ]);
    }

    /**
     * Record a label reprint event (US-M17 reprint).
     */
    public function reprint(int $id, ?string $reason = null): PackageLabel
    {
        $package = $this->repository->findById($id);

        if ($package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages([
                'package' => 'Cancelled packages cannot be reprinted.',
            ]);
        }

        DB::table('package_label_reprints')->insert([
            'package_label_id' => $package->id,
            'reprinted_by' => Auth::id(),
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $package;
    }

    /**
     * Payload encoded into the QR code, e.g. `KEP|PKG-00001|ITM-0001|BATCH-A|50.0000`.
     */
    public function buildPayload(string $labelNo, string $itemCode, ?string $batchNo, float $quantity): string
    {
        return implode('|', [
            self::PAYLOAD_PREFIX,
            $labelNo,
            $itemCode,
            $batchNo ?? '-',
            number_format($quantity, 4, '.', ''),
        ]);
    }

    /**
     * Accept either a bare label number or a full scanned payload.
     */
    public function labelNoFromPayload(string $code): string
    {
        $code = trim($code);

        if (! str_contains($code, '|')) {
            return strtoupper($code);
        }

        $parts = explode('|', $code);

        if (($parts[0] ?? '') !== self::PAYLOAD_PREFIX || ($parts[1] ?? '') === '') {
            throw ValidationException::withMessages([
                'code' => 'The scanned code is not a valid package label.',
            ]);
        }

        return strtoupper($parts[1]);
    }

    protected function createLabel(
        DeliveryChallan $challan,
        DeliveryChallanItem $line,
        PackingUnit $unit,
        float $quantity,
        ?int $parentPackageLabelId = null,
        ?float $secondaryQuantity = null
    ): PackageLabel {
        $labelNo = $this->numbering->next(DocumentSeriesType::PackageLabel);

        $package = $this->repository->create([
            'label_no' => $labelNo,
            'qr_payload' => $this->buildPayload(
                $labelNo,
                (string) ($line->item?->item_code ?? ''),
                $line->batch?->batch_no,
                $quantity
            ),
            'packing_unit_id' => $unit->id,
            'parent_package_label_id' => $parentPackageLabelId,
            'item_id' => $line->item_id,
            'batch_id' => $line->batch_id,
            'warehouse_id' => $challan->warehouse_id,
            'delivery_challan_id' => $challan->id,
            'delivery_challan_item_id' => $line->id,
            'quantity' => $quantity,
            'secondary_quantity' => $secondaryQuantity,
            'status' => PackageStatus::Packed->value,
            'packed_at' => now(),
            'packed_by' => Auth::id(),
        ]);

        return $this->repository->findById($package->id);
    }

    protected function verify(PackageLabel $package): PackageLabel
    {
        if ($package->status === PackageStatus::Dispatched) {
            throw ValidationException::withMessages([
                'code' => "Package {$package->label_no} has already been dispatched.",
            ]);
        }

        if ($package->status === PackageStatus::Verified) {
            throw ValidationException::withMessages([
                'code' => "Package {$package->label_no} was already scanned at ".$package->verified_at?->format('d M Y H:i').'.',
            ]);
        }

        $package->forceFill([
            'status' => PackageStatus::Verified,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ])->save();

        return $this->repository->findById($package->id);
    }

    protected function assertUnitUsable(PackingUnit $unit, int $itemId): void
    {
        if (! $unit->is_active) {
            throw ValidationException::withMessages([
                'packing_unit_id' => "Packing unit {$unit->code} is inactive.",
            ]);
        }

        if ($unit->item_id !== null && (int) $unit->item_id !== $itemId) {
            throw ValidationException::withMessages([
                'packing_unit_id' => "Packing unit {$unit->code} belongs to a different item.",
            ]);
        }
    }

    protected function assertWithinChallanQty(int $challanId, DeliveryChallanItem $line, float $additionalQty): void
    {
        $alreadyPacked = (float) PackageLabel::query()
            ->where('delivery_challan_id', $challanId)
            ->where('delivery_challan_item_id', $line->id)
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->sum('quantity');

        if (round($alreadyPacked + $additionalQty, 4) - (float) $line->quantity > self::QTY_TOLERANCE) {
            throw ValidationException::withMessages([
                'package_count' => sprintf(
                    'Packing %s would exceed the challan line quantity; %s is still open.',
                    number_format($additionalQty, 4, '.', ''),
                    number_format(max(0, (float) $line->quantity - $alreadyPacked), 4, '.', '')
                ),
            ]);
        }
    }

    /**
     * Soft-validate secondary quantity against item UOM conversion when defined.
     */
    protected function assertSecondaryQuantity(int $itemId, float $primaryQty, float $secondaryQty): void
    {
        $item = \App\Models\Item::query()->find($itemId);

        if ($item === null || $item->sales_uom_id === null) {
            return;
        }

        try {
            $expected = $this->uom->convert(
                $itemId,
                $primaryQty,
                (int) $item->stock_uom_id,
                (int) $item->sales_uom_id
            );

            if (abs($expected - $secondaryQty) > max(self::QTY_TOLERANCE, $expected * 0.02)) {
                throw ValidationException::withMessages([
                    'secondary_quantity' => sprintf(
                        'Secondary quantity should be about %s for the entered primary quantity.',
                        number_format($expected, 4, '.', '')
                    ),
                ]);
            }
        } catch (ValidationException $e) {
            if (str_contains(collect($e->errors())->flatten()->first() ?? '', 'No conversion factor')) {
                return;
            }

            throw $e;
        }
    }

    /**
     * @return list<PackageLabel>
     */
    protected function collectDescendants(PackageLabel $package): array
    {
        $children = PackageLabel::query()
            ->where('parent_package_label_id', $package->id)
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->get();

        $all = [];

        foreach ($children as $child) {
            $all[] = $child;
            array_push($all, ...$this->collectDescendants($child));
        }

        return $all;
    }

    /**
     * Verify a parent package and every descendant in one atomic scan (all-or-nothing).
     *
     * @param  list<PackageLabel>  $descendants
     */
    protected function verifyPackageTree(PackageLabel $parent, array $descendants): PackageLabel
    {
        return DB::transaction(function () use ($parent, $descendants): PackageLabel {
            $this->verify($parent);

            foreach ($descendants as $child) {
                $fresh = $this->repository->findById($child->id);
                $this->verify($fresh);
            }

            return $this->repository->findById($parent->id);
        });
    }
}
