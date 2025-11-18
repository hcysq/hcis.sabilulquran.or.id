jQuery(document).ready(function($) {
    const noticeContainer = $('#hcis-admin-notice');

    function showNotice(message, isError = false) {
        noticeContainer
            .html(message) // Use .html to allow for formatted messages
            .removeClass('notice-success notice-error')
            .addClass(isError ? 'notice-error' : 'notice-success')
            .show();
    }

    $('#hcis-test-connection').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        noticeContainer.hide();

        const nipToTest = $('#hcis-test-nip').val(); // Get NIP value

        $.post(hcis_admin.ajax_url, {
            action: 'hcis_test_connection',
            _ajax_nonce: hcis_admin.nonce,
            nip: nipToTest // Pass NIP
        }, function(response) {
            if (response.success) {
                let message = 'Connection successful: ' + response.data.connection_status;
                if (response.data.user_data_for_nip) {
                    message += '<br><br><strong>User Data for NIP:</strong><pre>' + JSON.stringify(response.data.user_data_for_nip, null, 2) + '</pre>';
                }
                showNotice(message);
            } else {
                showNotice('Connection failed: ' + response.data.message, true);
            }
        }).fail(function() {
            showNotice('An unexpected error occurred.', true);
        }).always(function() {
            button.prop('disabled', false);
        });
    });

    $('#hcis-clear-cache').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        noticeContainer.hide();

        $.post(hcis_admin.ajax_url, {
            action: 'hcis_clear_cache',
            _ajax_nonce: hcis_admin.nonce
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message);
            } else {
                showNotice('Failed to clear cache: ' + response.data.message, true);
            }
        }).fail(function() {
            showNotice('An unexpected error occurred.', true);
        }).always(function() {
            button.prop('disabled', false);
        });
    });

    $('#hcis-test-wa-connection').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        noticeContainer.hide();

        $.post(hcis_admin.ajax_url, {
            action: 'hcis_test_wa_connection',
            _ajax_nonce: hcis_admin.nonce
        }, function(response) {
            const destination = (response.data && response.data.destination) || hcis_admin.wa_test.target_number;

            if (response.success) {
                const template = hcis_admin.wa_test.success_text || '';
                const message = template ? template.replace('%s', destination) : response.data.message;
                showNotice(message || response.data.message);
            } else {
                const errorText = (response.data && response.data.message) || hcis_admin.wa_test.error_text;
                showNotice(errorText, true);
            }
        }).fail(function(xhr) {
            const failMessage = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message)
                ? xhr.responseJSON.data.message
                : hcis_admin.wa_test.error_text;
            showNotice(failMessage, true);
        }).always(function() {
            button.prop('disabled', false);
        });
    });
});
