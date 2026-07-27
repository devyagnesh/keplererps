/**
 * Package listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.status = $('#filterStatus').val();
                d.warehouse_id = $('#filterWarehouse').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'label_no' }, { data: 'item', orderable: false },
            { data: 'packing_unit', orderable: false }, { data: 'batch', orderable: false },
            { data: 'quantity' }, { data: 'challan', orderable: false },
            { data: 'packed_at' }, { data: 'status', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterStatus, #filterWarehouse').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Cancel this package?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Cancel package' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not cancel the package.'); }
            });
        });
    });
});
