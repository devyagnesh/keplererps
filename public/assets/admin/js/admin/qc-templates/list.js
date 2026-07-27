/**
 * QC template list DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.is_active = $('#filterActive').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'name' },
            { data: 'inspection_type', orderable: false },
            { data: 'sampling_plan', orderable: false },
            { data: 'scope', orderable: false },
            { data: 'is_active', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterActive').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete QC template?',
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
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
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
