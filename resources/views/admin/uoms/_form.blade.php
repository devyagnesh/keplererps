<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label><input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $uom?->code) }}" required></div>
    <div class="col-md-5"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $uom?->name) }}" required></div>
    <div class="col-md-2">
        <label class="form-label">Type *</label>
        <select class="form-control" name="uom_type">
            @foreach(['count','weight','length','volume','area','other'] as $type)
                <option value="{{ $type }}" {{ old('uom_type', $uom?->uom_type ?? 'count') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">Decimals</label><input type="number" min="0" max="4" class="form-control" name="decimal_places" value="{{ old('decimal_places', $uom?->decimal_places ?? 3) }}"></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $uom?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.uoms.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
