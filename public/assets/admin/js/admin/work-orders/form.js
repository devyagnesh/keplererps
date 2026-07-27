/**
 * Work order form: BOM lookup, release, close, manual material issue.
 */
$(function () {
    var $form = $('#workOrderForm');
    var bomsUrl = $form.data('boms-url');
    var selectedBomId = String($form.data('selected-bom-id') || '');

    /**
     * Load active BOM options for the selected manufacturable item.
     * @param {string|number} itemId
     */
    function loadBoms(itemId) {
        var $bom = $('#bomId');
        if (!itemId || !bomsUrl) {
            $bom.html('<option value="">Select BOM</option>');
            if ($bom.hasClass('select2-hidden-accessible')) {
                $bom.trigger('change.select2');
            }
            return;
        }

        $bom.prop('disabled', true);
        $.ajax({
            url: bomsUrl + '/' + itemId,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                var html = '<option value="">Select BOM</option>';
                $.each(response.data || [], function (_, row) {
                    var selected = String(row.id) === selectedBomId ? ' selected' : '';
                    html += '<option value="' + row.id + '"' + selected + '>' + row.label + '</option>';
                });
                $bom.html(html).prop('disabled', $bom.closest('form').find('input[name="bom_id"][type="hidden"]').length > 0);
                if ($bom.hasClass('select2-hidden-accessible')) {
                    $bom.trigger('change.select2');
                }
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load BOMs.');
            }
        });
    }

    $('#itemId').on('change', function () {
        selectedBomId = '';
        loadBoms($(this).val());
    });

    if ($('#itemId').val() && ! $form.find('input[name="bom_id"][type="hidden"]').length) {
        loadBoms($('#itemId').val());
    } else if ($('#itemId').val() && selectedBomId) {
        loadBoms($('#itemId').val());
    }

    $form.validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * POST a workflow action with optional confirmation payload.
     * @param {string} url
     * @param {jQuery} $btn
     * @param {string} title
     * @param {Object} extraData
     */
    function postAction(url, $btn, title, extraData) {
        Swal.fire({
            title: title,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            btnLoading($btn);
            $.ajax({
                url: url,
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, extraData || {}),
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    Notify.error(response.message || 'Action failed.');
                },
                complete: function () {
                    btnReset($btn);
                }
            });
        });
    }

    /**
     * Release work order; retry with confirm_non_critical when shortages are non-critical.
     * @param {string} url
     * @param {jQuery} $btn
     * @param {boolean} confirmNonCritical
     */
    function releaseWorkOrder(url, $btn, confirmNonCritical) {
        btnLoading($btn);
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                confirm_non_critical: confirmNonCritical ? 1 : 0
            },
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors && response.errors.confirm_non_critical) {
                    btnReset($btn);
                    Swal.fire({
                        title: 'Non-critical shortages',
                        text: response.errors.confirm_non_critical[0] || response.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Release anyway'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            releaseWorkOrder(url, $btn, true);
                        }
                    });
                    return;
                }
                Notify.error(response.message || 'Release failed.');
                btnReset($btn);
            },
            complete: function () {
                if (confirmNonCritical) {
                    btnReset($btn);
                }
            }
        });
    }

    $(document).on('click', '.btn-release-wo', function () {
        releaseWorkOrder($(this).data('url'), $(this), false);
    });

    $(document).on('click', '.btn-close-wo', function () {
        postAction($(this).data('url'), $(this), 'Close this work order and calculate cost variance?');
    });

    $('#issueMaterialsForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) {
            var $issueForm = $(form);
            var $btn = $issueForm.find('[type="submit"]');
            var items = [];

            $issueForm.find('tbody tr').each(function () {
                var qty = parseFloat($(this).find('.issue-qty').val());
                if (qty > 0) {
                    items.push({
                        work_order_component_id: $(this).find('input[name*="[work_order_component_id]"]').val(),
                        quantity: qty
                    });
                }
            });

            if (!items.length) {
                Notify.warning('Enter at least one issue quantity.');
                return;
            }

            btnLoading($btn);
            $.ajax({
                url: $issueForm.data('issue-url'),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    items: items
                },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    if (xhr.status === 422 && response.errors) {
                        Notify.warning(response.message || 'Please review issue quantities.');
                    } else {
                        Notify.error(response.message || 'Issue failed.');
                    }
                },
                complete: function () {
                    btnReset($btn);
                }
            });
        }
    });
});
