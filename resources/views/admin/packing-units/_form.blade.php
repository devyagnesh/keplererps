<div class="card custom-card"><div class="card-body">
<form id="packingUnitForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label>
        <input type="text" class="form-control text-uppercase" name="code" maxlength="30" value="{{ old('code', $unit?->code) }}" required>
    </div>
    <div class="col-md-5"><label class="form-label">Name *</label>
        <input type="text" class="form-control" name="name" value="{{ old('name', $unit?->name) }}" required>
    </div>
    <div class="col-md-4"><label class="form-label">Item</label>
        <select class="form-select" name="item_id">
            <option value="">Generic (any item)</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) old('item_id', $unit?->item_id) === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Nests Inside</label>
        <select class="form-select" name="parent_id">
            <option value="">None (base unit)</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $unit?->parent_id) === (string) $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Leave empty when the quantity is in the base UOM.</small>
    </div>
    <div class="col-md-4"><label class="form-label">Quantity per Unit *</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="quantity" value="{{ old('quantity', $unit ? (float) $unit->quantity : 1) }}" required>
    </div>
    <div class="col-md-4"><label class="form-label">UOM *</label>
        <select class="form-select" name="uom_id" required>
            <option value="">Select UOM</option>
            @foreach ($uoms as $uom)
                <option value="{{ $uom->id }}" @selected((string) old('uom_id', $unit?->uom_id) === (string) $uom->id)>{{ $uom->code }} — {{ $uom->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8"><label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks" value="{{ old('remarks', $unit?->remarks) }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $unit?->is_active ?? true))>
            <label class="form-check-label" for="isActive">Active</label>
        </div>
    </div>
</div>
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Packing Unit</button>
    <a href="{{ route('admin.packing-units.index') }}" class="btn btn-light">Cancel</a>
</div>
</form>
</div></div>
