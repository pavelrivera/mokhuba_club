(function(){
  function getBasePath() {
    var tag = document.querySelector('meta[name="base-path"]');
    return tag ? tag.getAttribute('content') : '';
  }

  window.editProfile = function () {
    window.location.href = getBasePath() + '/profile/edit';
  };

  var selectedMembership = null;
  window.selectMembership = function(level){
    selectedMembership = level;
    try {
      document.querySelectorAll('[data-membership]').forEach(function(el){
        el.classList.toggle('active', el.getAttribute('data-membership') === String(level));
      });
    } catch(e){ /* noop */ }
  };

  window.proceedToPayment = function(level){
    var lvl = level || selectedMembership;
    if(!lvl){
      alert('Selecciona una membresía primero.');
      return;
    }
    window.location.href = getBasePath() + '/payment/checkout/' + encodeURIComponent(lvl);
  };
})();