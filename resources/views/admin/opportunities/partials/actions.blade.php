<div class="hstack gap-2 fs-15">
    @can('opportunity.view')
    <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="btn btn-icon btn-sm btn-primary-light" title="Open"><i class="ri-eye-line"></i></a>
    @endcan
    @can('opportunity.delete')
        @if ($opportunity->stage->isOpen() && $opportunity->quotation_id === null)
        <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.opportunities.destroy', $opportunity) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
        @endif
    @endcan
</div>
