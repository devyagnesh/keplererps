<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.document-series.edit', $series) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-info-light btn-preview-series" data-url="{{ route('admin.document-series.preview', $series) }}"><i class="ri-eye-line"></i></button>
    <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.document-series.destroy', $series) }}"><i class="ri-delete-bin-line"></i></button>
</div>
