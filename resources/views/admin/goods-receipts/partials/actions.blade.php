<a href="{{ route('admin.goods-receipts.edit', $goodsReceipt) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($goodsReceipt->status->value === 'draft')
@can('goods_receipt.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.goods-receipts.destroy', $goodsReceipt) }}">Delete</button>
@endcan
@endif
