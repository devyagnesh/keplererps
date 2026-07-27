<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label><input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $transporter?->code) }}" required></div>
    <div class="col-md-5"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $transporter?->name) }}" required></div>
    <div class="col-md-4"><label class="form-label">GSTIN</label><input type="text" class="form-control text-uppercase" name="gstin" maxlength="15" value="{{ old('gstin', $transporter?->gstin) }}"></div>
    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="{{ old('phone', $transporter?->phone) }}"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ old('email', $transporter?->email) }}"></div>
    <div class="col-md-4"><label class="form-label">Vehicle Types</label><input type="text" class="form-control" name="vehicle_types" value="{{ old('vehicle_types', $transporter?->vehicle_types) }}" placeholder="Truck, Tempo"></div>
    <div class="col-md-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2">{{ old('address', $transporter?->address) }}</textarea></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $transporter?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.transporters.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
