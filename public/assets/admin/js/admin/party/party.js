$(function () {
    var partyTable = $('#partyTable').DataTable({
        ajax: {
            url: window.partyDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.party_type = $('#filterPartyType').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'party_code', name: 'party_code' },
            { data: 'party_name', name: 'party_name' },
            { data: 'party_type', name: 'party_type' },
            { data: 'gstin', name: 'gstin' },
            { data: 'state', name: 'state', orderable: false },
            { data: 'status', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filterPartyType, #filterStatus').on('change', function () {
        partyTable.ajax.reload(null, false);
    });

    $(document).on('click', '.btn-delete-party', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete party?',
            text: 'Parties with transactions cannot be deleted.',
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
                    partyTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    Notify.error(response.message || 'Failed to delete party.');
                }
            });
        });
    });
});
