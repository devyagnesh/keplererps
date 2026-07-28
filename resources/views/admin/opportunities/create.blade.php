@extends('admin.layouts.app')
@section('title', 'Add Opportunity')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Opportunity</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.opportunities.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.opportunities._form', ['opportunity' => null, 'action' => route('admin.opportunities.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/opportunities/form.js') }}"></script>
@endpush
