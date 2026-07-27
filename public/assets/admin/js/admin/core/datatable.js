/**
 * Global DataTable defaults for server-side listings.
 */
$(function () {
    if (typeof $.fn.dataTable === 'undefined') {
        return;
    }

    $.extend(true, $.fn.dataTable.defaults, {
        processing: true,
        serverSide: true,
        responsive: true,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No records found.',
            zeroRecords: 'No matching records found.'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']]
    });
});
