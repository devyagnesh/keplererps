@extends('admin.layouts.app')

@section('title', 'Edit Party')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Edit Party — {{ $party->party_code }}</h1>
        <x-admin.module-intro />
    </div>
</div>
@include('admin.parties._form', ['party' => $party, 'action' => route('admin.parties.update', $party), 'method' => 'PUT'])
@endsection

@push('scripts')
<script>
    window.gstinLookupUrl = @json(route('admin.parties.gstin-lookup'));
</script>
<script src="{{ asset('assets/admin/js/admin/party/party-form.js') }}"></script>
@endpush
