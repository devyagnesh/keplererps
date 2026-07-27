/**
 * Shared AJAX form helpers for gap-close screens.
 */
$(function () {
    $('form[data-ajax="1"], #allocationForm, #periodLockForm, #stockTakeCreateForm, #stockTakeLinesForm, #priceListForm, #customFieldForm, #approvalRuleForm').each(function () {
        var $form = $(this);
        if ($form.data('bound')) {
            return;
        }
        $form.data('bound', true);
        $form.on('submit', function (e) {
            e.preventDefault();
            var $btn = $form.find('[type="submit"]').first();
            if (typeof btnLoading === 'function') {
                btnLoading($btn);
            }
            $.ajax({
                url: $form.attr('action'),
                type: ($form.attr('method') || 'POST').toUpperCase(),
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (typeof Notify !== 'undefined') {
                        Notify.success(response.message || 'Saved.');
                    }
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if ($form.data('reload')) {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed.';
                    if (typeof Notify !== 'undefined') {
                        Notify.error(message);
                    }
                },
                complete: function () {
                    if (typeof btnReset === 'function') {
                        btnReset($btn);
                    }
                }
            });
        });
    });
});
