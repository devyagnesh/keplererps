@php
    use App\Enums\BomIssueMethod;
    use App\Enums\WorkOrderStatus;

    $isDraft = ! $workOrder || $workOrder->status->isEditable();
    $isClosed = $workOrder && $workOrder->status === WorkOrderStatus::Closed;
    $canIssueMaterials = $workOrder && in_array($workOrder->status, [WorkOrderStatus::Released, WorkOrderStatus::InProgress], true);
    $canClose = $workOrder && $workOrder->status->canClose();
    $canRelease = $workOrder && $workOrder->status->canRelease();
    $canRecordProduction = $workOrder && $workOrder->status->canReceiveProduction();
    $manualComponents = $workOrder
        ? $workOrder->components->filter(fn ($component) => $component->issue_method === BomIssueMethod::Manual && $component->pendingIssueQty() > 0)
        : collect();
@endphp
<div class="card custom-card"><div class="card-body">
<form id="workOrderForm"
    action="{{ $action }}"
    method="POST"
    novalidate
    data-boms-url="{{ url('/admin/work-orders/boms') }}"
    @if ($workOrder)
    data-selected-bom-id="{{ old('bom_id', $workOrder->bom_id) }}"
    @endif
>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($workOrder?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" @readonly(!$isDraft) required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Item *</label>
        <select id="itemId" name="item_id" class="form-select select2" @disabled($workOrder !== null) required>
            <option value="">Select item</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) old('item_id', $workOrder?->item_id) === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
        @if ($workOrder)
            <input type="hidden" name="item_id" value="{{ $workOrder->item_id }}">
        @endif
    </div>
    <div class="col-md-5">
        <label class="form-label">BOM *</label>
        <select id="bomId" name="bom_id" class="form-select select2" @disabled($workOrder !== null) required>
            <option value="">Select BOM</option>
            @if ($workOrder?->bom)
                <option value="{{ $workOrder->bom_id }}" selected>{{ $workOrder->bom->bom_number }} v{{ $workOrder->bom->version }}</option>
            @endif
        </select>
        @if ($workOrder)
            <input type="hidden" name="bom_id" value="{{ $workOrder->bom_id }}">
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label">Planned Quantity *</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="planned_quantity" value="{{ old('planned_quantity', $workOrder?->planned_quantity) }}" @readonly(!$isDraft) required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Planned Start *</label>
        <input type="date" class="form-control" name="planned_start_date" value="{{ old('planned_start_date', optional($workOrder?->planned_start_date)->format('Y-m-d') ?? now()->toDateString()) }}" @readonly(!$isDraft) required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Planned End *</label>
        <input type="date" class="form-control" name="planned_end_date" value="{{ old('planned_end_date', optional($workOrder?->planned_end_date)->format('Y-m-d') ?? now()->addDays(7)->toDateString()) }}" @readonly(!$isDraft) required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select" @disabled(!$isDraft)>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->value }}" @selected((string) old('priority', $workOrder?->priority?->value ?? 'normal') === (string) $priority->value)>{{ $priority->label() }}</option>
            @endforeach
        </select>
        @unless ($isDraft)
            <input type="hidden" name="priority" value="{{ $workOrder->priority->value }}">
        @endunless
    </div>
    <div class="col-md-4">
        <label class="form-label">Source Warehouse *</label>
        <select name="source_warehouse_id" class="form-select select2" @disabled(!$isDraft) required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('source_warehouse_id', $workOrder?->source_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)
            <input type="hidden" name="source_warehouse_id" value="{{ $workOrder->source_warehouse_id }}">
        @endunless
    </div>
    <div class="col-md-4">
        <label class="form-label">Target Warehouse *</label>
        <select name="target_warehouse_id" class="form-select select2" @disabled(!$isDraft) required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('target_warehouse_id', $workOrder?->target_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)
            <input type="hidden" name="target_warehouse_id" value="{{ $workOrder->target_warehouse_id }}">
        @endunless
    </div>
    <div class="col-md-4">
        <label class="form-label">Work Centre</label>
        <select name="work_centre_id" class="form-select select2" @disabled(!$isDraft)>
            <option value="">Select</option>
            @foreach ($workCentres as $workCentre)
                <option value="{{ $workCentre->id }}" @selected((string) old('work_centre_id', $workOrder?->work_centre_id) === (string) $workCentre->id)>{{ $workCentre->code }} — {{ $workCentre->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)
            @if ($workOrder->work_centre_id)
                <input type="hidden" name="work_centre_id" value="{{ $workOrder->work_centre_id }}">
            @endif
        @endunless
    </div>
    <div class="col-md-4">
        <label class="form-label">Sales Order</label>
        <select name="sales_order_id" class="form-select select2" @disabled(!$isDraft)>
            <option value="">None</option>
            @foreach ($salesOrders as $salesOrder)
                <option value="{{ $salesOrder->id }}" @selected((string) old('sales_order_id', $workOrder?->sales_order_id) === (string) $salesOrder->id)>{{ $salesOrder->document_no }}</option>
            @endforeach
        </select>
        @unless ($isDraft)
            @if ($workOrder->sales_order_id)
                <input type="hidden" name="sales_order_id" value="{{ $workOrder->sales_order_id }}">
            @endif
        @endunless
    </div>
    <div class="col-md-8">
        <label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks" value="{{ old('remarks', $workOrder?->remarks) }}" @readonly(!$isDraft)>
    </div>
</div>
@if ($isDraft)
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Draft</button>
    <a href="{{ route('admin.work-orders.index') }}" class="btn btn-light">Cancel</a>
</div>
@else
<div class="mt-3">
    <a href="{{ route('admin.work-orders.index') }}" class="btn btn-light">Back</a>
</div>
@endif
</form>
</div></div>

@if ($workOrder && ! empty($availability))
<div class="card custom-card mt-3"><div class="card-header"><div class="card-title">Material Availability</div></div><div class="card-body">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Required</th>
                    <th>Available</th>
                    <th>Shortage</th>
                    <th>Critical</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($availability as $row)
                <tr @class(['table-danger' => $row['shortage_quantity'] > 0 && $row['is_critical'], 'table-warning' => $row['shortage_quantity'] > 0 && ! $row['is_critical']])>
                    <td>{{ $row['item_code'] }} — {{ $row['item_name'] }}</td>
                    <td>{{ number_format((float) $row['required_quantity'], 4) }}</td>
                    <td>{{ number_format((float) $row['available_quantity'], 4) }}</td>
                    <td>{{ number_format((float) $row['shortage_quantity'], 4) }}</td>
                    <td>{{ $row['is_critical'] ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div></div>
@endif

@if ($canIssueMaterials && $manualComponents->isNotEmpty())
<div class="card custom-card mt-3"><div class="card-header"><div class="card-title">Issue Materials (Manual)</div></div><div class="card-body">
<form id="issueMaterialsForm" novalidate data-issue-url="{{ route('admin.work-orders.issue-materials', $workOrder) }}">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-3">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Required</th>
                    <th>Issued</th>
                    <th>Pending</th>
                    <th>Issue Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($manualComponents as $index => $component)
                <tr>
                    <td>
                        {{ $component->item?->item_code }} — {{ $component->item?->item_name }}
                        <input type="hidden" name="items[{{ $index }}][work_order_component_id]" value="{{ $component->id }}">
                    </td>
                    <td>{{ number_format((float) $component->required_quantity, 4) }}</td>
                    <td>{{ number_format((float) $component->issued_quantity, 4) }}</td>
                    <td>{{ number_format($component->pendingIssueQty(), 4) }}</td>
                    <td>
                        <input type="number" step="0.0001" min="0.0001" max="{{ $component->pendingIssueQty() }}" class="form-control form-control-sm issue-qty" name="items[{{ $index }}][quantity]" placeholder="Qty">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @can('work_order.update')
    <button type="submit" class="btn btn-primary btn-sm">Issue Selected</button>
    @endcan
</form>
</div></div>
@endif

@if ($isClosed)
<div class="card custom-card mt-3"><div class="card-header"><div class="card-title">Cost Summary</div></div><div class="card-body">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Standard Unit Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->standard_unit_cost, 4) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Material Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_material_cost, 2) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Machine Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_machine_cost, 2) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Labour Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_labour_cost, 2) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Overhead Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_overhead_cost, 2) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Total Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_total_cost, 2) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Actual Unit Cost</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->actual_unit_cost, 4) }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted mb-1">Cost Variance</label>
            <div class="fw-semibold">{{ number_format((float) $workOrder->cost_variance, 2) }}</div>
        </div>
    </div>
</div></div>
@endif
