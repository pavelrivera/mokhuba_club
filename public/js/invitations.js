/**
 * public/js/invitations.js
 * Adjunta a cualquier botón con [data-invite]
 */
(function(){
  function sendInvite(email, notes){
    return fetch('/invitations/send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, notes: notes || null })
    }).then(r => r.json());
  }

  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('[data-invite]');
    if(!btn){ return; }
    ev.preventDefault();
    var email = prompt('Email del invitado:');
    if(!email){ return; }
    var notes = prompt('Notas para el invitado (opcional):') || '';
    sendInvite(email, notes).then(function(res){
      alert(res.message || (res.ok ? 'Listo' : 'Error'));
      if(res.ok){ window.location.reload(); }
    }).catch(function(e){
      alert('Error de red: ' + e);
    });
  });
})();