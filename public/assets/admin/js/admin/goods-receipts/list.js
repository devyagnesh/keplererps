/**
 * GRN listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); }
        },
        columns: [
            { data: 'id' }, { data: 'document_no' }, { data: 'document_date' },
            { data: 'purchase_order', orderable: false }, { data: 'supplier', orderable: false },
            { data: 'status', orderable: false }, { data: 'action', orderable: false, searchable: false }
        ]
    });
    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete draft GRN?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
