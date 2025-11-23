jQuery(document).ready(function($) {
    const noticeContainer = $('#hcis-admin-notice');

    const userLabels = {
        nip: 'NIP',
        nama: 'Nama',
        nik: 'NIK',
        phone: 'Phone',
        email: 'Email',
        no_hp: 'No HP'
    };

    function buildUserDetails(userData, headingText) {
        if (!userData || !$.isPlainObject(userData)) {
            return null;
        }

        const list = $('<ul>').addClass('hcis-admin-user-list');
        let hasEntries = false;

        Object.keys(userLabels).forEach(function(key) {
            if (!userData[key]) {
                return;
            }

            hasEntries = true;
            const listItem = $('<li>');
            listItem.append($('<strong>').text(userLabels[key] + ': '));
            listItem.append($('<span>').text(userData[key]));
            list.append(listItem);
        });

        if (!hasEntries) {
            return null;
        }

        const userBlock = $('<div>').addClass('hcis-admin-user-details');
        userBlock.append($('<p>').text(headingText));
        userBlock.append(list);
        return userBlock;
    }

    function buildConnectionStatusBlocks(data) {
        const blocks = [];

        const statusDefs = [
            { key: 'google_sheets', label: 'Google Sheets' },
            { key: 'database', label: 'Database' }
        ];

        statusDefs.forEach(function(def) {
            const status = data ? data[def.key] : null;
            if (!status) {
                return;
            }

            const message = status.message || (status.success ? 'OK' : 'Unavailable');
            blocks.push($('<p>').text(def.label + ': ' + message));

            if (def.key === 'database' && status.sample_user) {
                const sampleBlock = buildUserDetails(status.sample_user, 'Contoh data pengguna dari database:');
                if (sampleBlock) {
                    blocks.push(sampleBlock);
                }
            }
        });

        if (!blocks.length && data && data.connection_status) {
            blocks.push($('<p>').text('Connection status: ' + data.connection_status));
        }

        return blocks;
    }

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
            const contentBlocks = buildConnectionStatusBlocks(response.data || {});

            const userData = response.data ? response.data.user_data_for_nip : null;
            const userBlock = buildUserDetails(userData, 'User Data for NIP:');
            if (userBlock) {
                contentBlocks.push(userBlock);
            } else if (typeof userData === 'string') {
                contentBlocks.push($('<p>').text(userData));
            }

            if (response.success) {
                if (!contentBlocks.length) {
                    contentBlocks.push($('<p>').text('Connection established.'));
                }
                showNotice(contentBlocks);
            } else {
                const errorBlocks = contentBlocks.slice();
                const errorMessage = (response.data && response.data.message)
                    ? 'Connection failed: ' + response.data.message
                    : 'Connection failed.';

                if (!errorBlocks.length) {
                    errorBlocks.push($('<p>').text(errorMessage));
                } else {
                    errorBlocks.unshift($('<p>').text(errorMessage));
                }

                showNotice(errorBlocks, true);
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

    $('#hcis-setup-sheets').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        noticeContainer.hide();

        $.post(hcis_admin.ajax_url, {
            action: 'hcis_setup_sheets',
            _ajax_nonce: hcis_admin.nonce
        }, function(response) {
            const contentBlocks = [];
            if (response.data) {
                if (Array.isArray(response.data.created) && response.data.created.length) {
                    contentBlocks.push($('<p>').text('Tab dibuat: ' + response.data.created.join(', ')));
                }

                if (Array.isArray(response.data.skipped) && response.data.skipped.length) {
                    contentBlocks.push($('<p>').text('Tab sudah ada: ' + response.data.skipped.join(', ')));
                }
            }

            const message = (response.data && response.data.message) || 'Setup selesai.';

            if (response.success) {
                contentBlocks.unshift($('<p>').text(message));
                showNotice(contentBlocks);
            } else {
                showNotice(message, true);
            }
        }).fail(function() {
            showNotice('Gagal menjalankan setup.', true);
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
