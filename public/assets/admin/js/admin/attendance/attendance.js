/**
 * Daily attendance sheet: bulk marking, AJAX save, and biometric CSV import.
 */
$(function () {
    $('.btn-mark-all').on('click', function () {
        $('.attendance-status').val($(this).data('status'));
    });

    $('#attendanceForm').on('submit', function (event) {
        event.preventDefault();
        submitAjaxForm(this);
    });

    $('#biometricImportForm').on('submit', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        var formData = new FormData(this);

        btnLoading($btn);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                Notify.success(response.message);
                window.location.reload();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Import failed.');
            },
            complete: function () {
                btnReset($btn);
            }
        });
    });
});
