$(function () {
    $('#masterForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        rules: {
            code: { required: true, minlength: 4, maxlength: 8, digits: true },
            code_type: { required: true },
            description: { required: true },
            default_gst_rate: { required: true, number: true, min: 0 }
        },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });
});
