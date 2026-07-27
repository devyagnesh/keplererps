<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-4"><label class="form-label">Document type *</label>
        <select name="document_type" class="form-select" required>
            @foreach ($documentTypes as $type)
                <option value="{{ $type->value }}" @selected(old('document_type', $series?->document_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Financial year</label>
        <select name="financial_year_id" class="form-select">
            <option value="">All years</option>
            @foreach ($financialYears as $fy)
                <option value="{{ $fy->id }}" @selected((string) old('financial_year_id', $series?->financial_year_id) === (string) $fy->id)>{{ $fy->code }} — {{ $fy->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Branch</label>
        <select name="branch_id" class="form-select">
            <option value="">All branches</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $series?->branch_id) === (string) $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">Prefix *</label><input type="text" class="form-control text-uppercase" name="prefix" value="{{ old('prefix', $series?->prefix) }}" required></div>
    <div class="col-md-2"><label class="form-label">Suffix</label><input type="text" class="form-control" name="suffix" value="{{ old('suffix', $series?->suffix) }}"></div>
    <div class="col-md-2"><label class="form-label">Separator</label><input type="text" class="form-control" name="separator" value="{{ old('separator', $series?->separator ?? '-') }}" maxlength="5" required></div>
    <div class="col-md-2"><label class="form-label">Padding</label><input type="number" min="1" max="10" class="form-control" name="padding" value="{{ old('padding', $series?->padding ?? 5) }}" required></div>
    <div class="col-md-2"><label class="form-label">Start no.</label><input type="number" min="1" class="form-control" name="start_number" value="{{ old('start_number', $series?->start_number ?? 1) }}" required></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="include_fy_code" value="0"><input class="form-check-input" type="checkbox" name="include_fy_code" value="1" {{ old('include_fy_code', $series?->include_fy_code) ? 'checked' : '' }}><label class="form-check-label">Include FY code</label></div></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="reset_yearly" value="0"><input class="form-check-input" type="checkbox" name="reset_yearly" value="1" {{ old('reset_yearly', $series?->reset_yearly ?? true) ? 'checked' : '' }}><label class="form-check-label">Reset yearly</label></div></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $series?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.document-series.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
