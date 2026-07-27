/**
 * Maintenance order form, issue parts, and close.
 */
$(function () {
    var partIndex = $('#partRows .part-row').length;

    function btnLoading($btn) {
        $btn.prop('disabled', true).data('original-text', $btn.html())
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    }
    function btnReset($btn) { $btn.prop('disabled', false).html($btn.data('original-text')); }

    $('#btnAddPart').on('click', function () {
        var $row = $('#partRows .part-row').first().clone();
        $row.find('select').prop('selectedIndex', 0).prop('disabled', false);
        $row.find('input').val('').prop('disabled', false);
        $row.find('.badge').remove();
        if ($row.find('.btn-remove-part').length === 0) {
            $row.find('.col-md-2').last().html('<button type="button" class="btn btn-sm btn-danger-light btn-remove-part">×</button>');
        }
        $row.find('[name]').each(function () {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/parts\[\d+]/, 'parts[' + partIndex + ']'));
            }
        });
        $('#partRows').append($row);
        partIndex++;
    });

    $(document).on('click', '.btn-remove-part', function () {
        if ($('#partRows .part-row').length <= 1) {
            Notify.warning('Keep at least one spare-part row (leave blank if unused).');
            return;
        }
        $(this).closest('.part-row').remove();
    });

    $('#masterForm').on('submit', function (e) {
        e.preventDefault();
        if ($('#masterForm').data('editable') !== 1 && $('#masterForm').data('editable') !== '1') {
            return;
        }
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        btnLoading($btn);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                Notify.success(response.message);
                if (response.redirect) { window.location.href = response.redirect; }
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save.');
            },
            complete: function () { btnReset($btn); }
        });
    });

    $('#btnIssueParts').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        Swal.fire({ title: 'Issue spare parts from stock?', icon: 'question', showCancelButton: true, confirmButtonText: 'Issue' })
            .then(function (result) {
                if (!result.isConfirmed) { return; }
                btnLoading($btn);
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        Notify.success(response.message);
                        if (response.redirect) { window.location.href = response.redirect; }
                    },
                    error: function (xhr) {
                        Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Issue failed.');
                    },
                    complete: function () { btnReset($btn); }
                });
            });
    });

    $('#btnCloseOrder').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        Swal.fire({
            title: 'Close maintenance order?',
            text: 'Unissued parts will be issued and the asset returned to Active.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Close'
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            btnLoading($btn);
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    action_taken: $('[name="action_taken"]').val(),
                    remarks: $('[name="remarks"]').val()
                },
                success: function (response) {
                    Notify.success(response.message);
                    if (response.redirect) { window.location.href = response.redirect; }
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Close failed.');
                },
                complete: function () { btnReset($btn); }
            });
        });
    });
});
