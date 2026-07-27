/**
 * Opportunity form: save, stage transitions, quotation link and follow-ups.
 */
$(function () {
    $('#opportunityForm').validate({
        rules: { title: { required: true, minlength: 3 } },
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

    $('#attachQuotationForm').validate({
        rules: { quotation_id: { required: true } },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Post a stage change for the current opportunity.
     *
     * @param {jQuery} $btn Clicked button.
     * @param {string} stage Target OpportunityStage value.
     * @param {string|null} lostReason Reason when marking the opportunity lost.
     */
    function postStage($btn, stage, lostReason) {
        btnLoading($btn);

        $.ajax({
            url: $btn.data('url'),
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                stage: stage,
                lost_reason: lostReason || ''
            },
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) window.location.href = response.redirect;
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not move the opportunity.');
            },
            complete: function () { btnReset($btn); }
        });
    }

    $(document).on('click', '.btn-opportunity-stage', function () {
        var $btn = $(this);
        Swal.fire({ title: 'Move this opportunity?', icon: 'question', showCancelButton: true, confirmButtonText: 'Move' })
            .then(function (result) {
                if (result.isConfirmed) postStage($btn, $btn.data('stage'), null);
            });
    });

    $(document).on('click', '.btn-opportunity-lost', function () {
        var $btn = $(this);
        Swal.fire({
            title: 'Mark this opportunity lost?',
            input: 'text',
            inputLabel: 'Reason',
            showCancelButton: true,
            confirmButtonText: 'Mark Lost',
            inputValidator: function (value) {
                return value && value.trim() ? null : 'A reason is required.';
            }
        }).then(function (result) {
            if (result.isConfirmed) postStage($btn, 'lost', result.value);
        });
    });
});
