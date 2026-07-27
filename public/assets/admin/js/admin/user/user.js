$(function () {
    var userTable = $('#userTable').DataTable({
        ajax: {
            url: window.userDataUrl,
            type: 'POST',
            data: function (d) { d._token = $('meta[name="csrf-token"]').attr('content'); }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'username', name: 'username' },
            { data: 'email', name: 'email' },
            { data: 'roles', name: 'roles', orderable: false },
            { data: 'branch', name: 'branch', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.btn-delete-user', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete user?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); userTable.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
