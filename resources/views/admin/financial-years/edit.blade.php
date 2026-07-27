@extends('admin.layouts.app')
@section('title', 'Edit Financial Year')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Financial Year</h1></div>
@include('admin.financial-years._form', ['financialYear' => $financialYear, 'action' => route('admin.financial-years.update', $financialYear), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/financial-year/form.js') }}"></script>
@endpush
