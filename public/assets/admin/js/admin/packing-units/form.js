/**
 * Packing unit form validation and submission.
 */
$(function () {
    $('#packingUnitForm').validate({
        rules: {
            code: { required: true, maxlength: 30 },
            name: { required: true, minlength: 2 },
            quantity: { required: true, number: true, min: 0.0001 },
            uom_id: { required: true }
        },
        messages: {
            code: { required: 'Packing unit code is required.' },
            quantity: { min: 'Quantity per unit must be greater than zero.' }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
