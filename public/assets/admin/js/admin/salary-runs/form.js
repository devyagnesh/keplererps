/**
 * Salary run create form and posting actions.
 */
$(function () {
    $('#salaryRunForm').validate({
        rules: {
            period_month: { required: true },
            period_year: { required: true, digits: true, min: 2000, max: 2100 },
            payment_date: { required: true }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Run a salary-run action endpoint and reload on success.
     * @param {string} url Endpoint URL.
     * @param {string} confirmText Confirmation dialog title.
     */
    function runAction(url, confirmText) {
        Swal.fire({ title: confirmText, icon: 'question', showCancelButton: true, confirmButtonText: 'Continue' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); window.location.reload(); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'The action could not be completed.'); }
            });
        });
    }

    $('#btnRecalculate').on('click', function () {
        runAction($(this).data('url'), 'Rebuild slips from current attendance?');
    });

    $('#btnPostRun').on('click', function () {
        runAction($(this).data('url'), 'Post this salary run to the ledger?');
    });

    $('#btnCancelRun').on('click', function () {
        runAction($(this).data('url'), 'Cancel this salary run?');
    });
});
