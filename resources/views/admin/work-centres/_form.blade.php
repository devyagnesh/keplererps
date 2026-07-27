<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3">
        <label class="form-label">Code *</label>
        <input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $asset?->code) }}" {{ $asset ? 'readonly' : 'required' }}>
    </div>
    <div class="col-md-5">
        <label class="form-label">Name *</label>
        <input type="text" class="form-control" name="name" value="{{ old('name', $asset?->name) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Type *</label>
        <select name="asset_type" class="form-select" required>
            @foreach ($assetTypes as $type)
                <option value="{{ $type->value }}" @selected(old('asset_type', $asset?->asset_type?->value ?? 'machine') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $asset?->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Make / Model</label><input type="text" class="form-control" name="make_model" value="{{ old('make_model', $asset?->make_model) }}"></div>
    <div class="col-md-4"><label class="form-label">Serial No</label><input type="text" class="form-control" name="serial_no" value="{{ old('serial_no', $asset?->serial_no) }}"></div>
    <div class="col-md-2"><label class="form-label">Purchase date</label><input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', $asset?->purchase_date?->format('Y-m-d')) }}"></div>
    <div class="col-md-2"><label class="form-label">Purchase value</label><input type="number" step="0.01" min="0" class="form-control" name="purchase_value" value="{{ old('purchase_value', $asset?->purchase_value) }}"></div>
    <div class="col-md-3"><label class="form-label">Location</label><input type="text" class="form-control" name="location" value="{{ old('location', $asset?->location) }}"></div>
    <div class="col-md-3"><label class="form-label">Department</label><input type="text" class="form-control" name="department" value="{{ old('department', $asset?->department) }}"></div>
    <div class="col-md-3"><label class="form-label">Capacity</label><input type="text" class="form-control" name="capacity" value="{{ old('capacity', $asset?->capacity) }}"></div>
    <div class="col-md-3"><label class="form-label">Cavity count</label><input type="number" min="1" max="128" class="form-control" name="cavity_count" value="{{ old('cavity_count', $asset?->cavity_count) }}"></div>
    <div class="col-md-3"><label class="form-label">Machine rate / hr</label><input type="number" step="0.01" min="0" class="form-control" name="machine_rate_per_hour" value="{{ old('machine_rate_per_hour', $asset?->machine_rate_per_hour ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Labour rate / hr</label><input type="number" step="0.01" min="0" class="form-control" name="labour_rate_per_hour" value="{{ old('labour_rate_per_hour', $asset?->labour_rate_per_hour ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Cycle time (sec)</label><input type="number" step="0.01" min="0" class="form-control" name="cycle_time_seconds" value="{{ old('cycle_time_seconds', $asset?->cycle_time_seconds) }}"></div>
    <div class="col-md-3"><label class="form-label">Life cycles</label><input type="number" min="1" class="form-control" name="life_cycles" value="{{ old('life_cycles', $asset?->life_cycles) }}"></div>
    <div class="col-md-3"><label class="form-label">Service interval (days)</label><input type="number" min="1" class="form-control" name="service_interval_days" value="{{ old('service_interval_days', $asset?->service_interval_days) }}"></div>
    <div class="col-md-3"><label class="form-label">Service interval (hours)</label><input type="number" step="0.01" min="0" class="form-control" name="service_interval_hours" value="{{ old('service_interval_hours', $asset?->service_interval_hours) }}"></div>
    <div class="col-md-3"><label class="form-label">Service interval (cycles)</label><input type="number" min="1" class="form-control" name="service_interval_cycles" value="{{ old('service_interval_cycles', $asset?->service_interval_cycles) }}"></div>
    <div class="col-md-3"><label class="form-label">Next service due</label><input type="date" class="form-control" name="next_service_due_on" value="{{ old('next_service_due_on', $asset?->next_service_due_on?->format('Y-m-d')) }}"></div>
    <div class="col-md-3">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $asset?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
    @if ($asset)
    <div class="col-md-3"><label class="form-label text-muted">Cycles used</label><div class="form-control-plaintext">{{ number_format((int) $asset->cycles_used) }}</div></div>
    <div class="col-md-3"><label class="form-label text-muted">Running hours</label><div class="form-control-plaintext">{{ number_format((float) $asset->running_hours, 2) }}</div></div>
    @endif
    <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes', $asset?->notes) }}</textarea></div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('admin.work-centres.index') }}" class="btn btn-light">Cancel</a>
    </div>
</div>
</form>
</div></div>
