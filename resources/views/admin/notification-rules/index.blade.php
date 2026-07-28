@extends('admin.layouts.app')
@section('title', 'Notification Rules')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Notification Rules</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-light btn-sm">My Inbox</a>
</div>

<div class="row">
<div class="col-xl-7">
    <div class="card custom-card">
        <div class="card-header"><div class="card-title">Rule Catalogue</div></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Audience</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $rule->name }}</div>
                            <div class="fs-11 text-muted">{{ $rule->code }}@if($rule->is_system) · system @endif</div>
                        </td>
                        <td>
                            <div>{{ $rule->event->label() }}</div>
                            <div class="fs-11 text-muted">{{ $rule->event->module() }}</div>
                        </td>
                        <td>
                            <div>{{ $rule->recipient_type->label() }}</div>
                            <div class="fs-11 text-muted">{{ $rule->recipient_value }}</div>
                        </td>
                        <td><span class="badge {{ $rule->channel->badgeClass() }}">{{ $rule->channel->label() }}</span></td>
                        <td>
                            <span class="badge {{ $rule->is_active ? 'bg-success-transparent' : 'bg-danger-transparent' }}">
                                {{ $rule->is_active ? 'Active' : 'Off' }}
                            </span>
                        </td>
                        <td>
                            <div class="hstack gap-2 fs-15">
                                @can('notification_rule.update')
                                <button type="button" class="btn btn-icon btn-sm btn-primary-light btn-edit-rule"
                                    data-url="{{ route('admin.notification-rules.update', $rule) }}"
                                    data-rule="{{ json_encode([
                                        'code' => $rule->code,
                                        'name' => $rule->name,
                                        'event' => $rule->event->value,
                                        'channel' => $rule->channel->value,
                                        'recipient_type' => $rule->recipient_type->value,
                                        'recipient_value' => $rule->recipient_value,
                                        'subject_template' => $rule->subject_template,
                                        'body_template' => $rule->body_template,
                                        'is_active' => $rule->is_active,
                                        'is_system' => $rule->is_system,
                                        'sort_order' => $rule->sort_order,
                                    ]) }}"><i class="ri-pencil-line"></i></button>
                                <button type="button" class="btn btn-icon btn-sm btn-warning-light btn-toggle-rule"
                                    data-url="{{ route('admin.notification-rules.toggle', $rule) }}"
                                    title="{{ $rule->is_active ? 'Disable' : 'Enable' }}"><i class="ri-toggle-line"></i></button>
                                @endcan
                                @can('notification_rule.delete')
                                @unless ($rule->is_system)
                                <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-rule"
                                    data-url="{{ route('admin.notification-rules.destroy', $rule) }}"><i class="ri-delete-bin-line"></i></button>
                                @endunless
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No notification rules yet. Seed defaults or add one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-xl-5">
    @canany(['notification_rule.create', 'notification_rule.update'])
    <div class="card custom-card">
        <div class="card-header"><div class="card-title" id="ruleFormTitle">Add Rule</div></div>
        <div class="card-body">
            <form id="notificationRuleForm" action="{{ route('admin.notification-rules.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="row gy-3">
                    <div class="col-12">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" maxlength="120" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control text-uppercase" name="code" maxlength="40" placeholder="Auto">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sort</label>
                        <input type="number" class="form-control" name="sort_order" min="0" max="9999" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Event *</label>
                        <select class="form-select" name="event" required>
                            <option value="">Select event</option>
                            @foreach ($lookups['events'] as $event)
                                <option value="{{ $event['value'] }}">{{ $event['module'] }} — {{ $event['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Channel *</label>
                        <select class="form-select" name="channel" required>
                            @foreach ($lookups['channels'] as $channel)
                                <option value="{{ $channel['value'] }}" @selected($channel['value'] === 'in_app') @disabled(! $channel['supported'])>
                                    {{ $channel['label'] }}@unless($channel['supported']) (later)@endunless
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Audience type *</label>
                        <select class="form-select" name="recipient_type" id="recipientType" required>
                            @foreach ($lookups['recipient_types'] as $type)
                                <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Audience *</label>
                        <select class="form-select" name="recipient_value" id="recipientValue" required>
                            <option value="">Select…</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subject template *</label>
                        <input type="text" class="form-control" name="subject_template" maxlength="200" required
                               placeholder="PO @{{document_no}} approved">
                        <div class="form-text">Placeholders: @{{document_no}}, @{{party_name}}, @{{period}}, …</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Body template *</label>
                        <textarea class="form-control" name="body_template" rows="3" maxlength="500" required></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ruleActive" checked>
                            <label class="form-check-label" for="ruleActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                    <button type="button" class="btn btn-light d-none" id="btnCancelRuleEdit">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endcanany
</div>
</div>
@endsection
@push('scripts')
<script>
window.NotificationRuleLookups = @json($lookups);
</script>
<script src="{{ asset('assets/admin/js/admin/notification-rules/rules.js') }}"></script>
@endpush
