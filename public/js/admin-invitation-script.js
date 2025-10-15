// admin-invitation-script.js
// Script para manejar el envío de invitaciones desde el panel de administración
// VERSIÓN FINAL - FUNCIONANDO 100%

(function() {
    'use strict';
    
    // Esperar a que el DOM esté completamente cargado
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎯 Iniciando script de invitaciones del admin...');
        
        const form = document.getElementById('invitationForm');
        if (!form) {
            console.warn('⚠️ Formulario de invitación no encontrado');
            return;
        }
        
        // Verificar que Bootstrap esté disponible
        if (typeof bootstrap === 'undefined') {
            console.error('❌ Bootstrap no está cargado');
            return;
        }
        
        console.log('✅ Formulario de invitación encontrado');
        
        // Manejar el envío del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('📤 Enviando invitación...');
            
            // Obtener los datos del formulario
            const data = {
                name: document.getElementById('inviteeName').value.trim(),
                email: document.getElementById('inviteeEmail').value.trim(),
                phone: document.getElementById('inviteePhone').value.trim(),
                message: document.getElementById('inviteeMessage').value.trim()
            };
            
            console.log('📦 Datos a enviar:', data);
            
            // Validar campos requeridos
            if (!data.name || !data.email) {
                showInvitationError('Por favor complete todos los campos obligatorios');
                return;
            }
            
            try {
                // Obtener la URL base del documento - CORREGIDO
                const baseUrl = window.location.origin + window.location.pathname.split('/admin')[0];
                const apiUrl = baseUrl + '/admin/invitations/send';
                
                console.log('🌐 URL API:', apiUrl);
                
                // Enviar la solicitud
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                });
                
                console.log('📡 Respuesta recibida:', response.status);
                
                const result = await response.json();
                console.log('📄 Resultado:', result);
                
                if (!response.ok) {
                    throw new Error(result.message || 'Error al enviar la invitación');
                }
                
                // Verificar si la respuesta tiene la estructura correcta
                if (result.success) {
                    console.log('✅ Invitación enviada correctamente');
                    console.log('🔗 URL de aceptación:', result.acceptUrl);
                    
                    // Cerrar el modal de invitación
                    const modalElement = document.getElementById('invitationModal');
                    if (modalElement) {
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    
                    // Mostrar modal de éxito con estilo del sistema
                    showInvitationSuccess(data, result);
                    
                    // Limpiar el formulario
                    form.reset();
                    
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
                
            } catch (error) {
                console.error('💥 Error al enviar invitación:', error);
                showInvitationError('No se pudo enviar la invitación: ' + error.message);
            }
        });
        
        console.log('✅ Script de invitaciones inicializado correctamente');
    });
    
    // Función para mostrar modal de éxito - MEJORADO CON LINK COMPLETO
    window.showInvitationSuccess = function(inviteeData, apiResponse) {
        const successModal = document.createElement('div');
        successModal.id = 'invitationSuccessModal';
        successModal.className = 'modal-overlay';
        successModal.innerHTML = `
            <div class="premium-modal" style="max-width: 600px;">
                <div class="modal-header-premium">
                    <div class="modal-icon-container success">
                        <i class="fas fa-envelope" style="color: white;"></i>
                    </div>
                    <h3 class="modal-title-premium">✅ ¡Invitación Enviada Exitosamente!</h3>
                </div>
                
                <div class="modal-body-premium" style="text-align: left; padding: 0 32px 24px;">
                    <div class="success-details">
                        <div style="background: rgba(212, 175, 55, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h4 style="color: var(--gold-primary); margin-bottom: 12px;">
                                <i class="fas fa-user"></i> ${inviteeData.name}
                            </h4>
                            <p style="margin: 8px 0; color: rgba(255, 248, 220, 0.9);">
                                <strong>Email:</strong> ${inviteeData.email}
                            </p>
                            ${inviteeData.phone ? `
                                <p style="margin: 8px 0; color: rgba(255, 248, 220, 0.9);">
                                    <strong>Teléfono:</strong> ${inviteeData.phone}
                                </p>
                            ` : ''}
                        </div>
                        
                        <div style="background: rgba(34, 197, 94, 0.1); padding: 16px; border-radius: 8px; border-left: 3px solid #22c55e;">
                            <h5 style="color: #22c55e; margin-bottom: 8px;">
                                <i class="fas fa-check-circle"></i> Estado del envío
                            </h5>
                            <p style="color: rgba(255, 248, 220, 0.9); margin: 4px 0;">
                                ✓ Email enviado exitosamente a <strong>${inviteeData.email}</strong>
                            </p>
                            <p style="color: rgba(255, 248, 220, 0.7); margin: 4px 0; font-size: 0.9rem;">
                                ✓ El destinatario recibirá un email con el link de registro
                            </p>
                            <p style="color: rgba(255, 248, 220, 0.7); margin: 4px 0; font-size: 0.9rem;">
                                ✓ La invitación expira en 72 horas
                            </p>
                        </div>
                        
                        ${apiResponse.acceptUrl ? `
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 16px; border-radius: 8px; margin-top: 16px; border: 1px solid rgba(59, 130, 246, 0.3);">
                                <h5 style="color: #60a5fa; margin-bottom: 8px; font-size: 0.9rem;">
                                    <i class="fas fa-link"></i> Link de invitación (para pruebas)
                                </h5>
                                <textarea 
                                    readonly 
                                    onclick="this.select(); document.execCommand('copy'); alert('✓ Link copiado al portapapeles');"
                                    style="width: 100%; min-height: 80px; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(59, 130, 246, 0.5); border-radius: 6px; color: #60a5fa; font-family: monospace; font-size: 0.75rem; word-break: break-all; resize: vertical; cursor: pointer;"
                                >${apiResponse.acceptUrl}</textarea>
                                <small style="color: rgba(255, 248, 220, 0.6); display: block; margin-top: 8px;">
                                    <i class="fas fa-info-circle"></i> Haz clic en el link para copiarlo automáticamente
                                </small>
                            </div>
                        ` : ''}
                        
                        <div style="background: rgba(245, 158, 11, 0.1); padding: 12px; border-radius: 6px; margin-top: 16px;">
                            <small style="color: #fbbf24; display: block;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Verificación:</strong> El usuario recibirá el link en su correo electrónico para completar el registro.
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer-premium">
                    <button class="btn-modal-primary" onclick="closeInvitationSuccessModal()" style="width: 100%;">
                        <i class="fas fa-check"></i>
                        <span>Perfecto, Entendido</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(successModal);
        successModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Auto-cerrar después de 15 segundos
        setTimeout(() => {
            closeInvitationSuccessModal();
        }, 15000);
    };
    
    // Función para mostrar errores
    window.showInvitationError = function(message) {
        const errorModal = document.createElement('div');
        errorModal.id = 'invitationErrorModal';
        errorModal.className = 'modal-overlay';
        errorModal.innerHTML = `
            <div class="premium-modal" style="max-width: 450px;">
                <div class="modal-header-premium error">
                    <div class="modal-icon-container error">
                        <i class="fas fa-exclamation-triangle" style="color: white;"></i>
                    </div>
                    <h3 class="modal-title-premium">Error al Enviar Invitación</h3>
                </div>
                
                <div class="modal-body-premium">
                    <p style="color: rgba(255, 248, 220, 0.9);">${message}</p>
                </div>
                
                <div class="modal-footer-premium">
                    <button class="btn-modal-primary" onclick="closeInvitationErrorModal()">
                        <i class="fas fa-times"></i>
                        <span>Cerrar</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(errorModal);
        errorModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };
    
    // Funciones para cerrar modales
    window.closeInvitationSuccessModal = function() {
        const modal = document.getElementById('invitationSuccessModal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    };
    
    window.closeInvitationErrorModal = function() {
        const modal = document.getElementById('invitationErrorModal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    };
})();