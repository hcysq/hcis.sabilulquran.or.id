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
        msg.textContent = 'Akun & Pasword wajib diisi.';
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

    const forgotBtn = document.getElementById('hcisysq-forgot');
    const backdrop = document.getElementById('hcisysq-modal');
    const cancelBtn = document.getElementById('hcisysq-cancel');
    const closeBtn = document.getElementById('hcisysq-close-modal');
    const sendBtn = document.getElementById('hcisysq-send');
    const npInput = document.getElementById('hcisysq-nip-forgot');
    const fMsg = document.getElementById('hcisysq-forgot-msg');

    if (forgotBtn && backdrop) {
      const closeModal = () => {
        backdrop.style.display = 'none';
        if (fMsg) {
          fMsg.className = 'modal-msg';
          fMsg.textContent = '';
        }
      };

      forgotBtn.onclick = () => {
        backdrop.style.display = 'flex';
        if (npInput) npInput.value = (form.nip.value || '').trim();
        if (fMsg) {
          fMsg.className = 'modal-msg';
          fMsg.textContent = '';
        }
      };

      if (cancelBtn) cancelBtn.onclick = closeModal;
      if (closeBtn) closeBtn.onclick = closeModal;
      if (sendBtn) {
        sendBtn.onclick = () => {
          const nip = (npInput.value || '').trim();
          if (!nip) {
            fMsg.textContent = 'Akun wajib diisi.';
            return;
          }
          fMsg.textContent = 'Mengirim permintaan…';

          shared.ajax('hcisysq_forgot', { nip })
            .then((res) => {
              if (res && res.ok) {
                fMsg.className = 'modal-msg ok';
                fMsg.textContent = res.msg || 'Permintaan terkirim. Anda akan dihubungi Admin via WhatsApp.';
                setTimeout(closeModal, 1500);
              } else {
                fMsg.className = 'modal-msg';
                fMsg.textContent = (res && res.msg) ? res.msg : 'Gagal mengirim permintaan. Coba lagi.';
              }
            });
        };
      }
    }
  }

  document.addEventListener('DOMContentLoaded', bootLogin);
})();
