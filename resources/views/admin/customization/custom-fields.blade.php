@extends('admin.layouts.app')
@section('title', 'Custom Fields')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Custom Fields</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form id="customFieldForm" data-ajax="1" data-reload="1" method="post" action="{{ route('admin.custom-fields.store') }}">
    @csrf
    <div class="row g-2">
        <div class="col-md-2"><input name="entity_type" class="form-control" placeholder="entity e.g. party" required></div>
        <div class="col-md-2"><input name="field_key" class="form-control" placeholder="field_key" required></div>
        <div class="col-md-3"><input name="label" class="form-control" placeholder="Label" required></div>
        <div class="col-md-2"><select name="field_type" class="form-select"><option value="text">text</option><option value="number">number</option><option value="date">date</option><option value="select">select</option><option value="boolean">boolean</option></select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered"><thead><tr><th>Entity</th><th>Key</th><th>Label</th><th>Type</th><th>Required</th></tr></thead>
<tbody>
@foreach($definitions as $def)
<tr><td>{{ $def->entity_type }}</td><td>{{ $def->field_key }}</td><td>{{ $def->label }}</td><td>{{ $def->field_type }}</td><td>{{ $def->is_required ? 'Yes' : 'No' }}</td></tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
