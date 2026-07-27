/**
 * Packing workbench: pick a challan line, choose a packing unit and print labels.
 */
$(function () {
    $(document).on('click', '.btn-open-pack', function () {
        var $btn = $(this);

        $('#packLineId').val($btn.data('line-id'));
        $('#packItemLabel').text($btn.data('item'));
        $('#packOpenQty').text($btn.data('open-qty'));
        $('#packCount').val(1);
        $('#packUnit').val('');
        $('#packQtyPerPackage').val('');

        new bootstrap.Modal(document.getElementById('packModal')).show();
    });

    /** Prefill the per-package quantity from the selected packing unit. */
    $(document).on('change', '#packUnit', function () {
        var baseQty = $(this).find('option:selected').data('base-qty');
        if (baseQty) {
            $('#packQtyPerPackage').val(baseQty);
        }
    });

    $('#packForm').validate({
        rules: {
            packing_unit_id: { required: true },
            package_count: { required: true, digits: true, min: 1, max: 500 }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) {
            submitAjaxForm(form, {
                onSuccess: function (response) {
                    if (response.redirect) {
                        window.open(response.redirect, '_blank');
                    }
                    window.location.reload();
                }
            });
        }
    });
});
