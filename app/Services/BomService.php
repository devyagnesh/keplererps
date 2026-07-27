<?php

namespace App\Services;

use App\Enums\BomIssueMethod;
use App\Enums\DocumentSeriesType;
use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\BomOperation;
use App\Models\BomOutput;
use App\Models\Item;
use App\Repositories\Interfaces\BomRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bill of Materials business logic (M04).
 */
class BomService
{
    public function __construct(
        protected BomRepositoryInterface $repository,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Bom
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bom
    {
        return DB::transaction(function () use ($data): Bom {
            $components = $data['components'] ?? [];
            $operations = $data['operations'] ?? [];
            $outputs = $data['outputs'] ?? [];
            unset($data['components'], $data['operations'], $data['outputs']);

            $item = Item::query()->findOrFail((int) $data['item_id']);
            $this->assertManufacturable($item);

            $data['bom_number'] = $this->numbering->next(DocumentSeriesType::Bom);
            $data['version'] = $this->repository->nextVersionForItem((int) $data['item_id']);
            $data['output_uom_id'] = $item->stock_uom_id;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            $this->assertNoActiveOverlap(
                (int) $data['item_id'],
                (string) $data['valid_from'],
                $data['valid_to'] ?? null,
                null,
                (bool) $data['is_active']
            );

            $this->assertNoCircularReferences((int) $data['item_id'], $components);

            $bom = $this->repository->create($data);
            $this->syncLines($bom, $components, $operations, $outputs);
            $this->recalculateCosts($bom->id);

            return $this->repository->findById($bom->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Bom
    {
        return DB::transaction(function () use ($id, $data): Bom {
            $bom = $this->repository->findById($id);
            $components = $data['components'] ?? [];
            $operations = $data['operations'] ?? [];
            $outputs = $data['outputs'] ?? [];
            unset($data['components'], $data['operations'], $data['outputs'], $data['bom_number'], $data['version'], $data['item_id']);

            $data['updated_by'] = Auth::id();
            $data['is_active'] = (bool) ($data['is_active'] ?? $bom->is_active);

            $this->assertNoActiveOverlap(
                (int) $bom->item_id,
                (string) ($data['valid_from'] ?? $bom->valid_from->toDateString()),
                $data['valid_to'] ?? $bom->valid_to?->toDateString(),
                $bom->id,
                (bool) $data['is_active']
            );

            $this->assertNoCircularReferences((int) $bom->item_id, $components);

            $this->repository->update($id, $data);
            $this->syncLines($bom, $components, $operations, $outputs);
            $this->recalculateCosts($id);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Create a new version copied from an existing BOM (US-M04-02).
     */
    public function createNewVersion(int $id): Bom
    {
        return DB::transaction(function () use ($id): Bom {
            $source = $this->repository->findById($id);

            $payload = [
                'item_id' => $source->item_id,
                'output_quantity' => (float) $source->output_quantity,
                'valid_from' => now()->toDateString(),
                'valid_to' => null,
                'is_active' => true,
                'overhead_percent' => (float) $source->overhead_percent,
                'notes' => $source->notes,
                'components' => $source->components->map(fn (BomComponent $c): array => [
                    'component_item_id' => $c->component_item_id,
                    'quantity' => (float) $c->quantity,
                    'uom_id' => $c->uom_id,
                    'wastage_percent' => (float) $c->wastage_percent,
                    'is_critical' => $c->is_critical,
                    'issue_method' => $c->issue_method->value,
                    'operation_sequence' => $c->operation_sequence,
                ])->all(),
                'operations' => $source->operations->map(fn (BomOperation $o): array => [
                    'sequence' => $o->sequence,
                    'manufacturing_operation_id' => $o->manufacturing_operation_id,
                    'work_centre_id' => $o->work_centre_id,
                    'setup_time_minutes' => (float) $o->setup_time_minutes,
                    'run_time_per_unit_minutes' => (float) $o->run_time_per_unit_minutes,
                    'machine_rate_per_hour' => (float) $o->machine_rate_per_hour,
                    'labour_rate_per_hour' => (float) $o->labour_rate_per_hour,
                    'operators_required' => $o->operators_required,
                    'is_outsourced' => $o->is_outsourced,
                    'vendor_id' => $o->vendor_id,
                    'outsourced_rate' => $o->outsourced_rate !== null ? (float) $o->outsourced_rate : null,
                    'quality_check_required' => $o->quality_check_required,
                ])->all(),
                'outputs' => $source->outputs->map(fn (BomOutput $o): array => [
                    'item_id' => $o->item_id,
                    'expected_quantity' => (float) $o->expected_quantity,
                    'uom_id' => $o->uom_id,
                    'cost_allocation_percent' => (float) $o->cost_allocation_percent,
                    'output_type' => $o->output_type,
                ])->all(),
            ];

            if ($source->is_active) {
                $this->repository->update($source->id, [
                    'is_active' => false,
                    'valid_to' => now()->subDay()->toDateString(),
                    'updated_by' => Auth::id(),
                ]);
            }

            return $this->create($payload);
        });
    }

    /**
     * Required component quantities for an order qty (US-M04-01).
     *
     * @return list<array<string, mixed>>
     */
    public function explodeRequirements(int $bomId, float $orderQuantity): array
    {
        $bom = $this->repository->findById($bomId);
        $outputQty = (float) $bom->output_quantity;

        if ($orderQuantity <= 0 || $outputQty <= 0) {
            throw ValidationException::withMessages([
                'order_quantity' => 'Order quantity and BOM output quantity must be greater than zero.',
            ]);
        }

        $lines = [];
        foreach ($bom->components as $component) {
            $wastage = (float) $component->wastage_percent;
            $required = ($component->quantity / $outputQty) * $orderQuantity * (1 + ($wastage / 100));
            $lines[] = [
                'component_item_id' => $component->component_item_id,
                'item_code' => $component->componentItem?->item_code,
                'item_name' => $component->componentItem?->item_name,
                'uom' => $component->uom?->code,
                'bom_quantity' => (float) $component->quantity,
                'wastage_percent' => $wastage,
                'required_quantity' => round($required, 4),
                'is_critical' => $component->is_critical,
            ];
        }

        return $lines;
    }

    /**
     * Recalculate rolled-up standard cost (US-M04-03).
     */
    public function recalculateCosts(int $bomId): Bom
    {
        $bom = $this->repository->findById($bomId);
        $material = 0.0;

        foreach ($bom->components as $component) {
            $unitCost = (float) ($component->componentItem?->standard_cost ?? 0);
            $qtyWithWaste = (float) $component->quantity * (1 + ((float) $component->wastage_percent / 100));
            $material += $qtyWithWaste * $unitCost;
        }

        $operations = 0.0;
        $outputQty = max((float) $bom->output_quantity, 0.0001);

        foreach ($bom->operations as $operation) {
            if ($operation->is_outsourced) {
                $operations += ((float) ($operation->outsourced_rate ?? 0)) * $outputQty;

                continue;
            }

            $setupHours = (float) $operation->setup_time_minutes / 60;
            $runHours = ((float) $operation->run_time_per_unit_minutes * $outputQty) / 60;
            $machine = ($setupHours + $runHours) * (float) $operation->machine_rate_per_hour;
            $labour = ($setupHours + $runHours) * (float) $operation->labour_rate_per_hour * max(1, (int) $operation->operators_required);
            $operations += $machine + $labour;
        }

        $subtotal = $material + $operations;
        $total = $subtotal * (1 + ((float) $bom->overhead_percent / 100));

        $bom->forceFill([
            'rolled_material_cost' => round($material, 2),
            'rolled_operation_cost' => round($operations, 2),
            'rolled_total_cost' => round($total, 2),
        ])->save();

        return $bom->fresh();
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @param  list<array<string, mixed>>  $operations
     * @param  list<array<string, mixed>>  $outputs
     */
    protected function syncLines(Bom $bom, array $components, array $operations, array $outputs): void
    {
        $bom->components()->delete();
        $bom->operations()->delete();
        $bom->outputs()->delete();

        foreach (array_values($components) as $index => $line) {
            if (empty($line['component_item_id']) || empty($line['quantity'])) {
                continue;
            }

            BomComponent::query()->create([
                'bom_id' => $bom->id,
                'component_item_id' => (int) $line['component_item_id'],
                'quantity' => round((float) $line['quantity'], 4),
                'uom_id' => (int) $line['uom_id'],
                'wastage_percent' => round((float) ($line['wastage_percent'] ?? 0), 2),
                'is_critical' => (bool) ($line['is_critical'] ?? false),
                'issue_method' => $line['issue_method'] ?? BomIssueMethod::Manual->value,
                'operation_sequence' => ! empty($line['operation_sequence']) ? (int) $line['operation_sequence'] : null,
                'sort_order' => $index,
            ]);
        }

        foreach ($operations as $line) {
            if (empty($line['manufacturing_operation_id']) || empty($line['sequence'])) {
                continue;
            }

            BomOperation::query()->create([
                'bom_id' => $bom->id,
                'sequence' => (int) $line['sequence'],
                'manufacturing_operation_id' => (int) $line['manufacturing_operation_id'],
                'work_centre_id' => ! empty($line['work_centre_id']) ? (int) $line['work_centre_id'] : null,
                'setup_time_minutes' => round((float) ($line['setup_time_minutes'] ?? 0), 2),
                'run_time_per_unit_minutes' => round((float) $line['run_time_per_unit_minutes'], 4),
                'machine_rate_per_hour' => round((float) ($line['machine_rate_per_hour'] ?? 0), 2),
                'labour_rate_per_hour' => round((float) ($line['labour_rate_per_hour'] ?? 0), 2),
                'operators_required' => (int) ($line['operators_required'] ?? 1),
                'is_outsourced' => (bool) ($line['is_outsourced'] ?? false),
                'vendor_id' => ! empty($line['vendor_id']) ? (int) $line['vendor_id'] : null,
                'outsourced_rate' => isset($line['outsourced_rate']) && $line['outsourced_rate'] !== ''
                    ? round((float) $line['outsourced_rate'], 4)
                    : null,
                'quality_check_required' => (bool) ($line['quality_check_required'] ?? false),
            ]);
        }

        foreach ($outputs as $line) {
            if (empty($line['item_id']) || empty($line['expected_quantity'])) {
                continue;
            }

            BomOutput::query()->create([
                'bom_id' => $bom->id,
                'item_id' => (int) $line['item_id'],
                'expected_quantity' => round((float) $line['expected_quantity'], 4),
                'uom_id' => (int) $line['uom_id'],
                'cost_allocation_percent' => round((float) ($line['cost_allocation_percent'] ?? 0), 2),
                'output_type' => $line['output_type'] ?? 'by_product',
            ]);
        }
    }

    protected function assertManufacturable(Item $item): void
    {
        if (! $item->is_manufacturable) {
            throw ValidationException::withMessages([
                'item_id' => 'Finished item must be marked as manufacturable.',
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    protected function assertNoCircularReferences(int $finishedItemId, array $components): void
    {
        foreach ($components as $line) {
            $componentId = (int) ($line['component_item_id'] ?? 0);
            if ($componentId === 0) {
                continue;
            }

            if ($componentId === $finishedItemId) {
                throw ValidationException::withMessages([
                    'components' => 'A BOM cannot include its own finished item as a component.',
                ]);
            }

            $path = [(string) $finishedItemId];
            if ($this->formsCycle($finishedItemId, $componentId, $path)) {
                throw ValidationException::withMessages([
                    'components' => 'Circular BOM reference detected: '.implode(' → ', $path).' → '.$componentId,
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $path
     */
    protected function formsCycle(int $rootItemId, int $componentItemId, array &$path): bool
    {
        $path[] = (string) $componentItemId;

        if ($componentItemId === $rootItemId) {
            return true;
        }

        $childBom = Bom::query()
            ->where('item_id', $componentItemId)
            ->where('is_active', true)
            ->with('components:id,bom_id,component_item_id')
            ->first();

        if ($childBom === null) {
            return false;
        }

        foreach ($childBom->components as $child) {
            if (in_array((string) $child->component_item_id, $path, true)) {
                $path[] = (string) $child->component_item_id;

                return true;
            }

            if ($this->formsCycle($rootItemId, (int) $child->component_item_id, $path)) {
                return true;
            }
        }

        array_pop($path);

        return false;
    }

    protected function assertNoActiveOverlap(
        int $itemId,
        string $validFrom,
        ?string $validTo,
        ?int $ignoreId,
        bool $isActive
    ): void {
        if (! $isActive) {
            return;
        }

        $from = $validFrom;
        $to = $validTo ?: '9999-12-31';

        $overlap = Bom::query()
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('valid_from', '<=', $to)
            ->where(function ($q) use ($from): void {
                $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $from);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'valid_from' => 'Active validity period overlaps another active BOM version for this item.',
            ]);
        }
    }
}
