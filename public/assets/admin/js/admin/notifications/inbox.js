/**
 * Inbox actions: mark one / mark all as read.
 */
$(function () {
    $(document).on('click', '.btn-mark-read', function () {
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Notify.success(response.message);
                window.location.reload();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not mark as read.');
            }
        });
    });

    $('#btnMarkAllRead').on('click', function () {
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Notify.success(response.message);
                window.location.reload();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not mark all as read.');
            }
        });
    });
});
