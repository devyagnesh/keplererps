@extends('admin.layouts.app')
@section('title', 'Add Tax Rate')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Tax Rate</h1></div>
@include('admin.tax-rates._form', ['taxRate' => null, 'action' => route('admin.tax-rates.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/tax-rate/form.js') }}"></script>
@endpush
