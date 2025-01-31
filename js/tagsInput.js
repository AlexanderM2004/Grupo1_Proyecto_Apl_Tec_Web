class TagsInput {
    constructor(inputElement) {
        if (!inputElement || inputElement.dataset.initialized === 'true') {
            return;
        }

        this.originalInput = inputElement;
        this.tags = [];
        this.setupUI();
        this.setupEventListeners();
        
        this.originalInput.dataset.initialized = 'true';
    }

    setupUI() {
        // Crear el contenedor principal
        this.container = document.createElement('div');
        this.container.className = 'tags-container bg-search rounded p-2 d-flex flex-wrap gap-2 align-items-center';
        
        // Crear el input para escribir tags
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'tag-input border-0 bg-transparent text-white';
        this.input.style.outline = 'none';
        this.input.style.minWidth = '60px';
        this.input.style.width = 'auto';
        this.input.placeholder = this.originalInput.placeholder;

        // Ocultar el input original
        this.originalInput.style.display = 'none';
        
        // Insertar los nuevos elementos
        this.container.appendChild(this.input);

        // Crear o encontrar el wrapper
        let wrapper = document.getElementById('tags-wrapper');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.id = 'tags-wrapper';
            this.originalInput.parentNode.insertBefore(wrapper, this.originalInput);
        }

        // Limpiar y actualizar el wrapper
        wrapper.innerHTML = '';
        wrapper.appendChild(this.container);
        wrapper.appendChild(this.originalInput);
    }

    setupEventListeners() {
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !this.input.value && this.tags.length > 0) {
                this.removeTag(this.tags.length - 1);
            }
        });

        this.input.addEventListener('keyup', (e) => {
            const value = this.input.value.trim();
            if (!value) return;

            // Agregar tag al presionar espacio, coma o Enter
            if (e.key === ' ' || e.key === ',' || e.key === 'Enter') {
                e.preventDefault();
                const tagText = value.replace(/,/g, '').trim();
                if (tagText) {
                    this.addTag(tagText);
                }
                this.input.value = '';
            }
        });

        this.input.addEventListener('input', () => {
            this.input.style.width = (this.input.value.length + 1) * 8 + 'px';
        });
    }

    addTag(text) {
        if (!text.startsWith('#')) {
            text = '#' + text;
        }

        // Verificar si el tag ya existe
        if (this.tags.includes(text)) return;

        this.tags.push(text);
        
        const tagElement = document.createElement('div');
        tagElement.className = 'bg-tags rounded p-1 d-flex align-items-center gap-2';
        
        const tagText = document.createElement('span');
        tagText.textContent = text;
        tagElement.appendChild(tagText);

        const removeButton = document.createElement('span');
        removeButton.innerHTML = '×';
        removeButton.className = 'remove-tag cursor-pointer';
        removeButton.style.cursor = 'pointer';
        removeButton.style.paddingLeft = '4px';
        removeButton.style.paddingRight = '4px';
        removeButton.addEventListener('click', () => {
            const index = this.tags.indexOf(text);
            if (index > -1) {
                this.removeTag(index);
            }
        });
        
        tagElement.appendChild(removeButton);
        this.container.insertBefore(tagElement, this.input);
        
        this.updateOriginalInput();
    }

    removeTag(index) {
        if (index < 0 || index >= this.tags.length) return;
        
        this.tags.splice(index, 1);
        const tagElements = this.container.querySelectorAll('.bg-tags');
        if (tagElements[index]) {
            this.container.removeChild(tagElements[index]);
        }
        this.updateOriginalInput();
    }

    updateOriginalInput() {
        this.originalInput.value = JSON.stringify(this.tags);
    }

    getTags() {
        return this.tags;
    }

    clear() {
        this.tags = [];
        const tagElements = this.container.querySelectorAll('.bg-tags');
        tagElements.forEach(el => el.remove());
        this.updateOriginalInput();
    }
}

// Inicializar solo una vez cuando el DOM esté listo
let tagsInputInstance = null;
document.addEventListener('DOMContentLoaded', () => {
    const tagsBox = document.querySelector('.tags-box');
    if (tagsBox && !tagsInputInstance) {
        tagsInputInstance = new TagsInput(tagsBox);
    }
});