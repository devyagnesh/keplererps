$(function () {
    $('#btnClearCache').on('click', function () {
        var $btn = $(this);
        btnLoading($btn);
        $.ajax({
            url: $btn.data('url'),
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) { Notify.success(response.message); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Cache clear failed.'); },
            complete: function () { btnReset($btn); }
        });
    });
});
