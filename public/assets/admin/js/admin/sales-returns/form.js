/**
 * Sales return form: load returnable invoice lines, post and cancel.
 */
$(function () {
    /**
     * Render returnable lines returned from the invoice lookup.
     *
     * @param {Array} rows Returnable line payload.
     */
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<tr class="line-row">';
            html += '<td><input type="hidden" name="items[' + index + '][sales_invoice_item_id]" value="' + row.sales_invoice_item_id + '">';
            html += '<input type="hidden" name="items[' + index + '][item_id]" value="' + row.item_id + '">';
            html += '<input type="hidden" name="items[' + index + '][uom_id]" value="' + row.uom_id + '">';
            html += '<input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></td>';
            html += '<td><input type="text" class="form-control" value="—" readonly></td>';
            html += '<td><input type="number" step="0.0001" class="form-control" name="items[' + index + '][quantity]" value="' + row.open_qty + '" required></td>';
            html += '<td><input type="number" step="0.0001" class="form-control" name="items[' + index + '][rate]" value="' + row.rate + '" required></td>';
            html += '<td><input type="number" step="0.01" class="form-control" name="items[' + index + '][gst_rate]" value="' + row.gst_rate + '"></td>';
            html += '</tr>';
        });
        $('#lineRows').html(html || '<tr><td colspan="5" class="text-muted">Nothing left to return on this invoice.</td></tr>');
    }

    $('#salesInvoiceId').on('change', function () {
        var id = $(this).val();
        if (!id || !window.salesReturnLinesUrl) return;
        $.ajax({
            url: window.salesReturnLinesUrl.replace(/0$/, id),
            type: 'GET',
            success: function (response) { renderLines(response.data); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load invoice lines.'); }
        });
    });

    $('#salesReturnForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Confirm and fire a POST action on the current return.
     *
     * @param {jQuery} $btn Clicked button.
     * @param {string} title Confirmation title.
     * @param {string} confirmText Confirm button label.
     */
    function confirmAction($btn, title, confirmText) {
        Swal.fire({ title: title, icon: 'question', showCancelButton: true, confirmButtonText: confirmText }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: $btn.data('url'), type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    }

    $(document).on('click', '.btn-post-return', function () {
        confirmAction($(this), 'Post return to stock ledger?', 'Post');
    });

    $(document).on('click', '.btn-cancel-return', function () {
        confirmAction($(this), 'Cancel this return and reverse stock?', 'Cancel return');
    });
});
