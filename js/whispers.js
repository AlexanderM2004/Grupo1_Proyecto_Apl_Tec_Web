class WhisperRenderer {
    constructor(options = {}) {
        this.page = 1;
        this.loading = false;
        this.hasMoreContent = true;
        this.container = options.container || document.querySelector('.whispers-container');
        this.options = {
            infiniteScroll: options.infiniteScroll || false,
            showInterests: options.showInterests || true,
            showLies: options.showLies || true,
            showResponses: options.showResponses || true,
            showViews: options.showViews || false,
            showPercentage: options.showPercentage || true,
            initialLoad: options.initialLoad || 20,
            loadMoreAmount: options.loadMoreAmount || 20,
            endpoint: options.endpoint || '/api/whispers/recent'
        };

        this.loadingTemplate = `
            <div class="loading-skeleton">
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
            </div>
        `;

        if (this.options.infiniteScroll) {
            this.setupInfiniteScroll();
        }
    }

    setupInfiniteScroll() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loading && this.hasMoreContent) {
                    this.loadMore();
                }
            });
        }, { threshold: 0.5 });

        // Crear y observar el elemento centinela
        const sentinel = document.createElement('div');
        sentinel.className = 'scroll-sentinel';
        this.container.appendChild(sentinel);
        observer.observe(sentinel);
    }

    async loadMore() {
        if (this.loading || !this.hasMoreContent) return;

        this.loading = true;
        this.showLoadingIndicator();

        try {
            const response = await fetch(`${this.options.endpoint}?page=${this.page}&limit=${this.options.loadMoreAmount}`);
            const data = await response.json();

            if (!data.data.whispers || data.data.whispers.length === 0) {
                this.hasMoreContent = false;
                this.hideLoadingIndicator();
                return;
            }

            this.renderWhispers(data.data.whispers);
            this.page++;

            if (data.data.whispers.length < this.options.loadMoreAmount) {
                this.hasMoreContent = false;
            }
        } catch (error) {
            console.error('Error cargando susurros:', error);
            alertManager.error('Error al cargar más susurros');
        } finally {
            this.loading = false;
            this.hideLoadingIndicator();
        }
    }

    showLoadingIndicator() {
        // Solo mostrar si no existe ya
        if (!this.container.querySelector('.loading-skeleton')) {
            const loadingElement = document.createElement('div');
            loadingElement.className = 'loading-skeleton';
            loadingElement.innerHTML = this.loadingTemplate;
            this.container.appendChild(loadingElement);

            // Iniciar animación de pulso
            this.pulseAnimation = setInterval(() => {
                const skeletons = loadingElement.querySelectorAll('.skeleton-line');
                skeletons.forEach(skeleton => {
                    skeleton.style.opacity = (Math.sin(Date.now() / 500) + 1) / 2 * 0.5 + 0.3;
                });
            }, 50);
        }
    }

    hideLoadingIndicator() {
        const loadingElement = this.container.querySelector('.loading-skeleton');
        if (loadingElement) {
            loadingElement.remove();
            if (this.pulseAnimation) {
                clearInterval(this.pulseAnimation);
            }
        }
    }

    renderWhispers(whispers) {
        whispers.forEach((whisper, index) => {
            const whisperElement = this.createWhisperElement(whisper);
            // Aplicar estilo inicial para la animación
            whisperElement.style.opacity = '0';
            whisperElement.style.transform = 'translateY(20px)';

            // Insertar al final del contenedor
            this.container.appendChild(whisperElement);

            // Aplicar la animación con delay basado en el índice
            setTimeout(() => {
                whisperElement.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                whisperElement.style.opacity = '1';
                whisperElement.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Siempre mantener el sentinel al final
        if (this.options.infiniteScroll) {
            const sentinel = this.container.querySelector('.scroll-sentinel');
            if (sentinel) {
                this.container.appendChild(sentinel);
            }
        }
    }

    createWhisperElement(whisper) {
        const element = document.createElement('div');
        element.className = 'p-3 bg-content rounded mb-3 conte';

        const genderBackgroundColor = {
            'M': 'var(--comment-gener-male)',
            'F': 'var(--pink)',
            'O': 'var(--purple)'
        };

        const calculateInterestPercentage = (whisper) => {
            if (!whisper.me_interesa || whisper.me_interesa === 0) return 0;
            const ratio = (whisper.me_interesa / (whisper.me_interesa + whisper.es_mentira)) * 100;
            return Math.round(ratio);
        };

        const isHot = calculateInterestPercentage(whisper) >= 70 && whisper.vistas >= 100;

        let html = `
            <div class="mx-2 row">
                <div class="col-8">
                    <div class="row gap-2">
                        <div style="background: ${genderBackgroundColor[whisper.genero]};" class="w-auto rounded-pill">
                            ${this.getGenderIcon(whisper.genero)}
                            ${whisper.username}
                        </div>
                        ${isHot ? '<div style="background: var(--comment-btn-fire-back);" class="w-auto rounded-pill">🔥Hot</div>' : ''}
                    </div>
                </div>
                <div class="col-4">
                    <p class="small text-end m-0 muted">${this.timeAgo(whisper.fecha_creacion)}</p>
                </div>
            </div>
            <p class="m-2 whisper-message text-truncate-3">${this.escapeHtml(whisper.mensaje)}</p>
        `;

        // Agregar etiquetas si existen
        if (whisper.etiquetas) {
            let tags = [];
            // Si las etiquetas vienen como string, convertirlas a array
            if (typeof whisper.etiquetas === 'string') {
                // Eliminar los caracteres '{' y '}' y dividir por comas
                tags = whisper.etiquetas
                    .replace('{', '')
                    .replace('}', '')
                    .split(',')
                    .filter(tag => tag && tag.trim()); // Filtrar valores vacíos
            } else if (Array.isArray(whisper.etiquetas)) {
                tags = whisper.etiquetas;
            }

            if (tags.length > 0) {
                html += '<div class="row d-flex mx-2">';
                tags.forEach(tag => {
                    if (tag) {
                        // Limpiar la etiqueta de posibles espacios o comillas
                        const cleanTag = tag.trim().replace(/"/g, '');
                        if (cleanTag) {
                            html += `<div class="bg-tags p-1 rounded m-1 w-auto">${cleanTag}</div>`;
                        }
                    }
                });
                html += '</div>';
            }
        }

        // Agregar sección de estadísticas según las opciones
        html += '<div class="row d-flex gap-2 mx-2">';

        if (this.options.showInterests) {
            html += `
                <div style="background: var(--comment-btn-fire-back);" class="w-auto rounded-pill mt-2 reacc pointer d-flex align-items-center position-relative">
                    <span class="icono position-absolute">🔥</span>
                    <span class="ms-4">${whisper.me_interesa}</span>
                </div>
            `;
        }

        if (this.options.showLies) {
            html += `
                <div style="background: var(--orange);" class="w-auto rounded-pill mt-2 reacc pointer d-flex align-items-center position-relative">
                    <span class="icono position-absolute">🤥</span>
                    <span class="ms-4">${whisper.es_mentira}</span>
                </div>
            `;
        }

        if (this.options.showResponses) {
            html += `<div class="w-auto mt-2">📧 ${whisper.respuestas_count}</div>`;
        }

        if (this.options.showViews) {
            html += `<div class="w-auto mt-2">👁️ ${whisper.vistas}</div>`;
        }

        if (this.options.showPercentage) {
            html += `<div class="w-auto mt-2">📊 ${calculateInterestPercentage(whisper)}%</div>`;
        }

        html += '</div>';
        element.innerHTML = html;

        // Agregar event listeners para las reacciones
        this.setupReactionListeners(element, whisper.id);

        return element;
    }

    getGenderIcon(gender) {
        const icons = {
            'M': '<svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000"><path d="M14.2323 9.74707C13.1474 8.66733 11.6516 8 10 8C6.68629 8 4 10.6863 4 14C4 17.3137 6.68629 20 10 20C13.3137 20 16 17.3137 16 14C16 12.3379 15.3242 10.8337 14.2323 9.74707ZM14.2323 9.74707L20 4M20 4H16M20 4V8" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            'F': '<svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000"><path d="M12 15C15.3137 15 18 12.3137 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9C6 12.3137 8.68629 15 12 15ZM12 15V19M12 21V19M12 19H10M12 19H14" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            'O': '<svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000"><path d="M5 20V19C5 15.134 8.13401 12 12 12V12C15.866 12 19 15.134 19 19V20" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 12C14.2091 12 16 10.2091 16 8C16 5.79086 14.2091 4 12 4C9.79086 4 8 5.79086 8 8C8 10.2091 9.79086 12 12 12Z" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>'
        };
        return icons[gender] || icons['O'];
    }

    setupReactionListeners(element, whisperId) {
        const meInteresa = element.querySelector('.reacc:first-child');
        const esMentira = element.querySelector('.reacc:nth-child(2)');

        if (meInteresa) {
            meInteresa.addEventListener('click', () => {
                this.animateReaction(meInteresa);
                this.updateReaction(whisperId, 'me_interesa', meInteresa);
            });
        }
        if (esMentira) {
            esMentira.addEventListener('click', () => {
                this.animateReaction(esMentira);
                this.updateReaction(whisperId, 'es_mentira', esMentira);
            });
        }
    }

    async updateReaction(whisperId, type, element) {
        try {
            const response = await fetch('/api/whispers/reaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getToken()}`
                },
                body: JSON.stringify({ whisper_id: whisperId, type: type })
            });

            if (!response.ok) {
                // Si hay error, revertir el incremento
                const countElement = element.querySelector('.ms-4');
                if (countElement) {
                    const currentValue = parseInt(countElement.textContent);
                    countElement.textContent = currentValue - 1;
                }
                throw new Error('Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('Error al actualizar reacción:', error);
            alertManager.error('Error al actualizar reacción');
        }
    }

    getToken() {
        return document.cookie.split('; ')
            .find(row => row.startsWith('session_data='))
            ?.split('=')[1];
    }

    timeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
            año: 31536000,
            mes: 2592000,
            semana: 604800,
            día: 86400,
            hora: 3600,
            minuto: 60
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `Hace ${interval} ${unit}${interval !== 1 ? 's' : ''}`;
            }
        }

        return 'Hace un momento';
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    animateReaction(element) {
        const iconElement = element.querySelector('.icono');
        const countElement = element.querySelector('.ms-4');

        // Animar el contenedor
        element.style.transform = 'scale(1.1)';
        setTimeout(() => {
            element.style.transform = 'scale(1.05)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 100);
        }, 150);

        // Animar el ícono
        if (iconElement) {
            let currentSize = 16;
            const animate = () => {
                currentSize += 6;
                iconElement.style.fontSize = `${currentSize}px`;
                if (currentSize < 22) {
                    requestAnimationFrame(animate);
                } else {
                    // Regresar al tamaño original
                    setTimeout(() => {
                        iconElement.style.transition = 'font-size 0.2s ease';
                        iconElement.style.fontSize = '16px';
                        setTimeout(() => {
                            iconElement.style.transition = '';
                        }, 200);
                    }, 200);
                }
            };
            animate();
        }

        // Incrementar y animar el contador
        if (countElement) {
            const currentValue = parseInt(countElement.textContent);
            countElement.textContent = currentValue + 1;
            countElement.style.transform = 'scale(1.2)';
            setTimeout(() => {
                countElement.style.transform = 'scale(1)';
            }, 200);
        }
    }

    async reloadWhispers() {
        // Reiniciar el estado
        this.page = 1;
        this.hasMoreContent = true;

        // Limpiar contenido actual
        while (this.container.firstChild) {
            this.container.removeChild(this.container.firstChild);
        }

        // Recargar susurros
        await this.loadMore();
    }

    async loadFeaturedWhispers() {
        try {
            const response = await fetch('/api/whispers/popular');
            const data = await response.json();

            if (!data.data || !data.data.whispers) {
                console.error('No featured whispers data found');
                return;
            }

            const featuredContainer = document.querySelector('#popular-whispers-loop');
            if (featuredContainer) {
                featuredContainer.innerHTML = data.data.whispers
                    .map(whisper => {
                        const interesPercentage = whisper.me_interesa > 0 ?
                            Math.round((whisper.me_interesa / (whisper.me_interesa + whisper.es_mentira)) * 100) : 0;

                        return `
                        <div style="background: var(--background-link-profile);" class="row rounded p-3 m-1 mb-3 dest">
                            <p class="small text-center">${this.timeAgo(whisper.fecha_creacion)}</p>
                            ${this.escapeHtml(whisper.mensaje.substring(0, 50))}...
                            <div class="row d-flex gap-2">
                                <div style="background: var(--comment-btn-fire-back);" class="w-auto rounded-pill mt-2 reacc pointer d-flex align-items-center position-relative">
                                    <span class="icono position-absolute">🔥</span>
                                    <span class="ms-4">${whisper.me_interesa}</span>
                                </div>
                                <div style="background: var(--green);" class="w-auto rounded-pill mt-2 reacc pointer d-flex align-items-center position-relative">
                                    <span class="icono position-absolute">🤑</span>
                                    <span class="ms-4">${interesPercentage}%</span>
                                </div>
                            </div>
                        </div>
                    `})
                    .join('');
            }
        } catch (error) {
            console.error('Error loading featured whispers:', error);
            alertManager.error('Error al cargar los susurros destacados');
        }
    }

    async loadPopularTags() {
        try {
            const response = await fetch('/api/whispers/tags');
            const data = await response.json();

            if (!data.data || !data.data.tags) {
                console.error('No tags data found');
                return;
            }

            const tagsContainer = document.querySelector('#tags-loop');
            if (tagsContainer) {
                tagsContainer.innerHTML = data.data.tags
                    .map(tag => `
                        <li>
                            <div class="bg-tags p-1 rounded m-1 tarj">
                                ${tag.nombre}
                                <small>(${tag.uso_count})</small>
                            </div>
                        </li>`)
                    .join('');
            }
        } catch (error) {
            console.error('Error loading popular tags:', error);
            alertManager.error('Error al cargar las etiquetas populares');
        }
    }

    // Add this new method to the WhisperRenderer class
    async loadRecentWhispers() {
        try {
            const response = await fetch('/api/whispers/recent?limit=5');
            const data = await response.json();

            if (!data.data || !data.data.whispers) {
                console.error('No recent whispers data found');
                return;
            }

            const recentContainer = document.querySelector('#recent-whispers-loop');
            if (recentContainer) {
                recentContainer.innerHTML = data.data.whispers
                    .map(whisper => `
                        <div style="background: var(--background-link-profile);" class="row rounded p-3 m-1 mb-3 dest">
                            <p class="small text-center">${this.timeAgo(whisper.fecha_creacion)}</p>
                            ${this.escapeHtml(whisper.mensaje.substring(0, 50))}...
                            <div class="row d-flex gap-2">
                                <div style="background: var(--comment-btn-fire-back);" class="w-auto rounded-pill mt-2">
                                    🔥 ${whisper.me_interesa}
                                </div>
                                <div class="w-auto mt-2">
                                    📧 ${whisper.respuestas_count}
                                </div>
                            </div>
                        </div>
                    `)
                    .join('');
            }
        } catch (error) {
            console.error('Error loading recent whispers:', error);
            alertManager.error('Error al cargar los susurros recientes');
        }
    }
}
