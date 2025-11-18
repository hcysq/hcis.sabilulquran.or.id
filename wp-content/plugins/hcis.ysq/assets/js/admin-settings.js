jQuery(document).ready(function($) {
    const noticeContainer = $('#hcis-admin-notice');

    function showNotice(content, isError = false) {
        const contents = Array.isArray(content) ? content : [content];

        noticeContainer
            .removeClass('notice-success notice-error')
            .addClass(isError ? 'notice-error' : 'notice-success')
            .hide()
            .empty();

        contents.forEach(function(item) {
            if (!item && item !== 0) {
                return;
            }

            if (item instanceof jQuery) {
                noticeContainer.append(item);
            } else if (item instanceof window.Element) {
                noticeContainer.append(item);
            } else {
                noticeContainer.append($('<p>').text(String(item)));
            }
        });

        noticeContainer.show();
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
                const contentBlocks = [];
                const hasConnectionStatus = response.data && response.data.connection_status;
                const connectionStatus = hasConnectionStatus ? response.data.connection_status : 'Connection established.';
                contentBlocks.push($('<p>').text('Connection successful: ' + connectionStatus));

                const userData = response.data ? response.data.user_data_for_nip : null;
                if (userData && $.isPlainObject(userData)) {
                    const labels = {
                        nip: 'NIP',
                        nama: 'Nama',
                        nik: 'NIK',
                        phone: 'Phone',
                        email: 'Email'
                    };
                    const list = $('<ul>').addClass('hcis-admin-user-list');
                    let hasEntries = false;

                    Object.keys(labels).forEach(function(key) {
                        if (!userData[key]) {
                            return;
                        }

                        hasEntries = true;
                        const listItem = $('<li>');
                        listItem.append($('<strong>').text(labels[key] + ': '));
                        listItem.append($('<span>').text(userData[key]));
                        list.append(listItem);
                    });

                    if (hasEntries) {
                        const userBlock = $('<div>').addClass('hcis-admin-user-details');
                        userBlock.append($('<p>').text('User Data for NIP:'));
                        userBlock.append(list);
                        contentBlocks.push(userBlock);
                    }
                } else if (typeof userData === 'string') {
                    contentBlocks.push($('<p>').text(userData));
                }

                showNotice(contentBlocks);
            } else {
                const errorMessage = (response.data && response.data.message)
                    ? 'Connection failed: ' + response.data.message
                    : 'Connection failed.';
                showNotice(errorMessage, true);
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
