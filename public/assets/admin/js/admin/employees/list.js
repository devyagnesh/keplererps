/**
 * Employee listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.status = $('#filterStatus').val();
                d.shift_id = $('#filterShift').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'employee_code' }, { data: 'full_name' },
            { data: 'designation', orderable: false }, { data: 'department' },
            { data: 'shift', orderable: false }, { data: 'date_of_joining' },
            { data: 'monthly_gross' }, { data: 'status', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterStatus, #filterShift').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this employee?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
