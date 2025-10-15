// mkh-invitations-final.js
(function() {
  const INVITE_ENDPOINT = '/admin/invitations/send';

  function ensureModalPresent() {
    if (!document.getElementById('invitationModal')) {
      const tpl = `<!-- Modal de Invitación Reutilizable -->\n<div class=\"modal fade\" id=\"invitationModal\" tabindex=\"-1\" aria-labelledby=\"invitationModalLabel\" aria-hidden=\"true\">\n  <div class=\"modal-dialog modal-dialog-centered\">\n    <div class=\"modal-content\" style=\"background: linear-gradient(135deg, rgba(45, 24, 23, 0.95), rgba(92, 26, 26, 0.9)); border: 2px solid var(--gold-primary);\">\n      <div class=\"modal-header\" style=\"border-bottom: 1px solid rgba(212, 175, 55, 0.3);\">\n        <h5 class=\"modal-title\" id=\"invitationModalLabel\" style=\"color: var(--gold-primary);\">\n          <i class=\"fas fa-envelope me-2\"></i>Enviar Invitación\n        </h5>\n        <button type=\"button\" class=\"btn-close btn-close-white\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>\n      </div>\n      <div class=\"modal-body\">\n        <form id=\"invitationForm\">\n          <div class=\"mb-3\">\n            <label for=\"inviteeName\" class=\"form-label\" style=\"color: var(--cream);\">Nombre completo *</label>\n            <input type=\"text\" class=\"form-control\" id=\"inviteeName\" name=\"name\" required placeholder=\"Ej: Juan Pérez\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteeEmail\" class=\"form-label\" style=\"color: var(--cream);\">Email *</label>\n            <input type=\"email\" class=\"form-control\" id=\"inviteeEmail\" name=\"email\" required placeholder=\"ejemplo@email.com\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteePhone\" class=\"form-label\" style=\"color: var(--cream);\">Teléfono (opcional)</label>\n            <input type=\"tel\" class=\"form-control\" id=\"inviteePhone\" name=\"phone\" placeholder=\"+1 234 567 8900\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteeMessage\" class=\"form-label\" style=\"color: var(--cream);\">Mensaje (opcional)</label>\n            <textarea class=\"form-control\" id=\"inviteeMessage\" name=\"message\" rows=\"3\" placeholder=\"Mensaje opcional para el invitado...\"></textarea>\n          </div>\n          <div class=\"alert alert-info\">\n            La invitación expirará en 72 horas y solo puede usarse una vez.\n          </div>\n          <div class=\"d-grid gap-2\">\n            <button type=\"submit\" class=\"btn btn-lg btn-warning fw-bold\">\n              Enviar Invitación\n            </button>\n            <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button>\n          </div>\n        </form>\n      </div>\n    </div>\n  </div>\n</div>\n`;
      document.body.insertAdjacentHTML('beforeend', tpl);
    }
  }

  async function sendInvite(e) {
    e.preventDefault();
    const data = {
      name: document.getElementById('inviteeName').value.trim(),
      email: document.getElementById('inviteeEmail').value.trim(),
      phone: document.getElementById('inviteePhone').value.trim(),
      message: document.getElementById('inviteeMessage').value.trim(),
    };
    const res = await fetch(INVITE_ENDPOINT, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data),
      credentials: 'same-origin'
    });
    let json = {};
    try { json = await res.json(); } catch (_e) {}
    if (!res.ok || !json.ok) throw new Error(json.error || 'No se pudo enviar la invitación');
    try {
      const modalEl = document.getElementById('invitationModal');
      (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
    } catch (_e) {}
    alert('Invitación enviada. Expira: ' + (json.expiresAt || '72h'));
    document.getElementById('invitationForm').reset();
  }

  function openInviteModal(ev) {
    if (ev && ev.preventDefault) ev.preventDefault();
    ensureModalPresent();
    try { (new bootstrap.Modal(document.getElementById('invitationModal'))).show(); }
    catch (_e) { document.getElementById('invitationModal').style.display = 'block'; }
  }

  // 1) Captura de clicks ANTES que otros handlers (para anular "Función en desarrollo")
  document.addEventListener('click', function(ev) {
    const el = ev.target.closest('button, a, [role="button"]');
    if (!el) return;
    const txt = (el.textContent || el.innerText || '').toLowerCase();
    if (el.classList.contains('js-invite') || el.hasAttribute('data-invite') || txt.includes('invitar')) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      openInviteModal(ev);
    }
  }, true);

  // 2) Observa el DOM: si aparece un modal con “Función en desarrollo”, lo cierra y abre el nuestro
  const observer = new MutationObserver(() => {
    const err = Array.from(document.querySelectorAll('.modal, [role="dialog"], .swal2-container'))
      .find(n => (n.textContent || '').toLowerCase().includes('función en desarrollo'));
    if (err) {
      const btn = err.querySelector('button, [data-bs-dismiss="modal"]');
      if (btn) btn.click();
      openInviteModal();
    }
  });
  observer.observe(document.documentElement, {childList: true, subtree: true});

  // 3) Enlace submit
  document.addEventListener('submit', function(e) {
    const f = e.target;
    if (f && f.id === 'invitationForm') {
      sendInvite(e).catch(err => alert(err.message));
    }
  });

  // 4) Asegurar modal desde el inicio
  document.addEventListener('DOMContentLoaded', ensureModalPresent);
})();
