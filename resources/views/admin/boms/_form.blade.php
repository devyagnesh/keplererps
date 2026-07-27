@php
    $componentLines = old('components', $bom?->components?->map(fn ($c) => [
        'component_item_id' => $c->component_item_id,
        'quantity' => $c->quantity,
        'uom_id' => $c->uom_id,
        'wastage_percent' => $c->wastage_percent,
        'is_critical' => $c->is_critical ? 1 : 0,
        'issue_method' => $c->issue_method->value,
        'operation_sequence' => $c->operation_sequence,
    ])->toArray() ?? [[
        'component_item_id' => '', 'quantity' => '', 'uom_id' => '', 'wastage_percent' => 0,
        'is_critical' => 0, 'issue_method' => 'manual', 'operation_sequence' => '',
    ]]);
    $operationLines = old('operations', $bom?->operations?->map(fn ($o) => [
        'sequence' => $o->sequence,
        'manufacturing_operation_id' => $o->manufacturing_operation_id,
        'work_centre_id' => $o->work_centre_id,
        'setup_time_minutes' => $o->setup_time_minutes,
        'run_time_per_unit_minutes' => $o->run_time_per_unit_minutes,
        'machine_rate_per_hour' => $o->machine_rate_per_hour,
        'labour_rate_per_hour' => $o->labour_rate_per_hour,
        'operators_required' => $o->operators_required,
        'is_outsourced' => $o->is_outsourced ? 1 : 0,
        'vendor_id' => $o->vendor_id,
        'outsourced_rate' => $o->outsourced_rate,
        'quality_check_required' => $o->quality_check_required ? 1 : 0,
    ])->toArray() ?? []);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="bomForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">Finished Item *</label>
        <select name="item_id" id="finishedItem" class="form-select select2" {{ $bom ? 'disabled' : '' }} required>
            <option value="">Select</option>
            @foreach ($finishedItems as $item)
                <option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" @selected((string) old('item_id', $bom?->item_id) === (string) $item->id)>
                    {{ $item->item_code }} — {{ $item->item_name }}
                </option>
            @endforeach
        </select>
        @if ($bom)<input type="hidden" name="item_id" value="{{ $bom->item_id }}">@endif
    </div>
    <div class="col-md-2">
        <label class="form-label">Output Qty *</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="output_quantity" value="{{ old('output_quantity', $bom?->output_quantity ?? 1) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Valid From *</label>
        <input type="date" class="form-control" name="valid_from" value="{{ old('valid_from', optional($bom?->valid_from)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Valid To</label>
        <input type="date" class="form-control" name="valid_to" value="{{ old('valid_to', optional($bom?->valid_to)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Overhead %</label>
        <input type="number" step="0.01" min="0" max="100" class="form-control" name="overhead_percent" value="{{ old('overhead_percent', $bom?->overhead_percent ?? 0) }}">
    </div>
    <div class="col-md-10">
        <label class="form-label">Notes / Process Instructions</label>
        <textarea class="form-control" name="notes" rows="2" maxlength="2000">{{ old('notes', $bom?->notes) }}</textarea>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $bom?->is_active ?? true))>
            <label class="form-check-label" for="isActive">Active</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Components</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddComponent">Add component</button>
</div>
<div id="componentRows" class="mb-4">
@foreach ($componentLines as $index => $line)
<div class="row g-2 mb-2 component-row">
    <div class="col-md-3">
        <select name="components[{{ $index }}][component_item_id]" class="form-select component-item" required>
            <option value="">Component</option>
            @foreach ($componentItems as $item)
                <option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" @selected((string) ($line['component_item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" class="form-control" name="components[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" required></div>
    <div class="col-md-2">
        <select name="components[{{ $index }}][uom_id]" class="form-select component-uom" required>
            <option value="">UOM</option>
            @foreach ($uoms as $uom)
                <option value="{{ $uom->id }}" @selected((string) ($line['uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="components[{{ $index }}][wastage_percent]" value="{{ $line['wastage_percent'] ?? 0 }}" placeholder="Waste %"></div>
    <div class="col-md-2">
        <select name="components[{{ $index }}][issue_method]" class="form-select" required>
            @foreach ($issueMethods as $method)
                <option value="{{ $method->value }}" @selected(($line['issue_method'] ?? 'manual') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" min="1" class="form-control" name="components[{{ $index }}][operation_sequence]" value="{{ $line['operation_sequence'] ?? '' }}" placeholder="Op#"></div>
    <div class="col-md-1 d-flex align-items-center">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="components[{{ $index }}][is_critical]" value="1" @checked(!empty($line['is_critical']))><label class="form-check-label">Crit</label></div>
    </div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-component"><i class="ri-close-line"></i></button></div>
</div>
@endforeach
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Operations</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddOperation">Add operation</button>
</div>
<div id="operationRows" class="mb-4">
@foreach ($operationLines as $index => $line)
@include('admin.boms.partials.operation-row', ['index' => $index, 'line' => $line, 'manufacturingOperations' => $manufacturingOperations, 'workCentres' => $workCentres])
@endforeach
</div>

@if ($bom)
<div class="card border mb-4"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Material requirements</h6>
        <div class="d-flex gap-2">
            <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm" id="orderQuantity" value="1">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnExplode">Calculate</button>
        </div>
    </div>
    <div id="explodeResult" class="small text-muted">Enter order quantity and calculate required component quantities.</div>
</div></div>
<div class="alert alert-light border mb-3">
    Material {{ number_format((float) $bom->rolled_material_cost, 2) }}
    + Operations {{ number_format((float) $bom->rolled_operation_cost, 2) }}
    + Overhead {{ number_format((float) $bom->overhead_percent, 2) }}%
    = <strong>{{ number_format((float) $bom->rolled_total_cost, 2) }}</strong>
</div>
@endif

<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save BOM</button>
    <a href="{{ route('admin.boms.index') }}" class="btn btn-light">Cancel</a>
</div>
</form>
</div></div>

<template id="tplComponent">
<div class="row g-2 mb-2 component-row">
    <div class="col-md-3">
        <select name="components[__INDEX__][component_item_id]" class="form-select component-item" required>
            <option value="">Component</option>
            @foreach ($componentItems as $item)
                <option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" class="form-control" name="components[__INDEX__][quantity]" placeholder="Qty" required></div>
    <div class="col-md-2">
        <select name="components[__INDEX__][uom_id]" class="form-select component-uom" required>
            <option value="">UOM</option>
            @foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="components[__INDEX__][wastage_percent]" value="0"></div>
    <div class="col-md-2">
        <select name="components[__INDEX__][issue_method]" class="form-select" required>
            @foreach ($issueMethods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" min="1" class="form-control" name="components[__INDEX__][operation_sequence]" placeholder="Op#"></div>
    <div class="col-md-1 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="components[__INDEX__][is_critical]" value="1"><label class="form-check-label">Crit</label></div></div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-component"><i class="ri-close-line"></i></button></div>
</div>
</template>

<template id="tplOperation">
@include('admin.boms.partials.operation-row', ['index' => '__INDEX__', 'line' => [], 'manufacturingOperations' => $manufacturingOperations, 'workCentres' => $workCentres])
</template>
