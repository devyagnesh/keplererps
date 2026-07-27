/**
 * Delivery challan form: pending SO lines, dispatch, POD upload, e-way payload.
 */
$(function () {
    /**
     * Toggle vehicle number required indicator for road transport.
     */
    function syncTransportMode() {
        var isRoad = $('#transportMode').val() === 'road';
        $('.vehicle-required').toggleClass('d-none', !isRoad);
        $('#vehicleNumber').prop('required', isRoad && !$('#vehicleNumber').prop('readonly'));
    }

    /**
     * Build batch column HTML for a line row when batch tracking applies.
     * @param {number} index
     * @param {Object} row
     * @param {string|number} batchId
     * @return {string}
     */
    function batchColumnHtml(index, row, batchId) {
        if (row.tracking_type !== 'batch') {
            return '';
        }
        return '<div class="col-md-2"><input type="number" class="form-control" name="items[' + index + '][batch_id]" value="' + (batchId || '') + '" placeholder="Batch ID" min="1"></div>';
    }

    /**
     * Render dispatch line rows from pending sales order items.
     * @param {Array<Object>} rows
     */
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<div class="row g-2 mb-2 line-row">';
            html += '<input type="hidden" name="items[' + index + '][sales_order_item_id]" value="' + row.sales_order_item_id + '">';
            html += '<div class="col-md-3"><input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></div>';
            html += '<div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control challan-qty" name="items[' + index + '][quantity]" value="' + row.pending_qty + '" placeholder="Qty" required></div>';
            html += batchColumnHtml(index, row, '');
            html += '<div class="col-md-3"><input type="text" class="form-control" name="items[' + index + '][description]" value="' + (row.description || '') + '" placeholder="Description"></div>';
            html += '</div>';
        });
        $('#lineRows').html(html || '<p class="text-muted">No pending quantities on this sales order.</p>');
    }

    syncTransportMode();

    $('#transportMode').on('change', syncTransportMode);

    $('#transporterId').on('change', function () {
        var gstin = $(this).find(':selected').data('gstin') || '';
        if (gstin) {
            $('#transporterGstin').val(gstin);
        }
    });

    $('#salesOrderId').on('change', function () {
        var id = $(this).val();
        var baseUrl = $('#deliveryChallanForm').data('pending-lines-url');
        if (!id || !baseUrl) {
            return;
        }
        $.ajax({
            url: baseUrl + '/' + id,
            type: 'GET',
            success: function (response) {
                renderLines(response.data);
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load sales order lines.');
            }
        });
    });

    $('#deliveryChallanForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) {
            $(el).addClass('is-invalid');
        },
        unhighlight: function (el) {
            $(el).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });

    $('#podForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) {
            $(el).addClass('is-invalid');
        },
        unhighlight: function (el) {
            $(el).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });

    $(document).on('click', '.btn-dispatch-challan', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({ title: 'Dispatch challan and issue stock?', icon: 'question', showCancelButton: true, confirmButtonText: 'Dispatch' }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            btnLoading($btn);
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Dispatch failed.');
                },
                complete: function () {
                    btnReset($btn);
                }
            });
        });
    });

    $(document).on('click', '.btn-eway-payload', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        btnLoading($btn);
        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'eway-payload.json';
                link.click();
                URL.revokeObjectURL(link.href);
                Notify.success(response.message);
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not generate e-way payload.');
            },
            complete: function () {
                btnReset($btn);
            }
        });
    });
});
