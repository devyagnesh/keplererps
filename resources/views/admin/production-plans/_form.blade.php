@php
    $isDraft = ! $productionPlan || $productionPlan->status->value === 'draft';
    $lines = old('items', $productionPlan?->items?->map(fn ($l) => [
        'item_id' => $l->item_id,
        'bom_id' => $l->bom_id,
        'sales_order_id' => $l->sales_order_id,
        'sales_order_item_id' => $l->sales_order_item_id,
        'planned_quantity' => $l->planned_quantity,
        'required_date' => $l->required_date?->format('Y-m-d'),
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
        'bom_label' => $l->bom ? $l->bom->bom_number.' v'.$l->bom->version : '',
        'sales_order_no' => $l->salesOrder?->document_no,
        'work_order_no' => $l->workOrder?->document_no,
    ])->toArray() ?? []);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="productionPlanForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Plan Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($productionPlan?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Horizon From *</label><input type="date" class="form-control" id="planFromDate" name="plan_from_date" value="{{ old('plan_from_date', optional($productionPlan?->plan_from_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Horizon To *</label><input type="date" class="form-control" id="planToDate" name="plan_to_date" value="{{ old('plan_to_date', optional($productionPlan?->plan_to_date)->format('Y-m-d') ?? now()->addMonth()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $productionPlan?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-4"><label class="form-label">Component Warehouse *</label>
        <select name="source_warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('source_warehouse_id', $productionPlan?->source_warehouse_id) === (string) $warehouse->id)>
                    {{ $warehouse->code }} — {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Finished Goods Warehouse *</label>
        <select name="target_warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('target_warehouse_id', $productionPlan?->target_warehouse_id) === (string) $warehouse->id)>
                    {{ $warehouse->code }} — {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </div>
    @if ($isDraft)
    <div class="col-md-4 d-flex align-items-end">
        <button type="button" class="btn btn-primary-light" id="btnLoadDemand">Pull Open Demand</button>
    </div>
    @endif
</div>
<div class="mb-2"><h6 class="mb-0">Plan Lines</h6></div>
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead><tr><th>Item</th><th>BOM</th><th>Sales Order</th><th>Plan Qty *</th><th>Required Date</th><th>Work Order</th></tr></thead>
<tbody id="lineRows">
@forelse ($lines as $index => $line)
<tr class="line-row">
    <td>
        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
        <input type="hidden" name="items[{{ $index }}][bom_id]" value="{{ $line['bom_id'] }}">
        <input type="hidden" name="items[{{ $index }}][sales_order_id]" value="{{ $line['sales_order_id'] }}">
        <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $line['sales_order_item_id'] }}">
        <input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly>
    </td>
    <td><input type="text" class="form-control" value="{{ $line['bom_label'] ?? '' }}" readonly></td>
    <td><input type="text" class="form-control" value="{{ $line['sales_order_no'] ?? '—' }}" readonly></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][planned_quantity]" value="{{ $line['planned_quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="date" class="form-control" name="items[{{ $index }}][required_date]" value="{{ $line['required_date'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }}></td>
    <td><input type="text" class="form-control" value="{{ $line['work_order_no'] ?? '—' }}" readonly></td>
</tr>
@empty
<tr id="emptyLinesHint"><td colspan="6" class="text-muted">Pull open demand to build the plan.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.production-plans.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
