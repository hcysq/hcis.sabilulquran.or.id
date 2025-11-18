jQuery(document).ready(function($) {
    const noticeContainer = $('#hcis-admin-notice');

    function showNotice(message, isError = false) {
        noticeContainer
            .text(message)
            .removeClass('notice-success notice-error')
            .addClass(isError ? 'notice-error' : 'notice-success')
            .show();
    }

    $('#hcis-test-connection').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        noticeContainer.hide();

        $.post(hcis_admin.ajax_url, {
            action: 'hcis_test_connection',
            _ajax_nonce: hcis_admin.nonce
        }, function(response) {
            if (response.success) {
                showNotice('Connection successful: ' + response.data.message);
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
});
