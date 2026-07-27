/**
 * Production entry form: rejection fields, work order summary, post action.
 */
$(function () {
    var $form = $('#productionEntryForm');

    /**
     * Toggle rejection detail fields when rejected quantity is greater than zero.
     */
    function syncRejectionFields() {
        var rejected = parseFloat($('#rejectedQuantity').val()) || 0;
        $('#rejectionFields').toggleClass('d-none', rejected <= 0);
        syncDowngradeField();
    }

    /**
     * Show downgrade item selector only for downgrade disposition.
     */
    function syncDowngradeField() {
        var rejected = parseFloat($('#rejectedQuantity').val()) || 0;
        var isDowngrade = $('#rejectionDisposition').val() === 'downgrade';
        $('#downgradeItemWrap').toggleClass('d-none', rejected <= 0 || !isDowngrade);
    }

    /**
     * Update work order summary text from the selected option.
     */
    function syncWorkOrderSummary() {
        var $opt = $('#workOrderId').find(':selected');
        if (!$opt.val()) {
            $('#workOrderSummary').text('');
            return;
        }
        $('#workOrderSummary').text(
            ($opt.data('item') || '') +
            ' · Planned ' + ($opt.data('planned') || '0') +
            ' · Good ' + ($opt.data('good') || '0')
        );
    }

    $('#rejectedQuantity').on('input change', syncRejectionFields);
    $('#rejectionDisposition').on('change', syncDowngradeField);
    $('#workOrderId').on('change', syncWorkOrderSummary);

    syncRejectionFields();
    syncWorkOrderSummary();

    if ($form.length && $form.find('[type="submit"]').length) {
        $form.validate({
            errorElement: 'span',
            errorClass: 'invalid-feedback d-block',
            highlight: function (el) { $(el).addClass('is-invalid'); },
            unhighlight: function (el) { $(el).removeClass('is-invalid'); },
            submitHandler: function (form) { submitAjaxForm(form); }
        });
    }

    $(document).on('click', '.btn-post-entry', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({
            title: 'Post production entry to stock?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Post'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            btnLoading($btn);
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Post failed.');
                },
                complete: function () {
                    btnReset($btn);
                }
            });
        });
    });
});
