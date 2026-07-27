/**
 * Global AJAX configuration.
 * Sets CSRF token header for all jQuery AJAX requests.
 */
$(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});
