/**
 * Asset form AJAX submit.
 */
$(function () {
    function btnLoading($btn) {
        $btn.prop('disabled', true).data('original-text', $btn.html())
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    }
    function btnReset($btn) { $btn.prop('disabled', false).html($btn.data('original-text')); }

    $('#masterForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) {
            var $form = $(form);
            var $btn = $form.find('[type="submit"]');
            btnLoading($btn);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) { window.location.href = response.redirect; }
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save asset.');
                },
                complete: function () { btnReset($btn); }
            });
        }
    });
});
