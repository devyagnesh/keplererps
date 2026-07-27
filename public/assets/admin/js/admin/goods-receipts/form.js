/**
 * GRN form: load pending PO lines, post to stock.
 */
$(function () {
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<div class="row g-2 mb-2 line-row">';
            html += '<input type="hidden" name="items[' + index + '][purchase_order_item_id]" value="' + row.purchase_order_item_id + '">';
            html += '<div class="col-md-3"><input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></div>';
            html += '<div class="col-md-1"><input type="number" step="0.0001" class="form-control received-qty" name="items[' + index + '][received_qty]" value="' + row.pending_qty + '" required></div>';
            html += '<div class="col-md-1"><input type="number" step="0.0001" class="form-control accepted-qty" name="items[' + index + '][accepted_qty]" value="' + row.pending_qty + '" required></div>';
            html += '<div class="col-md-1"><input type="number" step="0.0001" class="form-control" name="items[' + index + '][rejected_qty]" value="0"></div>';
            html += '<div class="col-md-2"><input type="text" class="form-control" name="items[' + index + '][rejection_reason]" placeholder="Reject reason"></div>';
            html += '<div class="col-md-1"><input type="number" step="0.0001" class="form-control" name="items[' + index + '][rate]" value="' + row.rate + '"></div>';
            html += '<div class="col-md-1"><input type="text" class="form-control" name="items[' + index + '][batch_no]" placeholder="Batch"></div>';
            html += '<div class="col-md-2"><input type="text" class="form-control" name="items[' + index + '][serial_no]" placeholder="Serial"></div>';
            html += '</div>';
        });
        $('#lineRows').html(html || '<p class="text-muted">No pending quantities on this PO.</p>');
    }

    $('#purchaseOrderId').on('change', function () {
        var id = $(this).val();
        if (!id || !window.grnPendingLinesUrl) return;
        $.ajax({
            url: window.grnPendingLinesUrl + '/' + id,
            type: 'GET',
            success: function (response) { renderLines(response.data); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load PO lines.'); }
        });
    });

    $(document).on('change', '.received-qty', function () {
        var $row = $(this).closest('.line-row');
        $row.find('.accepted-qty').val($(this).val());
    });

    $('#grnForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $(document).on('click', '.btn-post-grn', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({ title: 'Post GRN to stock ledger?', icon: 'question', showCancelButton: true, confirmButtonText: 'Post' }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Posting failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    });
});
