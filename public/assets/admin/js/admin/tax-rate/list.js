$(function () {
    var table = $('#masterTable').DataTable({
        ajax: { url: window.masterDataUrl, type: 'POST', data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); } },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'cgst_rate', name: 'cgst_rate', orderable: false },
            { data: 'sgst_rate', name: 'sgst_rate', orderable: false },
            { data: 'igst_rate', name: 'igst_rate' },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false },
        ]
    });
    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete record?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
