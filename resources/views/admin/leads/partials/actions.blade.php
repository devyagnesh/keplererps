<div class="hstack gap-2 fs-15">
    @can('lead.view')
    <a href="{{ route('admin.leads.edit', $lead) }}" class="btn btn-icon btn-sm btn-primary-light" title="Open"><i class="ri-eye-line"></i></a>
    @endcan
    @can('lead.delete')
        @if ($lead->status !== \App\Enums\LeadStatus::Converted)
        <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.leads.destroy', $lead) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
        @endif
    @endcan
</div>
