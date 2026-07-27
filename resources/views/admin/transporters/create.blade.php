@extends('admin.layouts.app')
@section('title', 'Add Transporter')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Transporter</h1></div>
@include('admin.transporters._form', ['transporter' => null, 'action' => route('admin.transporters.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/transporter/form.js') }}"></script>
@endpush
