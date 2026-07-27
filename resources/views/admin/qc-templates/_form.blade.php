@php
    $parameters = old('parameters', $template?->parameters?->map(fn ($p) => [
        'name' => $p->name,
        'parameter_type' => $p->parameter_type->value,
        'uom' => $p->uom,
        'min_value' => $p->min_value,
        'max_value' => $p->max_value,
        'target_value' => $p->target_value,
        'is_critical' => $p->is_critical,
        'test_method' => $p->test_method,
    ])->all() ?? [['name' => '', 'parameter_type' => 'numeric', 'is_critical' => false]]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3">
        <label class="form-label">Code *</label>
        <input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $template?->code) }}" required>
    </div>
    <div class="col-md-5">
        <label class="form-label">Name *</label>
        <input type="text" class="form-control" name="name" value="{{ old('name', $template?->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Inspection type *</label>
        <select name="inspection_type" class="form-select" required>
            @foreach ($inspectionTypes as $type)
                <option value="{{ $type->value }}" @selected(old('inspection_type', $template?->inspection_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Item (optional)</label>
        <select name="item_id" class="form-select">
            <option value="">— Any item —</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) old('item_id', $template?->item_id) === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Category (optional)</label>
        <select name="category_id" class="form-select">
            <option value="">— Any category —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $template?->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Sampling plan *</label>
        <select name="sampling_plan" class="form-select" id="samplingPlan" required>
            @foreach ($samplingPlans as $plan)
                <option value="{{ $plan->value }}" @selected(old('sampling_plan', $template?->sampling_plan?->value) === $plan->value)>{{ $plan->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Sampling value</label>
        <input type="number" step="0.0001" min="0" class="form-control" name="sampling_value" id="samplingValue" value="{{ old('sampling_value', $template?->sampling_value) }}">
    </div>
    <div class="col-md-3">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $template?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control" name="notes" rows="2">{{ old('notes', $template?->notes) }}</textarea>
    </div>
</div>

<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Parameters *</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddParameter">Add parameter</button>
</div>
<div id="parameterRows">
    @foreach ($parameters as $index => $row)
        <div class="row g-2 mb-2 parameter-row">
            <div class="col-md-3">
                <input type="text" class="form-control" name="parameters[{{ $index }}][name]" placeholder="Parameter name" value="{{ $row['name'] ?? '' }}" required>
            </div>
            <div class="col-md-2">
                <select name="parameters[{{ $index }}][parameter_type]" class="form-select parameter-type">
                    @foreach ($parameterTypes as $ptype)
                        <option value="{{ $ptype->value }}" @selected(($row['parameter_type'] ?? '') === $ptype->value)>{{ $ptype->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input type="text" class="form-control" name="parameters[{{ $index }}][uom]" placeholder="UOM" value="{{ $row['uom'] ?? '' }}"></div>
            <div class="col-md-1"><input type="number" step="any" class="form-control" name="parameters[{{ $index }}][min_value]" placeholder="Min" value="{{ $row['min_value'] ?? '' }}"></div>
            <div class="col-md-1"><input type="number" step="any" class="form-control" name="parameters[{{ $index }}][max_value]" placeholder="Max" value="{{ $row['max_value'] ?? '' }}"></div>
            <div class="col-md-1"><input type="number" step="any" class="form-control" name="parameters[{{ $index }}][target_value]" placeholder="Target" value="{{ $row['target_value'] ?? '' }}"></div>
            <div class="col-md-2"><input type="text" class="form-control" name="parameters[{{ $index }}][test_method]" placeholder="Test method" value="{{ $row['test_method'] ?? '' }}"></div>
            <div class="col-md-1 d-flex align-items-center gap-2">
                <div class="form-check">
                    <input type="hidden" name="parameters[{{ $index }}][is_critical]" value="0">
                    <input class="form-check-input" type="checkbox" name="parameters[{{ $index }}][is_critical]" value="1" {{ !empty($row['is_critical']) ? 'checked' : '' }}>
                    <label class="form-check-label">Critical</label>
                </div>
                <button type="button" class="btn btn-sm btn-danger-light btn-remove-parameter">×</button>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-4">
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('admin.qc-templates.index') }}" class="btn btn-light">Cancel</a>
</div>
</form>
</div></div>
