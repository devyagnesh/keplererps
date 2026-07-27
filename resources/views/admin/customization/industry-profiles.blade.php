@extends('admin.layouts.app')
@section('title', 'Industry Profiles')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Industry Profiles</h1></div>
@if($active)
<p class="text-muted">Active: <strong>{{ $active->name }}</strong> ({{ $active->code }})</p>
@endif
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered">
    <thead><tr><th>Code</th><th>Name</th><th>Costing</th><th>Flags</th><th></th></tr></thead>
    <tbody>
    @foreach($profiles as $profile)
        <tr>
            <td>{{ $profile->code }}</td>
            <td>{{ $profile->name }} @if($profile->is_active)<span class="badge bg-success-transparent">Active</span>@endif</td>
            <td>{{ $profile->costing['method'] ?? '—' }}</td>
            <td class="fs-12">{{ collect($profile->modules ?? [])->filter()->keys()->take(4)->implode(', ') }}</td>
            <td>
                @unless($profile->is_active)
                <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.industry-profiles.activate') }}">
                    @csrf
                    <input type="hidden" name="code" value="{{ $profile->code }}">
                    <button class="btn btn-sm btn-primary" type="submit">Activate</button>
                </form>
                @endunless
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
