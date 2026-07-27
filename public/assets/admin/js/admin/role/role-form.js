$(function () {
    $('.select-module').on('change', function () {
        var module = $(this).data('module');
        $('.perm-' + module).prop('checked', $(this).is(':checked'));
    });

    $('#roleForm').validate({
        rules: {
            name: { required: true, maxlength: 100 },
            slug: { required: true, maxlength: 100 }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
