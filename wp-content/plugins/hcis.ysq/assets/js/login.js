(function () {
  const shared = window.hcisysqShared;
  if (!shared) return;

  function bootLogin() {
    const form = document.getElementById('hcisysq-login-form');
    if (!form) return;

    const eye = document.getElementById('hcisysq-eye');
    const pw = document.getElementById('hcisysq-pw');
    if (eye && pw) {
      eye.addEventListener('click', () => {
        pw.type = pw.type === 'password' ? 'text' : 'password';
        eye.textContent = (pw.type === 'password') ? 'lihat' : 'sembunyikan';
        pw.focus();
      });
    }

    const msg = form.querySelector('.msg');
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      msg.className = 'msg';
      msg.textContent = 'Memeriksa…';

      const nip = (form.nip.value || '').trim();
      const pwv = (form.pw.value || '').trim();
      if (!nip || !pwv) {
        msg.textContent = 'Akun & Password wajib diisi.';
        return;
      }

      shared.ajax('hcisysq_login', { nip, pw: pwv })
        .then((res) => {
          if (!res || !res.ok) {
            msg.textContent = (res && res.msg) ? res.msg : 'Login gagal.';
            return;
          }
          const dashSlug = (window.hcisysq && hcisysq.dashboardSlug) ? hcisysq.dashboardSlug : 'dashboard';
          const redirectUrl = res.redirect || ('/' + dashSlug.replace(/^\/+/, '') + '/');
          window.location.href = redirectUrl;
        });
    });

  }

  document.addEventListener('DOMContentLoaded', bootLogin);
})();
