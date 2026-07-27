/**
 * Opportunity listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.stage = $('#filterStage').val();
                d.assigned_user_id = $('#filterOwner').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'opportunity_no' }, { data: 'opportunity_date' }, { data: 'title' },
            { data: 'customer', orderable: false }, { data: 'expected_value' },
            { data: 'weighted_value', orderable: false }, { data: 'expected_close_date' },
            { data: 'owner', orderable: false }, { data: 'stage', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterStage, #filterOwner, #filterDateFrom, #filterDateTo').on('change', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this opportunity?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
