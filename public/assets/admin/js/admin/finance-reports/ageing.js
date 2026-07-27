/**
 * AR/AP ageing screen.
 */
$(function () {
    /**
     * Format a numeric value with two decimals.
     *
     * @param {number|string} value Raw amount.
     * @returns {string}
     */
    function amount(value) {
        return (parseFloat(value) || 0).toFixed(2);
    }

    function loadAgeing() {
        var $btn = $('#btnLoadAgeing');
        btnLoading($btn);

        $.ajax({
            url: window.ageingDataUrl,
            type: 'GET',
            data: { type: $('#filterType').val(), as_on_date: $('#filterAsOnDate').val() },
            success: function (response) {
                var html = '';
                $.each(response.data || [], function (_, row) {
                    html += '<tr>';
                    html += '<td>' + (row.party_code || '') + ' — ' + (row.party_name || '') + '</td>';
                    html += '<td>' + amount(row.outstanding) + '</td>';
                    html += '<td>' + amount(row.bucket_0_30) + '</td>';
                    html += '<td>' + amount(row.bucket_31_60) + '</td>';
                    html += '<td>' + amount(row.bucket_61_90) + '</td>';
                    html += '<td>' + amount(row.bucket_90_plus) + '</td>';
                    html += '</tr>';
                });
                $('#ageingTable tbody').html(html || '<tr><td colspan="6" class="text-muted">No outstanding balances.</td></tr>');
                updateExportLink();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load ageing.');
            },
            complete: function () { btnReset($btn); }
        });
    }

    /**
     * Keep the CSV export link in sync with the current filters.
     */
    function updateExportLink() {
        $('#btnExportAgeing').attr(
            'href',
            window.ageingExportUrl + '?type=' + $('#filterType').val() + '&as_on_date=' + $('#filterAsOnDate').val()
        );
    }

    $('#btnLoadAgeing').on('click', loadAgeing);
    $('#filterType, #filterAsOnDate').on('change', loadAgeing);
    loadAgeing();
});
