/**
 * Purchase order list DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.status = $('#filterStatus').val();
                d.supplier_id = $('#filterSupplier').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'document_no' }, { data: 'document_date' },
            { data: 'supplier', orderable: false }, { data: 'warehouse', orderable: false },
            { data: 'status', orderable: false }, { data: 'grand_total', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });
    $('#filterStatus, #filterSupplier').on('change', function () { table.ajax.reload(null, false); });
    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete draft PO?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
