class AlertManager {
    constructor() {
        this.init();
    }

    init() {
        if (!document.querySelector('.alert-container')) {
            const container = document.createElement('div');
            container.className = 'alert-container';
            document.body.appendChild(container);
        }
    }

    show(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.alert-container');
        const alert = document.createElement('div');
        alert.className = `custom-alert custom-alert-${type}`;
        
        // Definir icono según el tipo
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        alert.innerHTML = `
            <span class="alert-icon">${icons[type]}</span>
            <span class="alert-message">${message}</span>
            <span class="alert-close">✕</span>
        `;

        container.appendChild(alert);

        // Manejar el cierre manual
        const closeBtn = alert.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => this.close(alert));

        // Cierre automático después de la duración especificada
        setTimeout(() => {
            if (alert.parentNode) {
                this.close(alert);
            }
        }, duration);
    }

    close(alert) {
        alert.classList.add('fade-out');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    }

    success(message, duration) {
        this.show(message, 'success', duration);
    }

    error(message, duration) {
        this.show(message, 'error', duration);
    }

    warning(message, duration) {
        this.show(message, 'warning', duration);
    }

    info(message, duration) {
        this.show(message, 'info', duration);
    }
}

// Crear instancia global
const alertManager = new AlertManager();