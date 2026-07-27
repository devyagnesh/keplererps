<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryChallanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageLabelRequest;
use App\Http\Requests\Admin\PackageScanRequest;
use App\Models\DeliveryChallan;
use App\Models\PackageLabel;
use App\Models\Warehouse;
use App\Services\PackageLabelService;
use App\Services\PackingUnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Package label printing and gate scanning screens (M17).
 */
class PackageController extends Controller
{
    public function __construct(
        protected PackageLabelService $service,
        protected PackingUnitService $packingUnits,
        protected \App\Services\ScanExceptionService $scanExceptions
    ) {}

    public function index(): View
    {
        return view('admin.packages.index', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    /**
     * Packing workbench for a challan: lines, open quantity and printed labels.
     */
    public function pack(Request $request): View
    {
        $challanId = $request->integer('delivery_challan_id') ?: null;
        $challan = $challanId !== null
            ? DeliveryChallan::query()->with('customer:id,party_code,party_name')->findOrFail($challanId)
            : null;

        return view('admin.packages.pack', [
            'challans' => DeliveryChallan::query()
                ->whereIn('status', [DeliveryChallanStatus::Draft->value, DeliveryChallanStatus::Dispatched->value])
                ->orderByDesc('document_date')
                ->limit(100)
                ->get(['id', 'document_no', 'document_date', 'status']),
            'challan' => $challan,
            'summary' => $challan !== null ? $this->service->packingSummary($challan->id) : [],
            'packages' => $challan !== null ? $this->service->forChallan($challan->id) : collect(),
            'packingUnits' => $this->packingUnits->selectableForItem(),
        ]);
    }

    /**
     * Packed-versus-challan quantity for a challan, used by the packing screen.
     */
    public function summary(DeliveryChallan $deliveryChallan): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Packing summary loaded.',
            'data' => $this->service->packingSummary($deliveryChallan->id),
        ]);
    }

    public function store(PackageLabelRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $labels = $this->service->generate((int) $data['delivery_challan_id'], $data);

            return response()->json([
                'status' => true,
                'message' => $labels->count().' package label(s) created.',
                'data' => ['ids' => $labels->pluck('id')->all()],
                'redirect' => route('admin.packages.print', [
                    'delivery_challan_id' => $data['delivery_challan_id'],
                ]),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Printable label sheet with QR payloads.
     */
    public function print(Request $request): View
    {
        $challanId = $request->integer('delivery_challan_id') ?: null;

        $packages = $challanId !== null
            ? $this->service->forChallan($challanId)
            : PackageLabel::query()->whereKey($request->integer('id'))->with(['item', 'batch', 'packingUnit', 'deliveryChallan'])->get();

        abort_if($packages->isEmpty(), 404, 'No packages to print.');

        return view('admin.packages.print', ['packages' => $packages]);
    }

    /**
     * Scan screen for the dispatch gate.
     */
    public function scanForm(): View
    {
        return view('admin.packages.scan');
    }

    public function scan(PackageScanRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->service->scan((string) $data['code'], (bool) ($data['confirm'] ?? false));
            $package = $result['package'];

            return response()->json([
                'status' => true,
                'message' => sprintf(
                    'Package %s · %s · %s',
                    $package->label_no,
                    $package->item?->item_code ?? '',
                    $package->status->label()
                ),
                'data' => [
                    'package' => [
                        'id' => $package->id,
                        'label_no' => $package->label_no,
                        'item_code' => $package->item?->item_code,
                        'item_name' => $package->item?->item_name,
                        'batch_no' => $package->batch?->batch_no,
                        'quantity' => (float) $package->quantity,
                        'packing_unit' => $package->packingUnit?->code,
                        'warehouse' => $package->warehouse?->code,
                        'challan_no' => $package->deliveryChallan?->document_no,
                        'status' => $package->status->value,
                        'status_label' => $package->status->label(),
                    ],
                    'summary' => $result['summary'],
                ],
            ]);
        } catch (ValidationException $e) {
            $this->scanExceptions->log(
                (string) ($data['code'] ?? ''),
                'unknown',
                'package',
                $request->header('X-Device-Id'),
                ['errors' => $e->errors(), 'offline' => (bool) ($data['offline_replay'] ?? false)]
            );

            return $this->validationError($e);
        }
    }

    /**
     * Replay scans queued while the gate device was offline.
     */
    public function replayOffline(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scans' => ['required', 'array', 'min:1', 'max:100'],
            'scans.*.code' => ['required', 'string', 'max:64'],
            'scans.*.confirm' => ['sometimes', 'boolean'],
            'scans.*.scanned_at' => ['nullable', 'date'],
            'device_id' => ['nullable', 'string', 'max:80'],
        ]);

        $results = [];
        foreach ($data['scans'] as $scan) {
            try {
                $result = $this->service->scan((string) $scan['code'], (bool) ($scan['confirm'] ?? false));
                $results[] = [
                    'code' => $scan['code'],
                    'status' => 'ok',
                    'label_no' => $result['package']->label_no,
                ];
            } catch (ValidationException $e) {
                $this->scanExceptions->log(
                    (string) $scan['code'],
                    'offline_replay',
                    'package',
                    $data['device_id'] ?? $request->header('X-Device-Id'),
                    ['errors' => $e->errors(), 'scanned_at' => $scan['scanned_at'] ?? null]
                );
                $results[] = [
                    'code' => $scan['code'],
                    'status' => 'failed',
                    'message' => collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Offline scan queue processed.',
            'data' => $results,
        ]);
    }

    public function reprint(Request $request, PackageLabel $package): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $label = $this->service->reprint($package->id, $data['reason'] ?? null);

            return response()->json([
                'status' => true,
                'message' => 'Reprint recorded.',
                'data' => $label,
                'redirect' => route('admin.packages.print', ['ids' => [$label->id]]),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(PackageLabel $package): JsonResponse
    {
        try {
            $this->service->cancel($package->id);

            return response()->json(['status' => true, 'message' => 'Package cancelled.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    protected function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => collect($e->errors())->flatten()->first(),
            'errors' => $e->errors(),
        ], 422);
    }
}
