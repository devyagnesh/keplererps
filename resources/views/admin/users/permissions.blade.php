@extends('admin.layouts.app')
@section('title', 'Effective Permissions')
@section('content')
<div class="my-4">
    <h1 class="page-title fw-semibold fs-18 mb-0">Effective Permissions — {{ $userModel->name }}</h1>
</div>
<div class="card custom-card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>Permission</th><th>Label</th><th>Source</th></tr></thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td><code>{{ $permission['name'] }}</code></td>
                    <td>{{ $permission['label'] }}</td>
                    <td>{{ $permission['source'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No permissions assigned.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <a href="{{ route('admin.users.edit', $userModel) }}" class="btn btn-light">Back</a>
</div></div>
@endsection
