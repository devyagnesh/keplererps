@extends('admin.layouts.app')

@section('title', 'Branches')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Branches</h1>
        <x-admin.module-intro />
    </div>
    <div class="ms-md-1 ms-0">
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">Add Branch</a>
    </div>
</div>
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="branchTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>State</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.branchDataUrl = @json(route('admin.branches.data'));
</script>
<script src="{{ asset('assets/admin/js/admin/branch/branch.js') }}"></script>
@endpush
