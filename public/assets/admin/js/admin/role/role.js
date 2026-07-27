$(function () {
    var roleTable = $('#roleTable').DataTable({
        ajax: {
            url: window.roleDataUrl,
            type: 'POST',
            data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'slug', name: 'slug' },
            { data: 'permissions_count', name: 'permissions_count', orderable: false },
            { data: 'is_system', name: 'is_system', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.btn-delete-role', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete role?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); roleTable.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });

    $(document).on('click', '.btn-copy-role', function () {
        var url = $(this).data('url');
        $.ajax({
            url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) window.location.href = response.redirect;
            },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Copy failed.'); }
        });
    });
});
