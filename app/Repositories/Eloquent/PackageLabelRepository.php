<?php

namespace App\Repositories\Eloquent;

use App\Enums\PackageStatus;
use App\Models\PackageLabel;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\PackageLabelRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent package label repository.
 */
class PackageLabelRepository implements PackageLabelRepositoryInterface
{
    use BuildsServerSideDataTable;

    /**
     * Relations needed whenever a package is shown or scanned.
     *
     * @var list<string>
     */
    protected const EAGER = [
        'packingUnit:id,code,name,parent_id,quantity,uom_id',
        'packingUnit.uom:id,code',
        'item:id,item_code,item_name',
        'batch:id,batch_no,expiry_date',
        'warehouse:id,code,name',
        'deliveryChallan:id,document_no,status,customer_id',
    ];

    public function findById(int $id): PackageLabel
    {
        return PackageLabel::query()->with(self::EAGER)->findOrFail($id);
    }

    public function findByLabelNo(string $labelNo): ?PackageLabel
    {
        return PackageLabel::query()->with(self::EAGER)->where('label_no', $labelNo)->first();
    }

    public function create(array $data): PackageLabel
    {
        return PackageLabel::query()->create($data);
    }

    public function delete(int $id): bool
    {
        return (bool) PackageLabel::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = PackageLabel::query()->with(self::EAGER);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['delivery_challan_id'])) {
            $query->where('delivery_challan_id', (int) $params['delivery_challan_id']);
        }
        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', (int) $params['warehouse_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'label_no', 'quantity', 'status', 'packed_at', 'created_at'],
            ['label_no', 'qr_payload'],
            function (PackageLabel $package): array {
                return [
                    'id' => $package->id,
                    'label_no' => $package->label_no,
                    'item' => e(($package->item?->item_code ?? '').' — '.($package->item?->item_name ?? '')),
                    'packing_unit' => e($package->packingUnit?->code ?? '—'),
                    'batch' => e($package->batch?->batch_no ?? '—'),
                    'quantity' => number_format((float) $package->quantity, 4, '.', ''),
                    'challan' => e($package->deliveryChallan?->document_no ?? '—'),
                    'packed_at' => $package->packed_at?->format('Y-m-d H:i') ?? '—',
                    'status' => '<span class="badge '.$package->status->badgeClass().'">'.$package->status->label().'</span>',
                    'action' => view('admin.packages.partials.actions', ['package' => $package])->render(),
                ];
            },
            $params
        );
    }

    public function forChallan(int $deliveryChallanId): Collection
    {
        return PackageLabel::query()
            ->with(self::EAGER)
            ->where('delivery_challan_id', $deliveryChallanId)
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->orderBy('label_no')
            ->get();
    }
}
