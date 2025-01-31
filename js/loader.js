class LoaderManager {
    constructor() {
        this.init();
    }

    init() {
        // Crear el overlay del loader si no existe
        if (!document.querySelector('.loader-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'loader-overlay';
            
            const loader = document.createElement('span');
            loader.className = 'loader';
            
            overlay.appendChild(loader);
            document.body.appendChild(overlay);
        }
    }

    show() {
        const overlay = document.querySelector('.loader-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    }

    hide() {
        const overlay = document.querySelector('.loader-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}

// Crear instancia global
const loaderManager = new LoaderManager();