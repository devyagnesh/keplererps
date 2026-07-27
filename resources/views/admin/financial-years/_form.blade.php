<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" value="{{ old('code', $financialYear?->code) }}" placeholder="2026-27" required></div>
    <div class="col-md-5"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $financialYear?->name) }}" required></div>
    <div class="col-md-2"><label class="form-label">Starts *</label><input type="date" class="form-control" name="starts_on" value="{{ old('starts_on', optional($financialYear?->starts_on)->format('Y-m-d')) }}" required></div>
    <div class="col-md-2"><label class="form-label">Ends *</label><input type="date" class="form-control" name="ends_on" value="{{ old('ends_on', optional($financialYear?->ends_on)->format('Y-m-d')) }}" required></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_current" value="0"><input class="form-check-input" type="checkbox" name="is_current" value="1" {{ old('is_current', $financialYear?->is_current) ? 'checked' : '' }}><label class="form-check-label">Set as current</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.financial-years.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
