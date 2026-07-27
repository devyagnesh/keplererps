/**
 * QC template form validation and AJAX submit.
 */
$(function () {
    var rowIndex = $('#parameterRows .parameter-row').length;

    function btnLoading($btn) {
        $btn.prop('disabled', true)
            .data('original-text', $btn.html())
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    }

    function btnReset($btn) {
        $btn.prop('disabled', false).html($btn.data('original-text'));
    }

    $('#btnAddParameter').on('click', function () {
        var html = $('#parameterRows .parameter-row').first().clone();
        html.find('input[type="text"], input[type="number"]').val('');
        html.find('input[type="checkbox"]').prop('checked', false);
        html.find('select').prop('selectedIndex', 0);
        html.find('[name]').each(function () {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/parameters\[\d+]/, 'parameters[' + rowIndex + ']'));
            }
        });
        $('#parameterRows').append(html);
        rowIndex++;
    });

    $(document).on('click', '.btn-remove-parameter', function () {
        if ($('#parameterRows .parameter-row').length <= 1) {
            Notify.warning('At least one parameter is required.');
            return;
        }
        $(this).closest('.parameter-row').remove();
    });

    $('#masterForm').validate({
        ignore: [],
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) {
            var $btn = $(form).find('[type="submit"]');
            var $form = $(form);
            btnLoading($btn);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON;
                    if (xhr.status === 422 && response && response.errors) {
                        $.each(response.errors, function (field, messages) {
                            var $field = $('[name="' + field + '"]');
                            $field.addClass('is-invalid');
                            $field.closest('.col-md-3, .col-md-4, .col-md-5, .col-12, .parameter-row')
                                .find('.invalid-feedback').remove();
                            $field.after('<span class="invalid-feedback d-block">' + messages[0] + '</span>');
                        });
                    }
                    Notify.error((response && response.message) || 'Unable to save template.');
                },
                complete: function () {
                    btnReset($btn);
                }
            });
        }
    });
});
