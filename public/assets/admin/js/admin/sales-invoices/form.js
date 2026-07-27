/**
 * Sales invoice form: load pending SO lines, confirm invoice.
 */
$(function () {
    /**
     * Render invoice line rows from pending sales order items.
     * @param {Array<Object>} rows
     */
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<div class="row g-2 mb-2 line-row">';
            html += '<input type="hidden" name="items[' + index + '][sales_order_item_id]" value="' + row.sales_order_item_id + '">';
            html += '<div class="col-md-3"><input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></div>';
            html += '<div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control invoice-qty" name="items[' + index + '][quantity]" value="' + row.pending_qty + '" placeholder="Qty" required></div>';
            html += '<div class="col-md-2"><input type="number" step="0.0001" min="0" class="form-control" name="items[' + index + '][rate]" value="' + row.rate + '" placeholder="Rate" required></div>';
            html += '<div class="col-md-2"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[' + index + '][discount_percent]" value="' + (row.discount_percent || 0) + '" placeholder="Disc %"></div>';
            html += '<div class="col-md-2"><input type="number" step="0.01" min="0" class="form-control" name="items[' + index + '][gst_rate]" value="' + (row.gst_rate || 0) + '" placeholder="GST"></div>';
            html += '</div>';
        });
        $('#lineRows').html(html || '<p class="text-muted">No pending quantities on this sales order.</p>');
    }

    $('#salesOrderId').on('change', function () {
        var id = $(this).val();
        var baseUrl = $('#salesInvoiceForm').data('pending-lines-url');
        if (!id || !baseUrl) return;
        $.ajax({
            url: baseUrl + '/' + id,
            type: 'GET',
            success: function (response) { renderLines(response.data); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load sales order lines.'); }
        });
    });

    $('#salesInvoiceForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $(document).on('click', '.btn-confirm-invoice', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({ title: 'Confirm invoice and post delivery?', icon: 'question', showCancelButton: true, confirmButtonText: 'Confirm' }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                    else window.location.reload();
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Confirmation failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    });
});
