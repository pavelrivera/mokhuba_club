// invitations-bootstrap.js
(function() {
  function ensureModal() {
    if (!document.getElementById('invitationModal')) {
      // Si el modal no está en el DOM (no se incluyó el twig), lo creamos inline:
      const tpl = `<!-- Modal Enviar Invitación (reutilizable) -->\n<div class=\"modal fade\" id=\"invitationModal\" tabindex=\"-1\" aria-labelledby=\"invitationModalLabel\" aria-hidden=\"true\">\n  <div class=\"modal-dialog modal-dialog-centered\">\n    <div class=\"modal-content\" style=\"background: linear-gradient(135deg, rgba(45, 24, 23, 0.95), rgba(92, 26, 26, 0.9)); border: 2px solid var(--gold-primary);\">\n      <div class=\"modal-header\" style=\"border-bottom: 1px solid rgba(212, 175, 55, 0.3);\">\n        <h5 class=\"modal-title\" id=\"invitationModalLabel\" style=\"color: var(--gold-primary);\">\n          <i class=\"fas fa-envelope me-2\"></i>Enviar Invitación\n        </h5>\n        <button type=\"button\" class=\"btn-close btn-close-white\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>\n      </div>\n      <div class=\"modal-body\">\n        <form id=\"invitationForm\">\n          <div class=\"mb-3\">\n            <label for=\"inviteeName\" class=\"form-label\" style=\"color: var(--cream);\"><i class=\"fas fa-user me-2\"></i>Nombre completo *</label>\n            <input type=\"text\" class=\"form-control\" id=\"inviteeName\" name=\"name\" required placeholder=\"Ej: Juan Pérez\" style=\"background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(212, 175, 55, 0.5); color: var(--cream);\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteeEmail\" class=\"form-label\" style=\"color: var(--cream);\"><i class=\"fas fa-envelope me-2\"></i>Email *</label>\n            <input type=\"email\" class=\"form-control\" id=\"inviteeEmail\" name=\"email\" required placeholder=\"ejemplo@email.com\" style=\"background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(212, 175, 55, 0.5); color: var(--cream);\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteePhone\" class=\"form-label\" style=\"color: var(--cream);\"><i class=\"fas fa-phone me-2\"></i>Teléfono (opcional)</label>\n            <input type=\"tel\" class=\"form-control\" id=\"inviteePhone\" name=\"phone\" placeholder=\"+1 234 567 8900\" style=\"background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(212, 175, 55, 0.5); color: var(--cream);\">\n          </div>\n          <div class=\"mb-3\">\n            <label for=\"inviteeMessage\" class=\"form-label\" style=\"color: var(--cream);\"><i class=\"fas fa-comment me-2\"></i>Mensaje (opcional)</label>\n            <textarea class=\"form-control\" id=\"inviteeMessage\" name=\"message\" rows=\"3\" placeholder=\"Mensaje opcional para el invitado...\" style=\"background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(212, 175, 55, 0.5); color: var(--cream);\"></textarea>\n          </div>\n          <div class=\"alert\" style=\"background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #93c5fd;\">\n            <i class=\"fas fa-info-circle me-2\"></i>La invitación expirará en 3 días y solo puede usarse una vez.\n          </div>\n          <div class=\"d-grid gap-2\">\n            <button type=\"submit\" class=\"btn btn-lg\" style=\"background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); color: var(--dark-brown); font-weight: 700;\">\n              <i class=\"fas fa-paper-plane me-2\"></i>Enviar Invitación\n            </button>\n            <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button>\n          </div>\n        </form>\n      </div>\n    </div>\n  </div>\n</div>\n`;
      document.body.insertAdjacentHTML('beforeend', tpl);
    }
  }
  async function send(e) {
    e.preventDefault();
    const data = {
      name: document.getElementById('inviteeName').value.trim(),
      email: document.getElementById('inviteeEmail').value.trim(),
      phone: document.getElementById('inviteePhone').value.trim(),
      message: document.getElementById('inviteeMessage').value.trim(),
    };
    const res = await fetch('/admin/invitations/send', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data),
      credentials: 'same-origin'
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json.ok) throw new Error(json.error || 'No se pudo enviar la invitación.');
    alert('Invitación enviada. Expira: ' + json.expiresAt);
    try {
      const modalEl = document.getElementById('invitationModal');
      const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modal.hide();
    } catch (_e) {}
    document.getElementById('invitationForm').reset();
  }
  function openModal(ev) {
    ev && ev.preventDefault && ev.preventDefault();
    ensureModal();
    try {
      const modal = new bootstrap.Modal(document.getElementById('invitationModal'));
      modal.show();
    } catch (_e) {
      // Si no hay Bootstrap Modal, al menos mostramos el bloque
      document.getElementById('invitationModal').style.display = 'block';
    }
  }
  function bind() {
    // 1) Botones con texto "Invitar" o con clase/atributo específico
    document.body.addEventListener('click', function(ev) {
      const el = ev.target.closest('a,button');
      if (!el) return;
      const text = (el.textContent || el.innerText || '').toLowerCase();
      if (el.classList.contains('js-invite') || el.hasAttribute('data-invite') || text.includes('invitar')) {
        // Evitar acciones previas que muestran "en desarrollo"
        ev.preventDefault();
        ev.stopImmediatePropagation();
        openModal(ev);
      }
    }, true); // captura para ganarle a otros handlers

    // 2) Envío
    document.addEventListener('submit', function(e) {
      const f = e.target;
      if (f && f.id === 'invitationForm') {
        send(e).catch(err => alert(err.message));
      }
    });
  }

  // 3) Anti "Función en desarrollo" (SweetAlert): si se invoca Swal.fire con ese texto, abrimos el modal
  function patchSwal() {
    if (!window.Swal || !Swal.fire) return;
    const old = Swal.fire.bind(Swal);
    Swal.fire = function(arg1, arg2, arg3) {
      const text = (typeof arg1 === 'string' ? arg1 : (arg1 && (arg1.text || arg1.title || arg1.html) || '')).toString().toLowerCase();
      if (text.includes('función en desarrollo') || text.includes('invitacion') || text.includes('invitación')) {
        openModal();
        return Promise.resolve();
      }
      return old.apply(Swal, arguments);
    };
  }

  document.addEventListener('DOMContentLoaded', function() {
    ensureModal();
    bind();
    patchSwal();
  });
})();
