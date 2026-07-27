@extends('admin.layouts.app')
@section('title', 'Add Stock Adjustment')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Stock Adjustment</h1></div>
@include('admin.stock-adjustments._form', ['stockAdjustment' => null, 'action' => route('admin.stock-adjustments.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/stock-adjustment/form.js') }}"></script>
@endpush
