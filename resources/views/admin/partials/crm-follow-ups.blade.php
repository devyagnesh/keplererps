{{-- Shared follow-up log for leads and opportunities (M05). --}}
<div class="card custom-card">
<div class="card-header"><div class="card-title">Follow-up Log</div></div>
<div class="card-body">
@if ($canLog)
<form id="followUpForm" action="{{ $followUpUrl }}" method="POST" class="row gy-2 mb-4" novalidate>
    @csrf
    <div class="col-md-2"><label class="form-label">Date *</label>
        <input type="date" class="form-control" name="follow_up_date" value="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-md-2"><label class="form-label">Mode *</label>
        <select class="form-select" name="mode" required>
            @foreach (\App\Enums\FollowUpMode::cases() as $mode)
                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Summary *</label>
        <input type="text" class="form-control" name="summary" placeholder="What was discussed" required>
    </div>
    <div class="col-md-2"><label class="form-label">Outcome</label>
        <input type="text" class="form-control" name="outcome">
    </div>
    <div class="col-md-2"><label class="form-label">Next Follow-up</label>
        <input type="date" class="form-control" name="next_follow_up_date">
    </div>
    <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100" type="submit">Log</button>
    </div>
</form>
@endif

<div class="table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Date</th><th>Mode</th><th>Summary</th><th>Outcome</th><th>Next</th><th>By</th></tr></thead>
    <tbody>
    @forelse ($owner->followUps as $followUp)
        <tr>
            <td>{{ $followUp->follow_up_date?->format('d M Y') }}</td>
            <td>{{ $followUp->mode->label() }}</td>
            <td class="text-wrap">{{ $followUp->summary }}</td>
            <td>{{ $followUp->outcome ?? '—' }}</td>
            <td>{{ $followUp->next_follow_up_date?->format('d M Y') ?? '—' }}</td>
            <td>{{ $followUp->createdBy?->name ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No follow-ups logged yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div></div>
