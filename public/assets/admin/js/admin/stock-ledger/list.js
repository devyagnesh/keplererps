$(function () {
    var table = $('#ledgerTable').DataTable({
        ajax: {
            url: window.ledgerDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.warehouse_id = $('#filterWarehouse').val();
                d.item_id = $('#filterItem').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'posting_at' }, { data: 'item', orderable: false },
            { data: 'warehouse', orderable: false }, { data: 'batch', orderable: false },
            { data: 'type', orderable: false }, { data: 'qty_in' }, { data: 'qty_out' },
            { data: 'rate' }, { data: 'value' }, { data: 'balance_qty' }
        ]
    });
    $('#filterWarehouse, #filterItem, #filterDateFrom, #filterDateTo').on('change', function () {
        table.ajax.reload(null, false);
    });
});
