function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

$(document).ready(function() {
    if (!window.location.pathname.includes('/login')) {
        initializeUserProfile();
    }
});

function initializeUserProfile() {
    const token = getCookie('session_data');
    if (!token) {
        handleAuthError();
        return;
    }

    try {
        const [, payloadBase64] = token.split('.');
        const payload = JSON.parse(atob(payloadBase64));
        
        if (payload.exp && payload.exp * 1000 < Date.now()) {
            handleAuthError();
            return;
        }

        updateUserInterface(payload);
        
    } catch (e) {
        console.error('Error al procesar el token:', e);
        handleAuthError();
    }
}

function updateUserInterface(userData) {
    // Actualizar el "Hola, usuario"
    $('.user-display-name').text(`Hola, ${userData.username}`);
    
    // Actualizar el @usuario con el ícono de género
    const userHandleWithGender = `
        <div class="d-flex align-items-center justify-content-center" style="gap: 0.35rem;">
            <span>@${userData.username}</span>
            ${getGenderIcon(userData.genero)}
        </div>
    `;
    $('.user-display-handle').html(userHandleWithGender);
}

function handleAuthError() {
    if (!window.location.pathname.includes('/login')) {
        document.cookie = 'session_data=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        window.location.href = '/login';
    }
}

function getGenderIcon(gender) {
    const icons = {
        'M': `<div style="background: var(--comment-gener-male);padding: 4px;" 
              class="rounded-circle d-flex align-items-center justify-content-center" 
              style="width: 20px; height: 20px; min-width: 20px; padding: 4px;">
                <svg width="16px" height="16px" stroke-width="2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                    <path d="M14.2323 9.74707C13.1474 8.66733 11.6516 8 10 8C6.68629 8 4 10.6863 4 14C4 17.3137 6.68629 20 10 20C13.3137 20 16 17.3137 16 14C16 12.3379 15.3242 10.8337 14.2323 9.74707ZM14.2323 9.74707L20 4M20 4H16M20 4V8" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>`,
        'F': `<div style="background: var(--pink);" 
              class="rounded-circle d-flex align-items-center justify-content-center" 
              style="width: 20px; height: 20px; min-width: 20px; padding: 4px;">
                <svg width="16px" height="16px" stroke-width="2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                    <path d="M12 15C15.3137 15 18 12.3137 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9C6 12.3137 8.68629 15 12 15ZM12 15V19M12 21V19M12 19H10M12 19H14" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>`,
        'O': `<div style="background: var(--purple);" 
              class="rounded-circle d-flex align-items-center justify-content-center" 
              style="width: 20px; height: 20px; min-width: 20px; padding: 4px;">
                <svg width="16px" height="16px" stroke-width="2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                    <path d="M5 20V19C5 15.134 8.13401 12 12 12V12C15.866 12 19 15.134 19 19V20" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M12 12C14.2091 12 16 10.2091 16 8C16 5.79086 14.2091 4 12 4C9.79086 4 8 5.79086 8 8C8 10.2091 9.79086 12 12 12Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>`
    };
    
    return icons[gender] || icons['O'];
}