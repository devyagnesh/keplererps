@extends('admin.layouts.app')
@section('title', 'UI Labels')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">UI Labels</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.ui-labels.store') }}">
    @csrf
    <div class="row g-2">
        <div class="col-md-2"><input name="locale" class="form-control" value="en" placeholder="Locale"></div>
        <div class="col-md-4"><input name="label_key" class="form-control" placeholder="work_order" required></div>
        <div class="col-md-4"><input name="label_value" class="form-control" placeholder="Job Card" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Save</button></div>
    </div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered"><thead><tr><th>Locale</th><th>Key</th><th>Value</th></tr></thead>
<tbody>
@foreach($labels as $row)
<tr><td>{{ $row->locale }}</td><td>{{ $row->label_key }}</td><td>{{ $row->label_value }}</td></tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
