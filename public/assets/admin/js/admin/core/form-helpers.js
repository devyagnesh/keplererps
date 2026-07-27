/**
 * Disable submit button and show loading spinner.
 * @param {jQuery} $btn
 */
function btnLoading($btn) {
    $btn.prop('disabled', true)
        .data('original-text', $btn.html())
        .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
}

/**
 * Restore button to its original state.
 * @param {jQuery} $btn
 */
function btnReset($btn) {
    $btn.prop('disabled', false).html($btn.data('original-text'));
}

/**
 * Apply Laravel 422 validation errors to a form.
 * @param {jQuery} $form
 * @param {Object} errors
 */
function applyValidationErrors($form, errors) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback.server-error').remove();

    $.each(errors, function (field, messages) {
        var name = field.replace(/\.(\d+)\./g, '[$1][').replace(/\.(\w+)/g, '[$1]');
        if (field.indexOf('.') !== -1 && name.indexOf('[') === -1) {
            name = field;
        }
        var $field = $form.find('[name="' + field + '"], [name="' + name + '"]').first();
        if (!$field.length) {
            $field = $form.find('[name="' + field + '[]"]').first();
        }
        if ($field.length) {
            $field.addClass('is-invalid');
            $field.after('<span class="invalid-feedback d-block server-error">' + messages[0] + '</span>');
        }
    });
}

/**
 * Standard AJAX form submit used by module forms.
 * @param {HTMLFormElement} form
 * @param {Object} options
 */
function submitAjaxForm(form, options) {
    var $form = $(form);
    var $btn = $form.find('[type="submit"]');
    var method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();
    var data = new FormData(form);

    btnLoading($btn);

    $.ajax({
        url: $form.attr('action'),
        type: method === 'GET' ? 'GET' : 'POST',
        data: data,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            Notify.success(response.message || 'Saved successfully.');
            if (options && typeof options.onSuccess === 'function') {
                options.onSuccess(response);
            } else if (response.redirect) {
                window.location.href = response.redirect;
            }
        },
        error: function (xhr) {
            var response = xhr.responseJSON || {};
            if (xhr.status === 422 && response.errors) {
                applyValidationErrors($form, response.errors);
                Notify.warning(response.message || 'Please review the highlighted fields.');
            } else if (xhr.status === 422 && response.requires_confirmation && options && options.onConfirmRequired) {
                options.onConfirmRequired(response);
            } else {
                Notify.error(response.message || 'An unexpected error occurred.');
            }
            if (options && typeof options.onError === 'function') {
                options.onError(xhr, response);
            }
        },
        complete: function () {
            btnReset($btn);
        }
    });
}

/**
 * Show available-to-promise (free) stock beside sales line item pickers (US-M03-04).
 * Does nothing when the form carries no data-availability-url, which is the case for
 * users without the stock_balance.view permission.
 * @param {jQuery} $form Form element carrying data-availability-url.
 * @param {string} itemSelector Selector matching the line item <select> elements.
 * @param {string} displaySelector Selector matching the per-line display element.
 */
function bindAvailabilityLookup($form, itemSelector, displaySelector) {
    var url = $form.data('availability-url');
    if (!url) {
        return;
    }

    /**
     * Fetch and render availability for a single line.
     * @param {jQuery} $select
     */
    function refresh($select) {
        var $display = $select.closest('.line-row').find(displaySelector);
        var itemId = $select.val();

        if (!itemId) {
            $display.text('');
            return;
        }

        $display.text('Checking stock…');

        $.ajax({
            url: url,
            type: 'GET',
            data: {
                item_id: itemId,
                warehouse_id: $form.find('[name="warehouse_id"]').first().val() || ''
            },
            dataType: 'json',
            success: function (response) {
                var atp = response.data || {};
                $display.text(
                    'Free ' + Number(atp.free_qty || 0).toFixed(3)
                    + ' · committed ' + Number(atp.committed_qty || 0).toFixed(3)
                    + ' · on order ' + Number(atp.on_order_qty || 0).toFixed(3)
                );
            },
            error: function () {
                $display.text('');
            }
        });
    }

    $form.on('change', itemSelector, function () {
        refresh($(this));
    });

    $form.on('change', '[name="warehouse_id"]', function () {
        $form.find(itemSelector).each(function () {
            refresh($(this));
        });
    });

    $form.find(itemSelector).each(function () {
        if ($(this).val()) {
            refresh($(this));
        }
    });
}

$(function () {
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }
});
