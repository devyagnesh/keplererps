/**
 * Purchase bill form: load billable GRN lines, approve with mismatch reason, cancel.
 */
$(function () {
    /**
     * Render billable lines returned from the GRN lookup.
     *
     * @param {Array} rows Billable line payload.
     */
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<tr class="line-row">';
            html += '<td><input type="hidden" name="items[' + index + '][goods_receipt_item_id]" value="' + row.goods_receipt_item_id + '">';
            html += '<input type="hidden" name="items[' + index + '][uom_id]" value="' + row.uom_id + '">';
            html += '<input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></td>';
            html += '<td><input type="text" class="form-control" value="' + row.grn_qty + '" readonly></td>';
            html += '<td><input type="number" step="0.0001" class="form-control" name="items[' + index + '][quantity]" value="' + row.quantity + '" required></td>';
            html += '<td><input type="text" class="form-control" value="' + row.po_rate + '" readonly></td>';
            html += '<td><input type="number" step="0.0001" class="form-control" name="items[' + index + '][rate]" value="' + row.rate + '" required></td>';
            html += '<td><input type="number" step="0.01" class="form-control" name="items[' + index + '][gst_rate]" value="' + row.gst_rate + '"></td>';
            html += '<td>—</td>';
            html += '</tr>';
        });
        $('#lineRows').html(html || '<tr><td colspan="7" class="text-muted">No accepted quantity to bill on this GRN.</td></tr>');
    }

    $('#goodsReceiptId').on('change', function () {
        var id = $(this).val();
        if (!id || !window.purchaseBillLinesUrl) return;
        $.ajax({
            url: window.purchaseBillLinesUrl.replace(/0$/, id),
            type: 'GET',
            success: function (response) { renderLines(response.data); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load GRN lines.'); }
        });
    });

    $('#purchaseBillForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $(document).on('click', '.btn-approve-bill', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var matched = String($btn.data('matched')) === '1';

        var options = matched
            ? { title: 'Approve this purchase bill?', icon: 'question', showCancelButton: true, confirmButtonText: 'Approve' }
            : {
                title: 'Bill is outside match tolerance',
                text: 'Give a reason to approve the mismatch.',
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Reason for approving outside tolerance',
                showCancelButton: true,
                confirmButtonText: 'Approve anyway'
            };

        Swal.fire(options).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    mismatch_reason: matched ? null : result.value
                },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Approval failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    });

    $(document).on('click', '.btn-cancel-bill', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        Swal.fire({ title: 'Cancel this purchase bill?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Cancel bill' }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Cancellation failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    });
});
