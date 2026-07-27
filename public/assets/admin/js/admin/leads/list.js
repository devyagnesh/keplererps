/**
 * Lead listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.status = $('#filterStatus').val();
                d.source = $('#filterSource').val();
                d.assigned_user_id = $('#filterOwner').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
                d.due_only = $('#filterDueOnly').is(':checked') ? 1 : '';
            }
        },
        columns: [
            { data: 'id' }, { data: 'lead_no' }, { data: 'lead_date' }, { data: 'company_name' },
            { data: 'contact', orderable: false }, { data: 'source', orderable: false },
            { data: 'estimated_value' }, { data: 'next_follow_up_date' },
            { data: 'owner', orderable: false }, { data: 'status', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterStatus, #filterSource, #filterOwner, #filterDateFrom, #filterDateTo, #filterDueOnly').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this lead?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
