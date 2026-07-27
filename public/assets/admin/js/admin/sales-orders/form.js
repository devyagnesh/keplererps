/**
 * Sales order form and workflow actions.
 */
$(function () {
    $('#btnAddLine').on('click', function () {
        var index = $('#lineRows .line-row').length;
        $('#lineRows').append($('#tplLine').html().replace(/__INDEX__/g, String(index)));
    });
    $(document).on('click', '.btn-remove-line', function () { $(this).closest('.line-row').remove(); });
    $(document).on('change', '.so-item', function () {
        var $opt = $(this).find(':selected');
        var $row = $(this).closest('.line-row');
        if ($opt.data('uom')) { $row.find('.so-uom').val(String($opt.data('uom'))); }
        if ($opt.data('rate') !== undefined) { $row.find('input[name*="[rate]"]').val($opt.data('rate')); }
        if ($opt.data('gst') !== undefined) { $row.find('input[name*="[gst_rate]"]').val($opt.data('gst')); }
    });
    bindAvailabilityLookup($('#salesOrderForm'), '.so-item', '.so-atp');

    $('#salesOrderForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Post a workflow action with confirmation.
     * @param {string} url
     * @param {jQuery} $btn
     * @param {string} title
     */
    function postAction(url, $btn, title) {
        Swal.fire({ title: title, icon: 'question', showCancelButton: true, confirmButtonText: 'Confirm' }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                    else window.location.reload();
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    }

    $(document).on('click', '.btn-confirm-so', function () { postAction($(this).data('url'), $(this), 'Confirm this sales order?'); });
    $(document).on('click', '.btn-cancel-so', function () { postAction($(this).data('url'), $(this), 'Cancel this sales order?'); });
});
