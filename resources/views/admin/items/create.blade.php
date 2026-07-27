@extends('admin.layouts.app')
@section('title', 'Add Item')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Item</h1></div>
@include('admin.items._form', ['item' => null, 'action' => route('admin.items.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/item/form.js') }}"></script>
@endpush
