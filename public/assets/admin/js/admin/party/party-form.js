$(function () {
    var contactIndex = $('#contactsWrapper .contact-row').length;

    $('#addContactBtn').on('click', function () {
        var html = ''
            + '<div class="row gy-2 contact-row mb-2" data-index="' + contactIndex + '">'
            + '<div class="col-md-3"><input type="text" class="form-control" name="contacts[' + contactIndex + '][name]" placeholder="Name"></div>'
            + '<div class="col-md-3"><input type="text" class="form-control" name="contacts[' + contactIndex + '][mobile]" placeholder="Mobile"></div>'
            + '<div class="col-md-3"><input type="email" class="form-control" name="contacts[' + contactIndex + '][email]" placeholder="Email"></div>'
            + '<div class="col-md-2"><input type="text" class="form-control" name="contacts[' + contactIndex + '][designation]" placeholder="Designation"></div>'
            + '<div class="col-md-1 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="contacts[' + contactIndex + '][whatsapp_opt_in]" value="1"></div></div>'
            + '</div>';
        $('#contactsWrapper').append(html);
        contactIndex += 1;
    });

    $('#gstin').on('blur', function () {
        var gstin = $(this).val();
        if (!gstin || gstin.length !== 15 || !window.gstinLookupUrl) {
            return;
        }
        $.getJSON(window.gstinLookupUrl, { gstin: gstin })
            .done(function (response) {
                if (!response.status || !response.data) {
                    return;
                }
                if (response.data.state_id) {
                    $('#billing_state_id').val(response.data.state_id).trigger('change');
                }
                var hint = 'State code ' + response.data.state_code;
                if (response.data.tax_type) {
                    hint += ' · Tax type: ' + (response.data.tax_type === 'igst' ? 'IGST' : 'CGST+SGST');
                }
                $('#gstinHint').text(hint);
            });
    });

    $('#partyForm').validate({
        rules: {
            party_name: { required: true, minlength: 2, maxlength: 150 },
            party_type: { required: true },
            gst_type: { required: true },
            billing_line1: { required: true },
            billing_city: { required: true },
            billing_state_id: { required: true },
            billing_pin_code: { required: true, digits: true, minlength: 6, maxlength: 6 },
            status: { required: true },
            'contacts[0][name]': { required: true },
            'contacts[0][mobile]': { required: true, minlength: 10, maxlength: 10 }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function (form) {
            submitAjaxForm(form);
        }
    });
});
