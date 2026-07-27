<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3">
        <label class="form-label">Code *</label>
        <input type="text" class="form-control" name="code" value="{{ old('code', $hsnCode?->code) }}" maxlength="8" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Type *</label>
        <select name="code_type" class="form-select" required>
            <option value="hsn" @selected(old('code_type', $hsnCode?->code_type ?? 'hsn') === 'hsn')>HSN</option>
            <option value="sac" @selected(old('code_type', $hsnCode?->code_type) === 'sac')>SAC</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Default GST %</label>
        <input type="number" step="0.01" min="0" class="form-control" name="default_gst_rate" value="{{ old('default_gst_rate', $hsnCode?->default_gst_rate ?? 18) }}">
    </div>
    <div class="col-md-3">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $hsnCode?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
    <div class="col-md-12">
        <label class="form-label">Description *</label>
        <input type="text" class="form-control" name="description" value="{{ old('description', $hsnCode?->description) }}" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('admin.hsn-codes.index') }}" class="btn btn-light">Cancel</a>
    </div>
</div>
</form>
</div></div>
