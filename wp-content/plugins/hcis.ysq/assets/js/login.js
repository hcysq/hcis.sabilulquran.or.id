(function($, hcisysq) {
  'use strict';

  function initLoginForm() {
    const $form = $('#hcisysq-login-form');
    if (!$form.length) {
      return;
    }

    const $msg = $form.find('.msg');
    const $submitBtn = $form.find('button[type="submit"]');
    const $nipInput = $form.find('input[name="nip"]');
    const $pwInput = $form.find('input[name="pw"]');

    $form.on('submit', function(e) {
      e.preventDefault();

      const nip = $nipInput.val();
      const pw = $pwInput.val();

      if (!nip || !pw) {
        $msg.text('NIP dan Password wajib diisi.').show();
        return;
      }

      $submitBtn.prop('disabled', true).text('Memproses...');
      $msg.hide().empty();

      const data = {
        action: 'hcisysq_login',
        _wpnonce: hcisysq.nonce,
        nip: nip,
        pw: pw,
      };
      
      const captchaToken = $form.find('input[name="hcisysq_captcha_token"]').val();
      if (captchaToken) {
        data.hcisysq_captcha_token = captchaToken;
      }

      $.post(hcisysq.ajax, data)
        .done(function(res) {
          if (res.ok) {
            $msg.text('Login berhasil. Mengalihkan...').removeClass('error').addClass('success').show();
            window.location.href = res.redirect || hcisysq.dashboardSlug;
          } else {
            // Display the specific error message from the server
            const errorMessage = res.msg || 'Terjadi kesalahan. Silakan coba lagi.';
            $msg.text(errorMessage).removeClass('success').addClass('error').show();

            // Log debug info if available
            if (res.debug && Array.isArray(res.debug)) {
              console.warn('HCISYSQ Login Debug Info:');
              res.debug.forEach(function(log) {
                console.log(log);
              });
            }
          }
        })
        .fail(function() {
          $msg.text('Terjadi kesalahan jaringan. Periksa koneksi Anda.').removeClass('success').addClass('error').show();
        })
        .always(function() {
          $submitBtn.prop('disabled', false).text('Masuk');
        });
    });
  }

  function initPasswordToggleButtons() {
    const $forms = $('#hcisysq-login-form, #hcisysq-reset-password-form');
    if (!$forms.length) {
      return;
    }

    $forms.find('button.eye[data-toggle]').each(function() {
      const $btn = $(this);
      const targetId = $btn.data('toggle');
      if (!targetId) {
        return;
      }

      const $input = $('#' + targetId);
      if (!$input.length) {
        return;
      }

      $btn.on('click', function() {
        const isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $btn.text(isPassword ? 'sembunyi' : 'lihat');
      });
    });
  }

  $(document).ready(function() {
    initLoginForm();
    initPasswordToggleButtons();
  });

})(jQuery, window.hcisysq);
