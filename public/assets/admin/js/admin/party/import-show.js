$(function () {
    $('#commitImportBtn').on('click', function () {
        var $btn = $(this);
        btnLoading($btn);
        $.ajax({
            url: $btn.data('url'),
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Notify.success(response.message);
                window.importStatus = 'processing';
                pollStatus();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Commit failed.');
            },
            complete: function () { btnReset($btn); }
        });
    });

    /**
     * Poll import status while processing.
     */
    function pollStatus() {
        if (window.importStatus !== 'processing') {
            return;
        }
        $.getJSON(window.importStatusUrl).done(function (response) {
            var data = response.data;
            $('#statStatus').text(data.status.charAt(0).toUpperCase() + data.status.slice(1));
            if (data.status === 'completed' || data.status === 'failed') {
                window.importStatus = data.status;
                Notify.info(data.status === 'completed'
                    ? (data.imported_rows + ' imported, ' + data.skipped_rows + ' skipped')
                    : (data.failure_reason || 'Import failed'));
                window.location.reload();
                return;
            }
            setTimeout(pollStatus, 2000);
        });
    }

    if (window.importStatus === 'processing') {
        pollStatus();
    }
});
