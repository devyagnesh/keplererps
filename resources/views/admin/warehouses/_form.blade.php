<div class="card custom-card">
    <div class="card-body">
        <form id="warehouseForm" action="{{ $action }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="_method" value="{{ $method }}">
            <div class="row gy-3">
                <div class="col-xl-4">
                    <label class="form-label" for="branch_id">Branch <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="branch_id" name="branch_id">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $warehouse?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="level">Level <span class="text-danger">*</span></label>
                    <select class="form-control" id="level" name="level">
                        @foreach($levels as $level)
                            <option value="{{ $level->value }}" {{ old('level', $warehouse?->level?->value) === $level->value ? 'selected' : '' }}>{{ ucfirst($level->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="parent_id">Parent</label>
                    <select class="form-control select2" id="parent_id" name="parent_id">
                        <option value="">None (Plant)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}"
                                data-branch="{{ $parent->branch_id }}"
                                data-depth="{{ $parent->depth }}"
                                {{ (string) old('parent_id', $warehouse?->parent_id) === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }} ({{ ucfirst($parent->level->value) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="code" name="code" value="{{ old('code', $warehouse?->code) }}">
                </div>
                <div class="col-xl-6">
                    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $warehouse?->name) }}">
                </div>
                <div class="col-xl-2">
                    <div class="form-check form-switch mt-4">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $warehouse?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-xl-12">
                    <button type="submit" class="btn btn-primary">Save Warehouse</button>
                    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
