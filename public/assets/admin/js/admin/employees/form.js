/**
 * Employee form validation and submission.
 */
$(function () {
    $('#employeeForm').validate({
        rules: {
            full_name: { required: true, minlength: 2 },
            date_of_joining: { required: true },
            status: { required: true },
            monthly_gross: { required: true, number: true, min: 0 },
            basic_percent: { required: true, number: true, min: 1, max: 100 },
            email: { email: true }
        },
        messages: {
            full_name: { required: 'Employee name is required.' },
            basic_percent: { max: 'Basic pay cannot exceed the gross.' }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
