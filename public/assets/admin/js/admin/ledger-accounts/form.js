/**
 * Ledger account form validation and AJAX submit.
 */
$(function () {
    $('#masterForm').validate({
        rules: {
            code: { required: true, maxlength: 20 },
            name: { required: true, minlength: 2, maxlength: 150 }
        },
        messages: {
            code: { required: 'Account code is required.' },
            name: { required: 'Account name is required.' }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
