/**
 * Purchase order form and workflow actions.
 */
$(function () {
    $('#btnAddLine').on('click', function () {
        var index = $('#lineRows .line-row').length;
        $('#lineRows').append($('#tplLine').html().replace(/__INDEX__/g, String(index)));
    });
    $(document).on('click', '.btn-remove-line', function () { $(this).closest('.line-row').remove(); });
    $(document).on('change', '.po-item', function () {
        var $opt = $(this).find(':selected');
        var $row = $(this).closest('.line-row');
        if ($opt.data('uom')) { $row.find('.po-uom').val(String($opt.data('uom'))); }
        if ($opt.data('rate') !== undefined) { $row.find('input[name*="[rate]"]').val($opt.data('rate')); }
        if ($opt.data('gst') !== undefined) { $row.find('input[name*="[gst_rate]"]').val($opt.data('gst')); }
    });
    $('#purchaseOrderForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

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

    $(document).on('click', '.btn-approve-po', function () { postAction($(this).data('url'), $(this), 'Approve this purchase order?'); });
    $(document).on('click', '.btn-mark-sent', function () { postAction($(this).data('url'), $(this), 'Mark PO as sent to supplier?'); });
});
