/**
 * Notification rule catalogue: inline create/edit, toggle, delete.
 */
$(function () {
    var $form = $('#notificationRuleForm');
    if (!$form.length) {
        return;
    }

    var storeUrl = $form.attr('action');
    var lookups = window.NotificationRuleLookups || { roles: [], permissions: [] };

    /**
     * Rebuild the audience select for the current recipient type.
     *
     * @param {string} type
     * @param {string|null} selected
     */
    function fillRecipientOptions(type, selected) {
        var $select = $('#recipientValue');
        var options = type === 'permission' ? lookups.permissions : lookups.roles;
        var html = '<option value="">Select…</option>';

        $.each(options, function (_, item) {
            var value = typeof item === 'string' ? item : item.value;
            var label = typeof item === 'string' ? item : item.label;
            html += '<option value="' + value + '"' + (selected === value ? ' selected' : '') + '>' + label + '</option>';
        });

        $select.html(html);
    }

    /**
     * Reset the form back to create mode.
     */
    function resetForm() {
        $form[0].reset();
        $form.attr('action', storeUrl);
        $form.find('input[name="_method"]').val('POST');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[name="code"]').prop('readonly', false);
        $form.find('[name="event"], [name="channel"]').prop('disabled', false);
        $('#ruleFormTitle').text('Add Rule');
        $('#btnCancelRuleEdit').addClass('d-none');
        fillRecipientOptions($('#recipientType').val());
    }

    fillRecipientOptions($('#recipientType').val());

    $('#recipientType').on('change', function () {
        fillRecipientOptions($(this).val());
    });

    $(document).on('click', '.btn-edit-rule', function () {
        var rule = $(this).data('rule');

        $form.attr('action', $(this).data('url'));
        $form.find('input[name="_method"]').val('PUT');
        $form.find('[name="name"]').val(rule.name);
        $form.find('[name="code"]').val(rule.code).prop('readonly', !!rule.is_system);
        $form.find('[name="sort_order"]').val(rule.sort_order);
        $form.find('[name="event"]').val(rule.event).prop('disabled', !!rule.is_system);
        $form.find('[name="channel"]').val(rule.channel).prop('disabled', !!rule.is_system);
        $form.find('[name="recipient_type"]').val(rule.recipient_type);
        fillRecipientOptions(rule.recipient_type, rule.recipient_value);
        $form.find('[name="subject_template"]').val(rule.subject_template);
        $form.find('[name="body_template"]').val(rule.body_template);
        $form.find('[name="is_active"]').prop('checked', !!rule.is_active);
        $('#ruleFormTitle').text('Edit Rule ' + rule.code);
        $('#btnCancelRuleEdit').removeClass('d-none');
    });

    $('#btnCancelRuleEdit').on('click', resetForm);

    $form.validate({
        rules: {
            name: { required: true, minlength: 2 },
            event: { required: true },
            channel: { required: true },
            recipient_type: { required: true },
            recipient_value: { required: true },
            subject_template: { required: true },
            body_template: { required: true }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) {
            // Disabled fields are omitted from serialize — re-enable briefly for system rules.
            $form.find('[name="event"], [name="channel"]').prop('disabled', false);
            submitAjaxForm(form);
        }
    });

    $(document).on('click', '.btn-toggle-rule', function () {
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Notify.success(response.message);
                window.location.reload();
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Toggle failed.');
            }
        });
    });

    $(document).on('click', '.btn-delete-rule', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete this rule?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    window.location.reload();
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.');
                }
            });
        });
    });
});
