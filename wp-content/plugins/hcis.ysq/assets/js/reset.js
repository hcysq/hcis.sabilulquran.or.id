(function () {
  const shared = window.hcisysqShared;
  if (!shared) return;

  function bootReset() {
    const form = document.getElementById('hcisysq-reset-form');
    if (!form) return;

    const token = form.dataset.token || '';
    const msg = form.querySelector('.msg');
    const passwordInput = form.querySelector('input[name="password"]');
    const confirmInput = form.querySelector('input[name="confirm"]');
    const nipInput = form.querySelector('input[name="nip"]');

    form.addEventListener('submit', (event) => {
      event.preventDefault();

      if (!msg) return;
      msg.className = 'msg';

      if (!token) {
        msg.textContent = 'Token reset tidak ditemukan.';
        return;
      }

      const password = (passwordInput && passwordInput.value ? passwordInput.value : '').trim();
      const confirm = (confirmInput && confirmInput.value ? confirmInput.value : '').trim();
      const nip = (nipInput && nipInput.value ? nipInput.value : '').trim();

      if (!password) {
        msg.textContent = 'Password baru wajib diisi.';
        return;
      }

      if (password.length < 6) {
        msg.textContent = 'Password minimal 6 karakter.';
        return;
      }

      if (!confirm || confirm !== password) {
        msg.textContent = 'Konfirmasi password harus sama.';
        return;
      }

      msg.textContent = 'Memperbarui password…';

      shared.ajax('hcisysq_reset_password', { token, password, confirm, nip })
        .then((res) => {
          if (res && res.ok) {
            msg.className = 'msg ok';
            msg.textContent = res.msg || 'Password berhasil diperbarui. Anda akan diarahkan ke halaman login.';
            const loginSlug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug : 'masuk';
            setTimeout(() => {
              window.location.href = '/' + loginSlug.replace(/^\/+/, '') + '/';
            }, 2000);
          } else {
            msg.className = 'msg';
            msg.textContent = (res && res.msg) ? res.msg : 'Gagal memperbarui password. Coba lagi.';
          }
        });
    });
  }

  document.addEventListener('DOMContentLoaded', bootReset);
})();
