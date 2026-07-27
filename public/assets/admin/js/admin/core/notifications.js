/**
 * Central notification helper using Toastify.
 */
const Notify = (function () {
    /**
     * @param {string} message
     * @param {string} backgroundColor
     */
    function _show(message, backgroundColor) {
        Toastify({
            text: message,
            duration: 4000,
            gravity: 'bottom',
            position: 'right',
            stopOnFocus: true,
            style: { background: backgroundColor, borderRadius: '6px', fontSize: '14px' },
            onClick: function () {}
        }).showToast();
    }

    return {
        success: function (msg) { _show(msg, '#28a745'); },
        error: function (msg) { _show(msg, '#dc3545'); },
        warning: function (msg) { _show(msg, '#ffc107'); },
        info: function (msg) { _show(msg, '#17a2b8'); }
    };
})();
