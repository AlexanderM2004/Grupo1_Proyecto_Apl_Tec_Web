class WhisperPublisher {
    constructor(options = {}) {
        this.form = options.form || document.querySelector('.whisper-form');
        this.messageInput = options.messageInput || document.querySelector('.whisper-box');
        this.tagsInput = options.tagsInput || document.querySelector('.tags-box');
        this.publishButton = options.publishButton || document.querySelector('.btn-publish');
        this.renderer = options.renderer || null;
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Validación en tiempo real del mensaje
        this.messageInput.addEventListener('input', () => {
            const charCount = this.messageInput.value.length;
            if (charCount > 500) {
                this.messageInput.value = this.messageInput.value.slice(0, 500);
                alertManager.warning('El mensaje no puede exceder los 500 caracteres');
            }
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        try {
            // Validaciones básicas
            const message = this.messageInput.value.trim();
            const tags = JSON.parse(this.tagsInput.value || '[]');
            
            if (!message) {
                alertManager.error('El mensaje no puede estar vacío');
                return;
            }
            
            if (!tags.length) {
                alertManager.error('Debes agregar al menos una etiqueta');
                return;
            }
    
            // Obtener token
            const token = this.getToken();
            if (!token) {
                window.location.href = '/login.html';
                return;
            }
    
            // Mostrar loader
            loaderManager.show();
            
            // Enviar susurro
            const response = await fetch('/api/whispers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    mensaje: message,
                    etiquetas: tags
                })
            });
    
            const data = await response.json();
    
            if (data.status === 'success') {
                alertManager.success('¡Susurro publicado exitosamente!');
                this.clearForm();
                
                // Si hay un renderer configurado, recargar los susurros
                if (this.renderer) {
                    await this.renderer.reloadWhispers();
                }
            } else {
                throw new Error(data.message || 'Error al publicar el susurro');
            }
    
        } catch (error) {
            console.error('Error:', error);
            alertManager.error(error.message || 'Error al publicar el susurro');
        } finally {
            loaderManager.hide();
        }
    }

    clearForm() {
        this.messageInput.value = '';
        this.tagsInput.value = '[]';
        
        // Limpiar el contenedor de etiquetas visual
        const tagsContainer = document.querySelector('.tags-container');
        if (tagsContainer) {
            const tagElements = tagsContainer.querySelectorAll('.bg-tags');
            tagElements.forEach(el => el.remove());
        }
    }

    getToken() {
        return document.cookie.split('; ')
            .find(row => row.startsWith('session_data='))
            ?.split('=')[1];
    }
}