class ProfileManager {
    constructor() {
        this.API_URL = '/api';
        this.setupEventListeners();
    }

    setupEventListeners() {
        const form = document.getElementById('changePasswordForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handlePasswordChange(e));

            // Validación en tiempo real para las contraseñas
            const newPassword = document.getElementById('new-password');
            const repeatNewPassword = document.getElementById('repeat-new-password');
            const errorMessage = document.getElementById('error-message');

            if (newPassword && repeatNewPassword) {
                // Validar coincidencia de contraseñas mientras se escribe
                [newPassword, repeatNewPassword].forEach(input => {
                    input.addEventListener('input', () => {
                        if (newPassword.value || repeatNewPassword.value) {
                            if (newPassword.value !== repeatNewPassword.value) {
                                this.showError('Las contraseñas no coinciden');
                            } else {
                                this.hideError();
                            }
                        }
                    });
                });
            }
        }
    }

    handlePasswordChange(event) {
        event.preventDefault();
        
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const repeatNewPassword = document.getElementById('repeat-new-password').value;

        // Validaciones
        if (!currentPassword) {
            this.showError('Por favor, ingresa tu contraseña actual');
            return;
        }

        if (!newPassword) {
            this.showError('Por favor, ingresa la nueva contraseña');
            return;
        }

        if (!repeatNewPassword) {
            this.showError('Por favor, confirma la nueva contraseña');
            return;
        }

        if (newPassword !== repeatNewPassword) {
            this.showError('Las nuevas contraseñas no coinciden');
            return;
        }

        if (newPassword.length < 6) {
            this.showError('La nueva contraseña debe tener al menos 6 caracteres');
            return;
        }

        if (currentPassword === newPassword) {
            this.showError('La nueva contraseña debe ser diferente a la actual');
            return;
        }

        // Mostrar loader
        loaderManager.show();

        // Obtener el token
        const token = this.getCookie('session_data');
        if (!token) {
            window.location.href = '/login.html';
            return;
        }

        // Realizar la petición AJAX
        $.ajax({
            url: `${this.API_URL}/change-password`,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            contentType: 'application/json',
            data: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            }),
            success: (response) => {
                if (response.status === 'success') {
                    // Limpiar campos
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('repeat-new-password').value = '';
                    
                    // Ocultar mensaje de error si existe
                    this.hideError();
                    
                    // Mostrar mensaje de éxito
                    alertManager.success('Contraseña actualizada exitosamente');
                } else {
                    this.showError(response.message || 'Error al cambiar la contraseña');
                    alertManager.error(response.message || 'Error al cambiar la contraseña');
                }
            },
            error: (xhr) => {
                const errorMessage = xhr.responseJSON?.message || 'Error en el servidor';
                this.showError(errorMessage);
                alertManager.error(errorMessage);
            },
            complete: () => {
                loaderManager.hide();
            }
        });
    }

    showError(message) {
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            
            // Animar la aparición del error
            errorMessage.style.opacity = '0';
            errorMessage.style.transform = 'translateY(-10px)';
            
            // Forzar un reflow
            errorMessage.offsetHeight;
            
            // Aplicar la transición
            errorMessage.style.transition = 'all 0.3s ease-in-out';
            errorMessage.style.opacity = '1';
            errorMessage.style.transform = 'translateY(0)';
        }
    }

    hideError() {
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) {
            errorMessage.style.display = 'none';
            errorMessage.textContent = '';
        }
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new ProfileManager();
});