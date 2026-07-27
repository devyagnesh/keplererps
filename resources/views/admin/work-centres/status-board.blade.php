@extends('admin.layouts.app')
@section('title', 'Asset Status Board')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Asset Status Board</h1>
    <a href="{{ route('admin.work-centres.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
<div class="row g-3">
    @foreach ($statuses as $status)
        @if ($status->value === 'retired')
            @continue
        @endif
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <div class="card-title mb-0">{{ $status->label() }}</div>
                </div>
                <div class="card-body">
                    @php $group = $assets->filter(fn ($asset) => $asset->status === $status); @endphp
                    @forelse ($group as $asset)
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="fw-semibold">{{ $asset->code }} — {{ $asset->name }}</div>
                            <div class="fs-12 text-muted">{{ $asset->asset_type->label() }} · Open WOs: {{ $asset->open_work_orders_count }}</div>
                        </div>
                    @empty
                        <div class="text-muted fs-12">None</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
