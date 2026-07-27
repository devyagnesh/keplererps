/**
 * Chart of accounts listing DataTable.
 */
$(function () {
    var table = $('#masterTable').DataTable({
        ajax: {
            url: window.masterDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.account_type = $('#filterAccountType').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'code' }, { data: 'name' }, { data: 'account_type', orderable: false },
            { data: 'account_group', orderable: false }, { data: 'parent', orderable: false },
            { data: 'opening_balance', orderable: false }, { data: 'is_active', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterAccountType').on('change', function () { table.ajax.reload(null, false); });

    $(document).on('click', '.btn-delete-master', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this account?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); table.ajax.reload(null, false); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
