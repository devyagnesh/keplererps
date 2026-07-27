$(function () {
    $('#userForm').validate({
        rules: {
            name: { required: true, minlength: 2 },
            username: { required: true, minlength: 4 },
            email: { required: true, email: true },
            mobile: { required: true, minlength: 10 },
            branch_id: { required: true },
            'role_ids[]': { required: true }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
