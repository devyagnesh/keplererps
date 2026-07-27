<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label><input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $category?->code) }}" required></div>
    <div class="col-md-5"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $category?->name) }}" required></div>
    <div class="col-md-2">
        <label class="form-label">Type *</label>
        <select class="form-control" name="category_type">
            @foreach(['item','party','other'] as $type)
                <option value="{{ $type }}" {{ old('category_type', $category?->category_type ?? 'item') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Parent</label>
        <select class="form-control select2" name="parent_id">
            <option value="">None</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ (string) old('parent_id', $category?->parent_id) === (string) $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $category?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
