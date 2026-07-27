/**
 * Lead form: save, status transitions, follow-up log and conversion.
 */
$(function () {
    $('#leadForm').validate({
        rules: {
            company_name: { required: true, minlength: 2 },
            contact_person: { required: true, minlength: 2 },
            mobile: { required: true, minlength: 10 },
            email: { email: true }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $('#followUpForm').validate({
        rules: { summary: { required: true, minlength: 3 } },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $('#convertForm').validate({
        rules: {
            billing_line1: { required: true },
            billing_city: { required: true },
            billing_state_id: { required: true },
            billing_pin_code: { required: true, minlength: 6, maxlength: 6 }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Post a status change for the current lead.
     *
     * @param {jQuery} $btn Clicked button.
     * @param {string} status Target LeadStatus value.
     * @param {string|null} lostReason Reason when marking the lead lost.
     */
    function postStatus($btn, status, lostReason) {
        btnLoading($btn);

        $.ajax({
            url: $btn.data('url'),
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: status,
                lost_reason: lostReason || ''
            },
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) window.location.href = response.redirect;
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not update the lead.');
            },
            complete: function () { btnReset($btn); }
        });
    }

    $(document).on('click', '.btn-lead-status', function () {
        var $btn = $(this);
        Swal.fire({ title: 'Update lead status?', icon: 'question', showCancelButton: true, confirmButtonText: 'Update' })
            .then(function (result) {
                if (result.isConfirmed) postStatus($btn, $btn.data('status'), null);
            });
    });

    $(document).on('click', '.btn-lead-lost', function () {
        var $btn = $(this);
        Swal.fire({
            title: 'Mark this lead lost?',
            input: 'text',
            inputLabel: 'Reason',
            inputPlaceholder: 'Why was the lead lost?',
            showCancelButton: true,
            confirmButtonText: 'Mark Lost',
            inputValidator: function (value) {
                return value && value.trim() ? null : 'A reason is required.';
            }
        }).then(function (result) {
            if (result.isConfirmed) postStatus($btn, 'lost', result.value);
        });
    });
});
