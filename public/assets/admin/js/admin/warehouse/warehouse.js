$(function () {
    var warehouseTable = $('#warehouseTable').DataTable({
        ajax: {
            url: window.warehouseDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.branch_id = $('#filterBranch').val();
                d.level = $('#filterLevel').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'branch', name: 'branch', orderable: false },
            { data: 'parent', name: 'parent', orderable: false },
            { data: 'level', name: 'level' },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterBranch, #filterLevel').on('change', function () {
        warehouseTable.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-warehouse', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete warehouse?',
            text: 'Parents with children cannot be deleted.',
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
                    warehouseTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    Notify.error(response.message || 'Failed to delete warehouse.');
                }
            });
        });
    });
});
