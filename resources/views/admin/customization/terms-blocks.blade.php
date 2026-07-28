@extends('admin.layouts.app')
@section('title', 'Terms Library')
@section('content')
<div class="my-4 page-header-breadcrumb"><div><h1 class="page-title fw-semibold fs-18 mb-0">Terms &amp; Conditions</h1><x-admin.module-intro /></div></div>
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.terms-blocks.store') }}">
    @csrf
    <div class="row g-2">
        <div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
        <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-md-3"><input name="document_type" class="form-control" placeholder="Optional document type"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
        <div class="col-12"><textarea name="body" class="form-control" rows="3" placeholder="Terms body" required></textarea></div>
    </div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered"><thead><tr><th>Code</th><th>Name</th><th>Document</th></tr></thead>
<tbody>
@foreach($blocks as $row)
<tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->document_type ?: 'Any' }}</td></tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
