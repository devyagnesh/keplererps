$(function () {
    var branchTable = $('#branchTable').DataTable({
        ajax: {
            url: window.branchDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'state', name: 'state', orderable: false },
            { data: 'is_head_office', name: 'is_head_office', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.btn-delete-branch', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete branch?',
            text: 'This will soft-delete the branch if it has no warehouses.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    branchTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    Notify.error(response.message || 'Failed to delete branch.');
                }
            });
        });
    });
});
