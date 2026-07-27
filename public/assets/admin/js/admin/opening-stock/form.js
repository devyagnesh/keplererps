$(function () {
    $('#btnAddLine').on('click', function () {
        var index = $('#lineRows .line-row').length;
        $('#lineRows').append($('#tplLine').html().replace(/__INDEX__/g, String(index)));
    });
    $(document).on('click', '.btn-remove-line', function () {
        $(this).closest('.line-row').remove();
    });
    $(document).on('change', 'select[name*="[item_id]"]', function () {
        var rate = $(this).find(':selected').data('rate');
        if (rate !== undefined) {
            $(this).closest('.line-row').find('input[name*="[rate]"]').val(rate);
        }
    });
    $('#inventoryDocForm').validate({
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
    $(document).on('click', '.btn-post-doc', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({ title: 'Post to stock ledger?', icon: 'question', showCancelButton: true, confirmButtonText: 'Post' }).then(function (result) {
            if (!result.isConfirmed) return;
            btnLoading($btn);
            $.ajax({
                url: url, type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Posting failed.');
                },
                complete: function () { btnReset($btn); }
            });
        });
    });
});
