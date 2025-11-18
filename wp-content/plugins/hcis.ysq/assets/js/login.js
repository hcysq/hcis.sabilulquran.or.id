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
    const $eyeBtn = $form.find('#hcisysq-eye');

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
          }
        })
        .fail(function() {
          $msg.text('Terjadi kesalahan jaringan. Periksa koneksi Anda.').removeClass('success').addClass('error').show();
        })
        .always(function() {
          $submitBtn.prop('disabled', false).text('Masuk');
        });
    });

    if ($eyeBtn.length) {
      $eyeBtn.on('click', function() {
        const isPassword = $pwInput.attr('type') === 'password';
        $pwInput.attr('type', isPassword ? 'text' : 'password');
        $(this).text(isPassword ? 'sembunyi' : 'lihat');
      });
    }
  }

  $(document).ready(function() {
    initLoginForm();
  });

})(jQuery, window.hcisysq);
