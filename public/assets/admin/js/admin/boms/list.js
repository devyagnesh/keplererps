/**
 * BOM listing DataTable and delete actions.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.item_id = $('#filterItem').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'bom_number', name: 'bom_number' },
            { data: 'item', name: 'item', orderable: false },
            { data: 'version', name: 'version', orderable: false },
            { data: 'valid_from', name: 'valid_from' },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'rolled_total_cost', name: 'rolled_total_cost', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterItem').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this BOM?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.');
                }
            });
        });
    });
});
