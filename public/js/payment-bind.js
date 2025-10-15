(function () {
  console.log('[payment-bind] attaching listeners');
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-action="proceed-payment"]');
    if (!btn) return;
    ev.preventDefault();
    var level = btn.getAttribute('data-level');
    if (!level) {
      console.error('Missing data-level in proceed-payment button');
      return;
    }
    if (!window.PaymentClient || !window.PaymentClient.checkout) {
      console.error('PaymentClient not available');
      return;
    }
    PaymentClient.checkout(level, btn);
  }, true);
})();