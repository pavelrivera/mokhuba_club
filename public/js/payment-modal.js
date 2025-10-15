/**
 * payment-modal.js
 * Manejo del Modal de Pago con validaciones de formulario
 */

// Configuración de precios por membresía
const membershipPrices = {
    'ruby': { price: 299, name: 'Ruby Club', description: 'Acceso completo a funciones Ruby' },
    'gold': { price: 599, name: 'Gold Club', description: 'Acceso premium Gold + beneficios exclusivos' },
    'platinum': { price: 999, name: 'Platinum Club', description: 'Acceso VIP ilimitado + conserje personal' }
};

/**
 * Abrir modal de pago con la membresía seleccionada
 */
function openPaymentModal(membershipType) {
    // Validar que la membresía existe
    if (!membershipPrices[membershipType]) {
        console.error('Membresía no válida:', membershipType);
        return;
    }

    const membership = membershipPrices[membershipType];
    
    // Actualizar información en el modal
    document.querySelector('.membership-type-display').textContent = membership.name;
    document.querySelector('.membership-price-display').textContent = '$' + membership.price;
    document.querySelector('#finalAmount').textContent = '$' + membership.price;
    document.getElementById('membershipType').value = membershipType;
    
    // Actualizar descripción
    const descriptionElement = document.querySelector('.selected-membership-info p');
    if (descriptionElement) {
        descriptionElement.textContent = membership.description;
    }
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

/**
 * Formatear número de tarjeta automáticamente
 */
document.addEventListener('DOMContentLoaded', function() {
    const cardNumberInput = document.getElementById('cardNumber');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }

    /**
     * Formatear fecha de expiración MM/AA
     */
    const expiryDateInput = document.getElementById('expiryDate');
    if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            
            e.target.value = value;
        });
    }

    /**
     * Solo números en CVV
     */
    const cvvInput = document.getElementById('cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/gi, '');
        });
    }

    /**
     * Validación del formulario antes de enviar
     */
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar campos
            if (!validatePaymentForm()) {
                return false;
            }
            
            // Deshabilitar botón para evitar doble envío
            const submitBtn = document.getElementById('submitPaymentBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            
            // Enviar formulario
            this.submit();
        });
    }
});

/**
 * Validar formulario de pago
 */
function validatePaymentForm() {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s+/g, '');
    const expiryDate = document.getElementById('expiryDate').value;
    const cvv = document.getElementById('cvv').value;
    const cardholderName = document.getElementById('cardholderName').value;
    const acceptTerms = document.getElementById('acceptTerms').checked;
    
    // Validar nombre del titular
    if (cardholderName.trim().length < 3) {
        showError('Por favor ingresa un nombre válido');
        return false;
    }
    
    // Validar número de tarjeta (debe tener 16 dígitos)
    if (cardNumber.length !== 16) {
        showError('El número de tarjeta debe tener 16 dígitos');
        return false;
    }
    
    // Validar fecha de expiración (formato MM/AA)
    const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
    if (!expiryRegex.test(expiryDate)) {
        showError('Fecha de expiración inválida. Usa formato MM/AA');
        return false;
    }
    
    // Validar que la fecha no esté vencida
    const [month, year] = expiryDate.split('/');
    const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
    const now = new Date();
    if (expiry < now) {
        showError('La tarjeta ha expirado');
        return false;
    }
    
    // Validar CVV (3 o 4 dígitos)
    if (cvv.length < 3 || cvv.length > 4) {
        showError('El CVV debe tener 3 o 4 dígitos');
        return false;
    }
    
    // Validar términos y condiciones
    if (!acceptTerms) {
        showError('Debes aceptar los términos y condiciones');
        return false;
    }
    
    return true;
}

/**
 * Mostrar mensaje de error
 */
function showError(message) {
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar antes del formulario
    const modalBody = document.querySelector('#paymentModal .modal-body');
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Función global para testing
 */
window.testPaymentModal = function(membershipType = 'ruby') {
    console.log('Testing payment modal with membership:', membershipType);
    openPaymentModal(membershipType);
};
