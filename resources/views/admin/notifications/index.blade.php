@extends('admin.layouts.app')
@section('title', 'My Notifications')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">My Notifications</h1>
        <p class="text-muted mb-0 fs-12">{{ $unreadCount }} unread</p>
    </div>
    <div class="hstack gap-2">
        @can('notification_rule.view')
        <a href="{{ route('admin.notification-rules.index') }}" class="btn btn-light btn-sm">Rule Catalogue</a>
        @endcan
        @if ($unreadCount > 0)
        <button type="button" class="btn btn-primary btn-sm" id="btnMarkAllRead"
            data-url="{{ route('admin.notifications.mark-all-read') }}">Mark all read</button>
        @endif
    </div>
</div>

<div class="card custom-card">
    <div class="card-body">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp
                <div class="list-group-item {{ $isUnread ? 'bg-primary-transparent' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">{{ $data['subject'] ?? 'Notification' }}</div>
                            <div class="text-muted">{{ $data['body'] ?? '' }}</div>
                            <div class="fs-11 text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                        </div>
                        <div class="hstack gap-2">
                            @if (! empty($data['url']))
                                <a href="{{ $data['url'] }}" class="btn btn-sm btn-light">Open</a>
                            @endif
                            @if ($isUnread)
                                <button type="button" class="btn btn-sm btn-primary-light btn-mark-read"
                                    data-url="{{ route('admin.notifications.mark-read', $notification->id) }}">Mark read</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted py-4 text-center">No notifications yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/notifications/inbox.js') }}"></script>
@endpush
