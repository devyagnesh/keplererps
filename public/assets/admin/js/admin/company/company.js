$(function () {
    $('#companyForm').validate({
        rules: {
            legal_name: { required: true, minlength: 2, maxlength: 150 },
            pan: { required: true, minlength: 10, maxlength: 10 },
            registered_address: { required: true, minlength: 10, maxlength: 250 },
            state_id: { required: true },
            pin_code: { required: true, digits: true, minlength: 6, maxlength: 6 },
            phone: { required: true, minlength: 10, maxlength: 15 },
            email: { required: true, email: true, maxlength: 100 },
            fy_start_month: { required: true },
            fy_start_day: { required: true, min: 1, max: 31 },
            base_currency: { required: true, maxlength: 3 },
            amount_decimals: { required: true, min: 0, max: 4 },
            quantity_decimals: { required: true, min: 0, max: 4 }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) {
            submitAjaxForm(form, {
                onConfirmRequired: function () {
                    Swal.fire({
                        title: 'Confirm GSTIN change?',
                        text: 'Transactions already exist. Changing GSTIN will be logged.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, change GSTIN'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            $('#confirm_gstin_change').val('1');
                            submitAjaxForm(form);
                        }
                    });
                }
            });
        }
    });
});
