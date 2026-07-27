$(function () {
    var table = $('#itemTable').DataTable({
        ajax: {
            url: window.itemDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.item_type = $('#filterItemType').val();
                d.category_id = $('#filterCategory').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'item_code', name: 'item_code' },
            { data: 'item_name', name: 'item_name' },
            { data: 'item_type', name: 'item_type', orderable: false },
            { data: 'category', name: 'category', orderable: false },
            { data: 'stock_uom', name: 'stock_uom', orderable: false },
            { data: 'hsn', name: 'hsn', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterItemType, #filterCategory').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-item', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete item?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.');
                }
            });
        });
    });
});
