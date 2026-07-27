@extends('admin.layouts.app')
@section('title', 'Edit Category')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Category</h1></div>
@include('admin.categories._form', ['category' => $category, 'action' => route('admin.categories.update', $category), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/category/form.js') }}"></script>
@endpush
