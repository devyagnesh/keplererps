$(function () {
    $('#loginForm').validate({
        rules: {
            login: { required: true, maxlength: 100 },
            password: { required: true, minlength: 8 }
        },
        messages: {
            login: { required: 'Email, username or mobile is required.' },
            password: { required: 'Password is required.', minlength: 'Minimum 8 characters.' }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) {
            submitAjaxForm(form, {
                onSuccess: function (response) {
                    window.location.href = response.data.redirect;
                }
            });
        }
    });
});
