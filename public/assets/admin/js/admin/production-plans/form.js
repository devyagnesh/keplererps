/**
 * Production plan form: pull open sales demand, generate work orders, cancel plan.
 */
$(function () {
    /**
     * Render plan lines from the open demand payload.
     *
     * @param {Array} rows Demand rows returned by the API.
     */
    function renderLines(rows) {
        var html = '';
        $.each(rows || [], function (index, row) {
            html += '<tr class="line-row">';
            html += '<td><input type="hidden" name="items[' + index + '][item_id]" value="' + row.item_id + '">';
            html += '<input type="hidden" name="items[' + index + '][bom_id]" value="' + row.bom_id + '">';
            html += '<input type="hidden" name="items[' + index + '][sales_order_id]" value="' + (row.sales_order_id || '') + '">';
            html += '<input type="hidden" name="items[' + index + '][sales_order_item_id]" value="' + (row.sales_order_item_id || '') + '">';
            html += '<input type="text" class="form-control" value="' + (row.item_code || '') + ' — ' + (row.item_name || '') + '" readonly></td>';
            html += '<td><input type="text" class="form-control" value="' + (row.bom_label || '') + '" readonly></td>';
            html += '<td><input type="text" class="form-control" value="' + (row.sales_order_no || '—') + '" readonly></td>';
            html += '<td><input type="number" step="0.0001" class="form-control" name="items[' + index + '][planned_quantity]" value="' + row.open_quantity + '" required></td>';
            html += '<td><input type="date" class="form-control" name="items[' + index + '][required_date]" value="' + (row.required_date || '') + '"></td>';
            html += '<td><input type="text" class="form-control" value="—" readonly></td>';
            html += '</tr>';
        });
        $('#lineRows').html(html || '<tr><td colspan="6" class="text-muted">No open manufacturing demand in this horizon.</td></tr>');
    }

    $('#btnLoadDemand').on('click', function () {
        var $btn = $(this);
        btnLoading($btn);
        $.ajax({
            url: window.productionPlanDemandUrl,
            type: 'GET',
            data: {
                plan_from_date: $('#planFromDate').val(),
                plan_to_date: $('#planToDate').val()
            },
            success: function (response) { renderLines(response.data); },
            error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load demand.'); },
            complete: function () { btnReset($btn); }
        });
    });

    $('#productionPlanForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Confirm and fire a POST action on the current plan.
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

    $(document).on('click', '.btn-generate-plan', function () {
        confirmAction($(this), 'Generate draft work orders for this plan?', 'Generate');
    });

    $(document).on('click', '.btn-cancel-plan', function () {
        confirmAction($(this), 'Cancel this plan and delete its draft work orders?', 'Cancel plan');
    });
});
