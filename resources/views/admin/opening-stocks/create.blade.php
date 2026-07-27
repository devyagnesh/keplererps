@extends('admin.layouts.app')
@section('title', 'Add Opening Stock')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Opening Stock</h1></div>
@include('admin.opening-stocks._form', ['openingStock' => null, 'action' => route('admin.opening-stocks.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/opening-stock/form.js') }}"></script>
@endpush
