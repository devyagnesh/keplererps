/**
 * Print view behaviour: manual print button plus auto-print when opened with ?print=1.
 */
$(function () {
    $('#btnPrintDocument').on('click', function () {
        window.print();
    });

    if (window.location.search.indexOf('print=1') !== -1) {
        window.print();
    }
});
