/**
 * QC inspection readings save / complete.
 */
$(function () {
    function btnLoading($btn) {
        $btn.prop('disabled', true)
            .data('original-text', $btn.html())
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    }

    function btnReset($btn) {
        $btn.prop('disabled', false).html($btn.data('original-text'));
    }

    function submitInspection(url, $btn, asComplete) {
        var $form = $('#inspectionForm');
        btnLoading($btn);

        var data = $form.serialize();
        if (asComplete) {
            data = data.replace(/_method=PUT/i, '_method=POST');
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            },
            error: function (xhr) {
                var response = xhr.responseJSON;
                Notify.error((response && response.message) || 'Unable to save inspection.');
            },
            complete: function () {
                btnReset($btn);
            }
        });
    }

    $('#inspectionForm').on('submit', function (e) {
        e.preventDefault();
        submitInspection($(this).attr('action'), $('#btnSaveInspection'), false);
    });

    $('#btnCompleteInspection').on('click', function () {
        var $btn = $(this);
        var url = $('#inspectionForm').data('complete-url');
        Swal.fire({
            title: 'Complete this inspection?',
            text: 'Stock will move based on disposition.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Complete'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            submitInspection(url, $btn, true);
        });
    });
});
