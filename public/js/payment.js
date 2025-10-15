(function () {
  console.log("[payment.js] loaded");
  function createHiddenInput(name, value) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    return input;
  }
  function submitPost(url, params) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    for (var k in params) {
      if (Object.prototype.hasOwnProperty.call(params, k)) {
        form.appendChild(createHiddenInput(k, params[k]));
      }
    }
    document.body.appendChild(form);
    form.submit();
  }
  function disableBtn(btn, text) {
    if (!btn) return;
    btn.dataset._originalText = btn.innerText;
    btn.disabled = true;
    if (text) btn.innerText = text;
  }
  function enableBtn(btn) {
    if (!btn) return;
    btn.disabled = false;
    if (btn.dataset._originalText) btn.innerText = btn.dataset._originalText;
  }
  function showToast(msg) {
    if (window.Toastify) {
      Toastify({ text: msg, duration: 4000 }).showToast();
    } else {
      alert(msg);
    }
  }
  window.PaymentClient = {
    checkout: function (level, btn) {
      try {
        if (!window.PAYMENT_CFG) throw new Error('PAYMENT_CFG missing');
        var checkoutUrl = window.PAYMENT_CFG.checkoutUrl;
        var csrf = window.PAYMENT_CFG.csrf;
        if (!checkoutUrl) throw new Error('checkoutUrl missing');
        if (!csrf) throw new Error('csrf token missing');
        if (!level) throw new Error('membership level missing');
        disableBtn(btn, 'Procesando...');
        submitPost(checkoutUrl, { level: level, _csrf_token: csrf });
      } catch (e) {
        console.error(e);
        enableBtn(btn);
        showToast('No se pudo iniciar el pago: ' + e.message);
      }
    }
  };
})();