// public/js/invitations-ui.js
(function() {
  function openModal() {
    const el = document.getElementById('invitationModal');
    if (!el) return console.error('Modal de invitación no encontrado');
    try { (new bootstrap.Modal(el)).show(); } catch (e) { el.style.display='block'; }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Fuerza los botones de Invitar a abrir el modal
    document.body.addEventListener('click', function(ev) {
      const t = ev.target.closest('button, a, [role="button"]');
      if (!t) return;
      const label = (t.innerText || t.textContent || '').trim().toLowerCase();
      if (t.classList.contains('js-invite') || t.hasAttribute('data-invite') || label.includes('invitar')) {
        ev.preventDefault(); ev.stopImmediatePropagation();
        openModal();
      }
    }, true);
  });

  // Submit
  document.addEventListener('submit', function(e) {
    const f = e.target;
    if (f && f.id === 'invitationForm') {
      e.preventDefault();
      const payload = {
        name: document.getElementById('inviteeName').value.trim(),
        email: document.getElementById('inviteeEmail').value.trim(),
        phone: document.getElementById('inviteePhone').value.trim(),
        message: document.getElementById('inviteeMessage').value.trim(),
      };
      fetch('/admin/invitations/send', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload), credentials:'same-origin'
      }).then(r=>r.json()).then(j=>{
        if(!j.ok) throw new Error(j.error||'No se pudo enviar');
        alert('Invitación enviada. Expira: '+j.expiresAt);
      }).catch(err=>alert(err.message));
    }
  });
})();
