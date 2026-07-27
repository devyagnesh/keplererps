$(function () {
    var table = $('#balanceTable').DataTable({
        ajax: {
            url: window.balanceDataUrl,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.warehouse_id = $('#filterWarehouse').val();
                d.category_id = $('#filterCategory').val();
            }
        },
        columns: [
            { data: 'id' }, { data: 'item_code' }, { data: 'item_name', orderable: false },
            { data: 'warehouse', orderable: false }, { data: 'batch', orderable: false },
            { data: 'qty' }, { data: 'committed_qty', orderable: false },
            { data: 'available_qty', orderable: false }, { data: 'value' }
        ]
    });

    function refreshSummary() {
        $.getJSON(window.balanceSummaryUrl, {
            warehouse_id: $('#filterWarehouse').val(),
            category_id: $('#filterCategory').val()
        }, function (response) {
            var data = response.data || {};
            $('#summaryQty').text(Number(data.total_qty || 0).toFixed(4));
            $('#summaryValue').text(Number(data.total_value || 0).toFixed(2));
            $('#summaryLines').text(data.lines || 0);
        });
    }

    $('#filterWarehouse, #filterCategory').on('change', function () {
        table.ajax.reload(null, false);
        refreshSummary();
    });
});
