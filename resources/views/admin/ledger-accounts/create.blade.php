@extends('admin.layouts.app')
@section('title', 'Add Ledger Account')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Ledger Account</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.ledger-accounts.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.ledger-accounts._form', ['account' => null, 'action' => route('admin.ledger-accounts.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/ledger-accounts/form.js') }}"></script>
@endpush
