$(function () {
    var table = $('#masterTable').DataTable({
        ajax: { url: window.masterDataUrl, type: 'POST', data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); } },
        columns: [
            { data: 'id' }, { data: 'code' }, { data: 'name' }, { data: 'starts_on' }, { data: 'ends_on' },
            { data: 'is_current', orderable: false }, { data: 'is_closed', orderable: false }, { data: 'action', orderable: false }
        ]
    });
    function postAction(url, title) {
        Swal.fire({ title: title, icon: 'question', showCancelButton: true, confirmButtonText: 'Confirm' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (r) { Notify.success(r.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.'); }
            });
        });
    }
    $(document).on('click', '.btn-set-current', function () { postAction($(this).data('url'), 'Set as current financial year?'); });
    $(document).on('click', '.btn-close-fy', function () { postAction($(this).data('url'), 'Close this financial year? Costing method will lock.'); });
    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete financial year?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (r) { Notify.success(r.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
