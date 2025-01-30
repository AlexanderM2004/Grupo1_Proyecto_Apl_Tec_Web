$(document).ready(function() {
    // Constantes
    const JWT_EXPIRATION = 3600; // 1 hora en segundos
    const API_URL = '/api';

    // Funciones de utilidad para cookies
    function setCookie(name, value, seconds) {
        const date = new Date();
        date.setTime(date.getTime() + (seconds * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // Función para mostrar errores
    function showError(element, message) {
        element.addClass('input-error');
        const errorDiv = element.siblings('.error-message');
        if (errorDiv.length) {
            errorDiv.text(message).show();
        } else {
            $(`<div class="error-message">${message}</div>`).insertAfter(element);
        }
    }

    // Función para limpiar errores
    function clearError(element) {
        element.removeClass('input-error');
        element.siblings('.error-message').hide();
    }

    // Función para validar formulario de login
    function validateLoginForm() {
        let isValid = true;
        const username = $('#loginUsername');
        const password = $('#loginPassword');

        // Limpiar errores previos
        clearError(username);
        clearError(password);

        // Validar usuario
        if (!username.val().trim()) {
            showError(username, 'El usuario es requerido');
            isValid = false;
        }

        // Validar contraseña
        if (!password.val().trim()) {
            showError(password, 'La contraseña es requerida');
            isValid = false;
        }

        return isValid;
    }

    // Función para validar formulario de registro
    function validateRegisterForm() {
        let isValid = true;
        const username = $('#registerUsername');
        const password = $('#registerPassword');
        const confirmPassword = $('#confirmPassword');
        const genderInput = $('#genderInput');

        // Limpiar errores previos
        clearError(username);
        clearError(password);
        clearError(confirmPassword);
        
        // Validar usuario
        if (!username.val().trim()) {
            showError(username, 'El usuario es requerido');
            isValid = false;
        } else if (username.val().trim().length < 3) {
            showError(username, 'El usuario debe tener al menos 3 caracteres');
            isValid = false;
        }

        // Validar contraseña
        if (!password.val()) {
            showError(password, 'La contraseña es requerida');
            isValid = false;
        } else if (password.val().length < 6) {
            showError(password, 'La contraseña debe tener al menos 6 caracteres');
            isValid = false;
        }

        // Validar confirmación de contraseña
        if (password.val() !== confirmPassword.val()) {
            showError(confirmPassword, 'Las contraseñas no coinciden');
            isValid = false;
        }

        // Validar género
        if (!genderInput.val()) {
            alert('Por favor selecciona un género');
            isValid = false;
        }

        return isValid;
    }

    // Manejar login
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateLoginForm()) {
            return;
        }

        $.ajax({
            url: `${API_URL}/login`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: $('#loginUsername').val(),
                password: $('#loginPassword').val()
            }),
            success: function(response) {
                if (response.status === 'success') {
                    setCookie('session_data', response.token, JWT_EXPIRATION);
                    window.location.href = '/';
                } else {
                    alert(response.message || 'Error en el inicio de sesión');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error en el servidor');
            }
        });
    });

    // Manejar registro
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();

        if (!validateRegisterForm()) {
            return;
        }

        $.ajax({
            url: `${API_URL}/register`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: $('#registerUsername').val(),
                password: $('#registerPassword').val(),
                genero: $('#genderInput').val(),
                pais: 'Ecuador' // Valor por defecto
            }),
            success: function(response) {
                if (response.status === 'success') {
                    setCookie('session_data', response.token, JWT_EXPIRATION);
                    window.location.href = '/';
                } else {
                    alert(response.message || 'Error en el registro');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error en el servidor');
            }
        });
    });

    // Input events para limpiar errores mientras el usuario escribe
    $('input').on('input', function() {
        clearError($(this));
    });

    // Manejar cierre de sesión
    $('.logout-link').on('click', function(e) {
        e.preventDefault();
        document.cookie = 'session_data=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        window.location.href = '/login';
    });

    // Verificar autenticación al cargar la página
    function checkAuth() {
        const token = getCookie('session_data');
        const currentPath = window.location.pathname;
        
        if ((currentPath === '/login' || currentPath === '/login.html') && token) {
            window.location.href = '/';
            return;
        }
        
        if (currentPath !== '/login' && currentPath !== '/login.html' && !token) {
            //window.location.href = '/login';
            return;
        }
    }

    // Verificar autenticación al cargar la página
    checkAuth();
});