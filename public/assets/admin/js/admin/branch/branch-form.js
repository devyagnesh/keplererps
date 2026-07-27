$(function () {
    $('#branchForm').validate({
        rules: {
            code: { required: true, minlength: 2, maxlength: 30 },
            name: { required: true, minlength: 2, maxlength: 150 },
            email: { email: true },
            pin_code: { digits: true, minlength: 6, maxlength: 6 }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });
});
