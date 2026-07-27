/**
 * Maintenance order list DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.status = $('#filterStatus').val();
                d.order_type = $('#filterType').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'document_no' },
            { data: 'document_date' },
            { data: 'asset', orderable: false },
            { data: 'order_type', orderable: false },
            { data: 'status', orderable: false },
            { data: 'downtime', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterStatus, #filterType').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Cancel maintenance order?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Cancel order' })
            .then(function (result) {
                if (!result.isConfirmed) { return; }
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                    error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Cancel failed.'); }
                });
            });
    });
});
