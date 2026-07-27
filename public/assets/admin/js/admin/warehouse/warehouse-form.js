$(function () {
    $('#warehouseForm').validate({
        rules: {
            branch_id: { required: true },
            level: { required: true },
            code: { required: true, minlength: 2, maxlength: 30 },
            name: { required: true, minlength: 2, maxlength: 150 }
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
