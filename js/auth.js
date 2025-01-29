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

    // Verificar autenticación
    function checkAuth() {
        const token = getCookie('session_data');
        const currentPath = window.location.pathname;
        
        // Si estamos en login y hay token, redirigir a index
        if ((currentPath === '/login' || currentPath === '/login.html') && token) {
            window.location.href = '/';
            return;
        }
        
        // Si no estamos en login y no hay token, redirigir a login
        if (currentPath !== '/login' && currentPath !== '/login.html' && !token) {
            window.location.href = '/login';
            return;
        }
    }

    // Manejar login
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
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

        // Validar contraseñas
        if ($('#registerPassword').val() !== $('#confirmPassword').val()) {
            alert('Las contraseñas no coinciden');
            return;
        }

        // Validar género seleccionado
        if (!$('#genderInput').val()) {
            alert('Por favor selecciona un género');
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
                pais: 'Ecuador' // Valor por defecto o podrías añadir un campo para esto
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
    checkAuth();
});