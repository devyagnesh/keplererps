<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-4"><label class="form-label">Code *</label><input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $taxRate?->code) }}" required></div>
    <div class="col-md-8"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $taxRate?->name) }}" required></div>
    <div class="col-md-3"><label class="form-label">CGST %</label><input type="number" step="0.01" min="0" class="form-control" name="cgst_rate" value="{{ old('cgst_rate', $taxRate?->cgst_rate ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">SGST %</label><input type="number" step="0.01" min="0" class="form-control" name="sgst_rate" value="{{ old('sgst_rate', $taxRate?->sgst_rate ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">IGST %</label><input type="number" step="0.01" min="0" class="form-control" name="igst_rate" value="{{ old('igst_rate', $taxRate?->igst_rate ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Cess %</label><input type="number" step="0.01" min="0" class="form-control" name="cess_rate" value="{{ old('cess_rate', $taxRate?->cess_rate ?? 0) }}"></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $taxRate?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.tax-rates.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
