/**
 * Render a QR code into every package label card on the print sheet.
 */
$(function () {
    if (typeof QRCode === 'undefined') {
        return;
    }

    $('.label-qr[data-qr-payload]').each(function () {
        var payload = $(this).attr('data-qr-payload');
        if (!payload) {
            return;
        }

        $(this).empty();
        new QRCode(this, {
            text: payload,
            width: 96,
            height: 96,
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});
