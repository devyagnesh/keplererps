@extends('admin.layouts.app')
@section('title', 'Edit Transporter')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Transporter</h1></div>
@include('admin.transporters._form', ['transporter' => $transporter, 'action' => route('admin.transporters.update', $transporter), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/transporter/form.js') }}"></script>
@endpush
