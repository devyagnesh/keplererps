/**
 * Purchase suggestion list (US-M07-01).
 */
$(function () {
    /**
     * Human label for where a suggestion came from.
     *
     * @param {Object} row Suggestion row from the API.
     * @returns {string}
     */
    function sourceLabel(row) {
        if (row.source === 'production_plan') {
            return '<span class="badge bg-warning-transparent">Plan shortage</span> ' + (row.reference || '');
        }

        return '<span class="badge bg-primary-transparent">Reorder level</span>';
    }

    function loadSuggestions() {
        $.ajax({
            url: window.purchaseSuggestionUrl,
            type: 'GET',
            data: { warehouse_id: $('#filterWarehouse').val() },
            success: function (response) {
                var html = '';
                $.each(response.data || [], function (_, row) {
                    var createUrl = window.purchaseOrderCreateUrl + '?item_id=' + row.item_id + '&warehouse_id=' + row.warehouse_id + '&qty=' + row.suggested_qty;
                    html += '<tr>';
                    html += '<td>' + row.item_code + ' — ' + row.item_name + '</td>';
                    html += '<td>' + (row.warehouse || '') + '</td>';
                    html += '<td>' + sourceLabel(row) + '</td>';
                    html += '<td>' + row.free_qty + '</td>';
                    html += '<td>' + row.on_order_qty + '</td>';
                    html += '<td>' + row.reorder_level + '</td>';
                    html += '<td>' + row.suggested_qty + '</td>';
                    html += '<td><a class="btn btn-sm btn-primary-light" href="' + createUrl + '">Create PO</a></td>';
                    html += '</tr>';
                });
                $('#suggestionTable tbody').html(html || '<tr><td colspan="8" class="text-muted">Nothing to purchase right now.</td></tr>');
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load suggestions.');
            }
        });
    }

    $('#btnLoadSuggestions').on('click', loadSuggestions);
    $('#filterWarehouse').on('change', loadSuggestions);
    loadSuggestions();
});
