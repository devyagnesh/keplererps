$(function () {
    /**
     * Append a repeater row from a template.
     * @param {string} containerSelector
     * @param {string} templateSelector
     */
    function addRepeaterRow(containerSelector, templateSelector) {
        var index = $(containerSelector + ' .repeater-row').length;
        var html = $(templateSelector).html().replace(/__INDEX__/g, String(index));
        $(containerSelector).append(html);
    }

    $('#btnAddUomConversion').on('click', function () {
        addRepeaterRow('#uomConversionRows', '#tplUomConversion');
    });
    $('#btnAddWarehouseSetting').on('click', function () {
        addRepeaterRow('#warehouseSettingRows', '#tplWarehouseSetting');
    });
    $('#btnAddSubstitute').on('click', function () {
        addRepeaterRow('#substituteRows', '#tplSubstitute');
    });

    $(document).on('click', '.btn-remove-row', function () {
        $(this).closest('.repeater-row').remove();
    });

    $('#hsn_code_id').on('change', function () {
        var gst = $(this).find(':selected').data('gst');
        if (gst !== undefined && gst !== null && gst !== '') {
            $('#gst_rate').val(gst);
        }
    });

    $('#itemForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        rules: {
            item_name: { required: true, minlength: 2 },
            item_type: { required: true },
            category_id: { required: true },
            stock_uom_id: { required: true },
            hsn_code_id: { required: true },
            gst_rate: { required: true, number: true, min: 0 },
            tracking_type: { required: true }
        },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });
});
