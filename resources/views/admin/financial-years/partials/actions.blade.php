<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.financial-years.edit', $financialYear) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    @unless ($financialYear->is_closed)
        <button type="button" class="btn btn-success-light btn-set-current" data-url="{{ route('admin.financial-years.set-current', $financialYear) }}" title="Set current"><i class="ri-check-line"></i></button>
        <button type="button" class="btn btn-warning-light btn-close-fy" data-url="{{ route('admin.financial-years.close', $financialYear) }}" title="Close year"><i class="ri-lock-line"></i></button>
    @endunless
    @if (! $financialYear->is_closed && ! $financialYear->is_current)
        <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.financial-years.destroy', $financialYear) }}"><i class="ri-delete-bin-line"></i></button>
    @endif
</div>
