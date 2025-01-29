document.addEventListener('DOMContentLoaded', function() {
    const messages = [
        '¡No creerás lo que pasó en el último evento!',
        'La verdad detrás del último rumor...',
        '¿Alguien más escuchó sobre esto?',
        'Secretos que nadie se atreve a contar...',
        'Esto no me lo crei cuando lo vi...'
    ];

    const container = document.querySelector('.floating-messages');

    function createMessage(text, index) {
        const message = document.createElement('div');
        message.className = 'message-bubble bg-sidebar p-3 rounded-3 small';
        message.textContent = text;
        
        // Posición horizontal aleatoria (entre 10% y 60% para evitar desbordamiento)
        const randomX = Math.random() * 30 + 10;
        message.style.left = `${randomX}%`;
        
        // Aseguramos que empiecen desde abajo
        message.style.bottom = '-50px';
        
        // Aplicamos el delay de la animación
        message.style.animationDelay = `${index * 1.6}s`;
        
        return message;
    }

    function initializeMessages() {
        container.innerHTML = ''; // Limpiamos el contenedor
        
        messages.forEach((text, index) => {
            const message = createMessage(text, index);
            container.appendChild(message);
        });
    }

    // Inicializamos los mensajes
    //initializeMessages();
});