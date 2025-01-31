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

    // Funciones de validación
    function showValidationError(element, message) {
        // Remover error anterior si existe
        removeValidationError(element);
        
        // Agregar clase de error
        element.addClass('is-invalid');
        
        // Crear y agregar mensaje de error
        const errorDiv = $('<div>')
            .addClass('invalid-feedback')
            .text(message);
        
        element.after(errorDiv);
    }

    function removeValidationError(element) {
        element.removeClass('is-invalid');
        element.next('.invalid-feedback').remove();
    }

    function validatePassword(password) {
        // Al menos 6 caracteres
        if (password.length < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        return '';
    }

    function validateUsername(username) {
        // Al menos 3 caracteres y sin espacios
        if (username.length < 3) {
            return 'El usuario debe tener al menos 3 caracteres';
        }
        if (username.includes(' ')) {
            return 'El usuario no puede contener espacios';
        }
        return '';
    }

    // Validar formulario de login
    function validateLoginForm() {
        let isValid = true;
        const username = $('#loginUsername');
        const password = $('#loginPassword');

        // Limpiar errores previos
        removeValidationError(username);
        removeValidationError(password);

        // Validar usuario
        if (!username.val().trim()) {
            showValidationError(username, 'El usuario es requerido');
            isValid = false;
        } else {
            const usernameError = validateUsername(username.val().trim());
            if (usernameError) {
                showValidationError(username, usernameError);
                isValid = false;
            }
        }

        // Validar contraseña
        if (!password.val()) {
            showValidationError(password, 'La contraseña es requerida');
            isValid = false;
        } else {
            const passwordError = validatePassword(password.val());
            if (passwordError) {
                showValidationError(password, passwordError);
                isValid = false;
            }
        }

        return isValid;
    }

    // Validar formulario de registro
    function validateRegisterForm() {
        let isValid = true;
        const username = $('#registerUsername');
        const password = $('#registerPassword');
        const confirmPassword = $('#confirmPassword');
        const genderInput = $('#genderInput');

        // Limpiar errores previos
        removeValidationError(username);
        removeValidationError(password);
        removeValidationError(confirmPassword);

        // Validar usuario
        if (!username.val().trim()) {
            showValidationError(username, 'El usuario es requerido');
            isValid = false;
        } else {
            const usernameError = validateUsername(username.val().trim());
            if (usernameError) {
                showValidationError(username, usernameError);
                isValid = false;
            }
        }

        // Validar contraseña
        if (!password.val()) {
            showValidationError(password, 'La contraseña es requerida');
            isValid = false;
        } else {
            const passwordError = validatePassword(password.val());
            if (passwordError) {
                showValidationError(password, passwordError);
                isValid = false;
            }
        }

        // Validar confirmación de contraseña
        if (!confirmPassword.val()) {
            showValidationError(confirmPassword, 'Debe confirmar la contraseña');
            isValid = false;
        } else if (password.val() !== confirmPassword.val()) {
            showValidationError(confirmPassword, 'Las contraseñas no coinciden');
            isValid = false;
        }

        // Validar género
        if (!genderInput.val()) {
            // Mostrar error cerca de los botones de género
            const genderButtons = $('.gender-btn').parent();
            if (!genderButtons.next('.invalid-feedback').length) {
                genderButtons.after($('<div>').addClass('invalid-feedback d-block').text('Debe seleccionar un género'));
            }
            isValid = false;
        } else {
            // Remover mensaje de error si existe
            $('.gender-btn').parent().next('.invalid-feedback').remove();
        }

        return isValid;
    }

    // Validación en tiempo real para contraseñas
    $('#registerPassword, #confirmPassword').on('input', function() {
        const password = $('#registerPassword');
        const confirmPassword = $('#confirmPassword');

        // Solo validar si ambos campos tienen contenido
        if (password.val() && confirmPassword.val()) {
            if (password.val() !== confirmPassword.val()) {
                showValidationError(confirmPassword, 'Las contraseñas no coinciden');
            } else {
                removeValidationError(confirmPassword);
            }
        }
    });

    // Manejar animación de tabs
    $('a[data-bs-toggle="tab"]').on('show.bs.tab', function (e) {
        const target = $($(e.target).data('bs-target'));
        const previous = $($(e.relatedTarget).data('bs-target'));

        // Limpiar todos los errores al cambiar de tab
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Animación de salida
        previous.css({
            'position': 'absolute',
            'opacity': '1'
        }).animate({
            'opacity': '0',
            'transform': 'translateX(-100%)'
        }, 300, function() {
            previous.css({
                'position': '',
                'transform': ''
            });
        });

        // Animación de entrada
        target.css({
            'position': 'relative',
            'opacity': '0',
            'transform': 'translateX(100%)'
        }).animate({
            'opacity': '1',
            'transform': 'translateX(0)'
        }, 300);
    });

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
                username: $('#loginUsername').val().trim(),
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
                username: $('#registerUsername').val().trim(),
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
            window.location.href = '/login';
            return;
        }
    }

    // Verificar autenticación al cargar la página
    checkAuth();
});