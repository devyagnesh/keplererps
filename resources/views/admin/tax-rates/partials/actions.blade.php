<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.tax-rates.edit', $taxRate) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.tax-rates.destroy', $taxRate) }}"><i class="ri-delete-bin-line"></i></button>
</div>
