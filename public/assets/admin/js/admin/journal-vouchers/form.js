/**
 * Journal voucher form: dynamic lines, live balance check, post and cancel actions.
 */
$(function () {
    /**
     * Recalculate the debit/credit footer totals and the balance hint.
     */
    function refreshTotals() {
        var debit = 0;
        var credit = 0;

        $('#lineRows .line-row').each(function () {
            debit += parseFloat($(this).find('.line-debit').val()) || 0;
            credit += parseFloat($(this).find('.line-credit').val()) || 0;
        });

        $('#totalDebit').text(debit.toFixed(2));
        $('#totalCredit').text(credit.toFixed(2));

        var difference = Math.abs(debit - credit);
        $('#balanceHint')
            .text(difference < 0.01 ? 'Balanced' : 'Out of balance by ' + difference.toFixed(2))
            .toggleClass('text-danger', difference >= 0.01)
            .toggleClass('text-muted', difference < 0.01);
    }

    /**
     * Append an empty line row cloned from the last row.
     */
    function addLine() {
        var $last = $('#lineRows .line-row').last();
        if (!$last.length) return;

        var index = $('#lineRows .line-row').length;
        var $row = $last.clone();

        $row.find('select, input').each(function () {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/lines\[\d+\]/, 'lines[' + index + ']'));
            }
            $(this).val('');
        });

        $('#lineRows').append($row);
        refreshTotals();
    }

    $('#btnAddLine').on('click', addLine);
    $(document).on('input', '.line-debit, .line-credit', refreshTotals);
    refreshTotals();

    $('#journalVoucherForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    /**
     * Confirm and fire a POST action on the current voucher.
     *
     * @param {jQuery} $btn Clicked button.
     * @param {string} title Confirmation title.
     * @param {string} confirmText Confirm button label.
     */
    function confirmAction($btn, title, confirmText) {
        Swal.fire({ title: title, icon: 'question', showCancelButton: true, confirmButtonText: confirmText }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: $btn.data('url'), type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.'); },
                complete: function () { btnReset($btn); }
            });
        });
    }

    $(document).on('click', '.btn-post-voucher', function () {
        confirmAction($(this), 'Post this voucher to the ledger?', 'Post');
    });

    $(document).on('click', '.btn-cancel-voucher', function () {
        confirmAction($(this), 'Cancel this voucher?', 'Cancel voucher');
    });
});
