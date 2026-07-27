$(function () {
    var table = $('#masterTable').DataTable({
        ajax: { url: window.masterDataUrl, type: 'POST', data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); } },
        columns: [
            { data: 'id' }, { data: 'document_type' }, { data: 'prefix' },
            { data: 'fy', orderable: false }, { data: 'branch', orderable: false },
            { data: 'next_number', orderable: false }, { data: 'is_active', orderable: false },
            { data: 'action', orderable: false }
        ]
    });
    $(document).on('click', '.btn-preview-series', function () {
        $.getJSON($(this).data('url'), function (response) {
            Notify.info('Next number: ' + ((response.data && response.data.next) || '—'));
        }).fail(function () { Notify.error('Preview failed.'); });
    });
    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete series?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (r) { Notify.success(r.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
