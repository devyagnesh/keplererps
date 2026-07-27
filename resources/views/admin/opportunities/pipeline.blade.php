@extends('admin.layouts.app')
@section('title', 'Pipeline Board')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Pipeline Board</h1>
        <p class="text-muted mb-0">Open pipeline {{ number_format($pipeline['total_value'], 2) }} · weighted {{ number_format($pipeline['total_weighted'], 2) }}</p>
    </div>
    <a href="{{ route('admin.opportunities.index') }}" class="btn btn-light btn-sm">List View</a>
</div>

<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.opportunities.pipeline') }}" class="row g-2">
    <div class="col-md-3">
        <select name="assigned_user_id" class="form-select">
            <option value="">All owners</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((int) $selectedUserId === (int) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Apply</button></div>
</form>
</div></div>

<div class="row gy-3">
@foreach ($pipeline['stages'] as $stageValue => $stage)
    <div class="col-12 col-md-6 col-xl">
        <div class="card custom-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">{{ $stage['label'] }}</div>
                <span class="badge bg-light text-default">{{ $stage['count'] }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted fs-12 mb-3">
                    Value {{ number_format($stage['value'], 2) }}<br>
                    Weighted {{ number_format($stage['weighted'], 2) }}
                </p>
                @forelse ($stage['opportunities'] as $opportunity)
                    <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="d-block border-bottom pb-2 mb-2 text-default text-decoration-none">
                        <span class="d-block fw-semibold">{{ $opportunity->title }}</span>
                        <span class="d-block fs-12 text-muted">
                            {{ $opportunity->party?->party_name ?? $opportunity->lead?->company_name ?? '—' }} ·
                            {{ number_format((float) $opportunity->expected_value, 2) }}
                        </span>
                        <span class="d-block fs-11 text-muted">
                            {{ $opportunity->assignedUser?->name ?? 'Unassigned' }}
                            @if ($opportunity->expected_close_date)· close {{ $opportunity->expected_close_date->format('d M Y') }} @endif
                        </span>
                    </a>
                @empty
                    <p class="text-muted fs-12 mb-0">Nothing in this stage.</p>
                @endforelse
            </div>
        </div>
    </div>
@endforeach
</div>
@endsection
