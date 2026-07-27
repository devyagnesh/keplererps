/**
 * Dispatch gate scanning with offline queue replay.
 */
$(function () {
    var OFFLINE_KEY = 'kepler_offline_scans';

    /**
     * @returns {Array<{code: string, confirm: boolean, scanned_at: string}>}
     */
    function loadOfflineQueue() {
        try {
            return JSON.parse(localStorage.getItem(OFFLINE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    /**
     * @param {Array} queue
     */
    function saveOfflineQueue(queue) {
        localStorage.setItem(OFFLINE_KEY, JSON.stringify(queue));
        $('#offlineQueueCount').text(queue.length);
    }

    /**
     * Render the scanned package and its challan progress.
     * @param {Object} data Scan response payload.
     */
    function renderResult(data) {
        var pkg = data.package;
        var lines = data.summary || [];
        var totals = { packages: 0, verified: 0 };

        $.each(lines, function (index, line) {
            totals.packages += line.package_count;
            totals.verified += line.verified_count;
        });

        $('#scanResult').removeClass('text-muted').html(
            '<div class="fs-18 fw-semibold">' + pkg.label_no + '</div>'
            + '<div class="mb-2">' + (pkg.item_code || '') + ' — ' + (pkg.item_name || '') + '</div>'
            + '<div>Packing unit: ' + (pkg.packing_unit || '—') + ' · Qty: ' + pkg.quantity + '</div>'
            + '<div>Batch: ' + (pkg.batch_no || '—') + ' · Warehouse: ' + (pkg.warehouse || '—') + '</div>'
            + '<div>Challan: ' + (pkg.challan_no || '—') + '</div>'
            + '<div class="mt-2"><span class="badge bg-primary-transparent">' + pkg.status_label + '</span></div>'
            + (totals.packages
                ? '<div class="mt-2 text-muted fs-12">Challan progress: ' + totals.verified + ' of ' + totals.packages + ' packages verified.</div>'
                : '')
        );

        $('#scanHistoryEmpty').remove();
        $('#scanHistory').prepend(
            '<tr><td>' + new Date().toLocaleTimeString() + '</td>'
            + '<td>' + pkg.label_no + '</td>'
            + '<td>' + (pkg.item_code || '') + '</td>'
            + '<td>' + pkg.quantity + '</td>'
            + '<td>' + pkg.status_label + '</td></tr>'
        );
    }

    /**
     * Flush queued scans when the browser is online again.
     */
    function flushOfflineQueue() {
        var queue = loadOfflineQueue();
        if (!queue.length || !navigator.onLine) {
            return;
        }

        $.ajax({
            url: '/admin/packages/replay-offline',
            type: 'POST',
            dataType: 'json',
            data: { scans: queue, device_id: localStorage.getItem('kepler_device_id') || null },
            success: function () {
                saveOfflineQueue([]);
                if (typeof Notify !== 'undefined') {
                    Notify.success('Offline scan queue synced.');
                }
            },
            error: function () {
                if (typeof Notify !== 'undefined') {
                    Notify.warning('Could not sync offline scans yet.');
                }
            }
        });
    }

    saveOfflineQueue(loadOfflineQueue());
    $(window).on('online', flushOfflineQueue);
    flushOfflineQueue();

    $('#scanForm').validate({
        rules: { code: { required: true } },
        messages: { code: { required: 'Scan or type a package code.' } },
        errorElement: 'span', errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        submitHandler: function (form) {
            if (!navigator.onLine) {
                var queue = loadOfflineQueue();
                queue.push({
                    code: $('#scanCode').val(),
                    confirm: true,
                    scanned_at: new Date().toISOString()
                });
                saveOfflineQueue(queue);
                if (typeof Notify !== 'undefined') {
                    Notify.info('Offline — scan queued locally.');
                }
                $('#scanCode').val('').trigger('focus');
                return;
            }

            submitAjaxForm(form, {
                onSuccess: function (response) {
                    renderResult(response.data);
                    $('#scanCode').val('').trigger('focus');
                },
                onError: function () {
                    $('#scanCode').trigger('select');
                }
            });
        }
    });
});
