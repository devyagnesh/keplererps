@extends('admin.layouts.app')
@section('title', 'Edit Item')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Item</h1></div>
@include('admin.items._form', ['item' => $item, 'action' => route('admin.items.update', $item), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/item/form.js') }}"></script>
@endpush
