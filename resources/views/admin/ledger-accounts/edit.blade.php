@extends('admin.layouts.app')
@section('title', 'Edit Ledger Account')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $account->code }} — {{ $account->name }}</h1>
        <x-admin.module-intro />
        @if ($account->is_system)
        <p class="text-muted mb-0">Control account: code and type are fixed.</p>
        @endif
    </div>
    <a href="{{ route('admin.ledger-accounts.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.ledger-accounts._form', ['action' => route('admin.ledger-accounts.update', $account), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/ledger-accounts/form.js') }}"></script>
@endpush
