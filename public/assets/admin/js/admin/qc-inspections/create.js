/**
 * Manual QC inspection form: batch narrowing by item and AJAX submit.
 */
$(function () {
    var $item = $('#inspectionItem');
    var $batch = $('#inspectionBatch');
    var allBatchOptions = $batch.find('option[data-item]').clone();

    /**
     * Limit the batch list to batches belonging to the selected item.
     */
    function filterBatches() {
        var itemId = $item.val();
        var selected = $batch.val();

        $batch.find('option[data-item]').remove();
        allBatchOptions.each(function () {
            if (String($(this).data('item')) === String(itemId)) {
                $batch.append($(this).clone());
            }
        });

        $batch.val($batch.find('option[value="' + selected + '"]').length ? selected : '');
    }

    $item.on('change', filterBatches);
    filterBatches();

    $('#inspectionCreateForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) { submitAjaxForm(form); }
    });
});
