@extends('admin.layouts.app')
@section('title', 'Add Stock Transfer')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Stock Transfer</h1></div>
@include('admin.stock-transfers._form', ['stockTransfer' => null, 'action' => route('admin.stock-transfers.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/stock-transfer/form.js') }}"></script>
@endpush
