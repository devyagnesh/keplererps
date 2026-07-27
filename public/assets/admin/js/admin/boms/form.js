/**
 * BOM create/edit form: line editors, explode, new version.
 */
$(function () {
    $('#btnAddComponent').on('click', function () {
        var index = $('#componentRows .component-row').length;
        $('#componentRows').append($('#tplComponent').html().replace(/__INDEX__/g, String(index)));
    });

    $('#btnAddOperation').on('click', function () {
        var index = $('#operationRows .operation-row').length;
        $('#operationRows').append($('#tplOperation').html().replace(/__INDEX__/g, String(index)));
    });

    $(document).on('click', '.btn-remove-component', function () {
        $(this).closest('.component-row').remove();
    });

    $(document).on('click', '.btn-remove-operation', function () {
        $(this).closest('.operation-row').remove();
    });

    $(document).on('change', '.component-item', function () {
        var uom = $(this).find(':selected').data('uom');
        if (uom) {
            $(this).closest('.component-row').find('.component-uom').val(String(uom));
        }
    });

    $(document).on('change', '.work-centre', function () {
        var $opt = $(this).find(':selected');
        var $row = $(this).closest('.operation-row');
        if ($opt.data('machine') !== undefined) {
            $row.find('.machine-rate').val($opt.data('machine'));
        }
        if ($opt.data('labour') !== undefined) {
            $row.find('.labour-rate').val($opt.data('labour'));
        }
    });

    $('#bomForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });

    $('#btnNewVersion').on('click', function () {
        var url = $(this).data('url');
        var $btn = $(this);
        Swal.fire({
            title: 'Create a new version?',
            text: 'The current active version will be closed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create'
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
                    }
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not create version.');
                },
                complete: function () { btnReset($btn); }
            });
        });
    });

    $('#btnExplode').on('click', function () {
        if (!window.bomExplodeUrl) {
            return;
        }
        var qty = $('#orderQuantity').val();
        var $btn = $(this);
        btnLoading($btn);
        $.ajax({
            url: window.bomExplodeUrl,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                order_quantity: qty
            },
            success: function (response) {
                Notify.success(response.message);
                var html = '<table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Required</th><th>UOM</th><th>Waste %</th></tr></thead><tbody>';
                $.each(response.data || [], function (_, row) {
                    html += '<tr><td>' + row.item_code + ' — ' + row.item_name + '</td><td>' + row.required_quantity + '</td><td>' + (row.uom || '') + '</td><td>' + row.wastage_percent + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#explodeResult').html(html);
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Calculation failed.');
            },
            complete: function () { btnReset($btn); }
        });
    });
});
