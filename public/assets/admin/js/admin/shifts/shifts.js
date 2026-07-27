/**
 * Shift master: inline create/edit form plus delete.
 */
$(function () {
    var $form = $('#shiftForm');
    var storeUrl = $form.attr('action');

    /**
     * Reset the form back to create mode.
     */
    function resetForm() {
        $form[0].reset();
        $form.attr('action', storeUrl);
        $form.find('input[name="_method"]').val('POST');
        $form.find('.is-invalid').removeClass('is-invalid');
        $('#shiftFormTitle').text('Add Shift');
        $('#btnCancelShiftEdit').addClass('d-none');
    }

    $(document).on('click', '.btn-edit-shift', function () {
        var shift = $(this).data('shift');

        $form.attr('action', $(this).data('url'));
        $form.find('input[name="_method"]').val('PUT');
        $form.find('[name="code"]').val(shift.code);
        $form.find('[name="name"]').val(shift.name);
        $form.find('[name="start_time"]').val(shift.start_time);
        $form.find('[name="end_time"]').val(shift.end_time);
        $form.find('[name="break_minutes"]').val(shift.break_minutes);
        $form.find('[name="is_active"]').prop('checked', !!shift.is_active);
        $('#shiftFormTitle').text('Edit Shift ' + shift.code);
        $('#btnCancelShiftEdit').removeClass('d-none');
    });

    $('#btnCancelShiftEdit').on('click', resetForm);

    $form.validate({
        rules: {
            code: { required: true, maxlength: 20 },
            name: { required: true, minlength: 2 },
            start_time: { required: true },
            end_time: { required: true }
        },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $(document).on('click', '.btn-delete-shift', function () {
        var url = $(this).data('url');
        Swal.fire({ title: 'Delete this shift?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'POST', data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) { Notify.success(response.message); window.location.reload(); },
                error: function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.'); }
            });
        });
    });
});
