@extends('admin.layouts.app')
@section('title', 'Add Category')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Category</h1></div>
@include('admin.categories._form', ['category' => null, 'action' => route('admin.categories.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/category/form.js') }}"></script>
@endpush
