/**
 * Shared register report screen: date/filter bar, AJAX rows, footer totals and CSV export.
 */
$(function () {
    var config = window.registerConfig;

    /**
     * Format a numeric cell with thousands separators.
     * @param {*} value Raw cell value.
     * @returns {string}
     */
    function formatNumber(value) {
        var number = parseFloat(value);
        if (isNaN(number)) {
            return '0';
        }

        return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    }

    /**
     * Escape values before injecting them into the table.
     * @param {*} value Raw cell value.
     * @returns {string}
     */
    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    /**
     * Render rows and totals into the register table.
     * @param {Object} payload Response data with rows, totals and truncated flag.
     */
    function render(payload) {
        var body = '';

        $.each(payload.rows, function (index, row) {
            body += '<tr>';
            $.each(config.columns, function (i, key) {
                var isNumeric = config.numeric.indexOf(key) !== -1;
                body += '<td class="' + (isNumeric ? 'text-end' : '') + '">'
                    + (isNumeric ? formatNumber(row[key]) : escapeHtml(row[key]))
                    + '</td>';
            });
            body += '</tr>';
        });

        if (body === '') {
            body = '<tr><td colspan="' + config.columns.length + '" class="text-muted">No records for the selected filters.</td></tr>';
        }

        $('#registerTable tbody').html(body);

        $.each(config.columns, function (i, key) {
            var $cell = $('#registerTable tfoot [data-total="' + key + '"]');
            if (config.numeric.indexOf(key) !== -1) {
                $cell.text(formatNumber(payload.totals[key]));
            } else if (i > 0) {
                $cell.text('');
            }
        });

        $('#truncatedNotice').toggleClass('d-none', !payload.truncated);
    }

    /**
     * Load the register for the current filter values.
     */
    function load() {
        var $btn = $('#registerFilters [type="submit"]');
        btnLoading($btn);

        $.ajax({
            url: config.dataUrl,
            type: 'GET',
            data: $('#registerFilters').serialize(),
            dataType: 'json',
            success: function (response) {
                render(response.data);
                $('#btnExport').attr('href', config.exportUrl + '?' + $('#registerFilters').serialize());
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load the register.');
            },
            complete: function () {
                btnReset($btn);
            }
        });
    }

    $('#registerFilters').on('submit', function (event) {
        event.preventDefault();
        load();
    });

    load();
});
