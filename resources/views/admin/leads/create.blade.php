@extends('admin.layouts.app')
@section('title', 'Add Lead')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Add Lead</h1>
    <a href="{{ route('admin.leads.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.leads._form', ['lead' => null, 'action' => route('admin.leads.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/leads/form.js') }}"></script>
@endpush
