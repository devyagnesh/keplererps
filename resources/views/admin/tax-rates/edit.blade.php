@extends('admin.layouts.app')
@section('title', 'Edit Tax Rate')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Tax Rate</h1></div>
@include('admin.tax-rates._form', ['taxRate' => $taxRate, 'action' => route('admin.tax-rates.update', $taxRate), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/tax-rate/form.js') }}"></script>
@endpush
