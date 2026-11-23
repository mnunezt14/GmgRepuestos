document.addEventListener('DOMContentLoaded', () => {

    // =========================================================
    // Carrusel del banner principal (hero)
    // =========================================================
    (function heroCarousel() {
        const heroCarousel = document.getElementById('heroCarousel');
        if (!heroCarousel) return;

        const slides = heroCarousel.querySelectorAll('.hero-slide');
        const dots = heroCarousel.querySelectorAll('.hero-dot');
        if (slides.length <= 1) return;

        let indiceActual = 0;
        let intervalo = null;

        function mostrarSlide(indice) {
            slides.forEach(s => s.classList.remove('is-active'));
            dots.forEach(d => d.classList.remove('is-active'));
            slides[indice].classList.add('is-active');
            if (dots[indice]) dots[indice].classList.add('is-active');
            indiceActual = indice;
        }

        function siguienteSlide() {
            mostrarSlide((indiceActual + 1) % slides.length);
        }

        function iniciarAutoplay() {
            intervalo = setInterval(siguienteSlide, 5500);
        }

        function detenerAutoplay() {
            clearInterval(intervalo);
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                mostrarSlide(parseInt(dot.dataset.heroIndex, 10));
                detenerAutoplay();
                iniciarAutoplay();
            });
        });

        heroCarousel.addEventListener('mouseenter', detenerAutoplay);
        heroCarousel.addEventListener('mouseleave', iniciarAutoplay);

        iniciarAutoplay();
    })();

    // =========================================================
    // Autocompletado de búsqueda
    // =========================================================
    (function buscadorAutocompletado() {
        const input = document.getElementById('searchInput');
        const contenedor = document.getElementById('searchSugerencias');
        if (!input || !contenedor) return;

        let temporizador = null;
        let indiceResaltado = -1;

        function escapeHTML(texto) {
            return String(texto ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clearTimeout(temporizador);

            if (q.length < 2) {
                ocultarSugerencias();
                return;
            }

            temporizador = setTimeout(() => buscarSugerencias(q), 250);
        });

        input.addEventListener('keydown', (e) => {
            const items = contenedor.querySelectorAll('.search-sugerencia-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                indiceResaltado = Math.min(indiceResaltado + 1, items.length - 1);
                resaltarItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                indiceResaltado = Math.max(indiceResaltado - 1, 0);
                resaltarItem(items);
            } else if (e.key === 'Enter' && indiceResaltado >= 0) {
                e.preventDefault();
                items[indiceResaltado].click();
            } else if (e.key === 'Escape') {
                ocultarSugerencias();
            }
        });

        document.addEventListener('click', (e) => {
            if (!contenedor.contains(e.target) && e.target !== input) {
                ocultarSugerencias();
            }
        });

        // Estados de la búsqueda (E9-H7): cargando, sin resultados y error.
        let numeroBusqueda = 0;

        function mostrarEstado(texto, esError) {
            indiceResaltado = -1;
            const aviso = document.createElement('div');
            aviso.className = 'search-sugerencias-estado' + (esError ? ' error' : '');
            aviso.setAttribute('role', 'status');
            aviso.textContent = texto;
            contenedor.innerHTML = '';
            contenedor.appendChild(aviso);
            contenedor.classList.add('open');
        }

        async function buscarSugerencias(q) {
            const miBusqueda = ++numeroBusqueda;
            mostrarEstado('Buscando...');
            try {
                const resp = await fetch(`backend/buscar_sugerencias.php?q=${encodeURIComponent(q)}`);
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const data = await resp.json();
                if (miBusqueda !== numeroBusqueda) return; // llegó una búsqueda más nueva
                renderSugerencias(data.sugerencias || []);
            } catch (err) {
                if (miBusqueda !== numeroBusqueda) return;
                mostrarEstado('No pudimos buscar en este momento. Inténtalo de nuevo.', true);
            }
        }

        function renderSugerencias(sugerencias) {
            indiceResaltado = -1;
            if (sugerencias.length === 0) {
                mostrarEstado('No encontramos sugerencias. Presiona "Buscar" para ver todos los resultados.');
                return;
            }

            // Todo dato del producto va escapado (CP-06) y cada sugerencia abre su ficha (F2-4).
            let html = '';
            sugerencias.forEach(s => {
                html += `
                    <a href="${escapeHTML(s.url || ('producto.php?id=' + encodeURIComponent(s.id)))}" class="search-sugerencia-item">
                        <img src="${escapeHTML(s.imagen)}" alt="" class="search-sugerencia-img">
                        <div class="search-sugerencia-info">
                            <div class="search-sugerencia-nombre">${escapeHTML(s.nombre)}</div>
                            <div class="search-sugerencia-sku">SKU: ${escapeHTML(s.sku)}${s.modelo ? ' · ' + escapeHTML(s.modelo) : ''}</div>
                        </div>
                        <div class="search-sugerencia-precio">$${Number(s.precio).toLocaleString('es-CL')}</div>
                    </a>
                `;
            });
            contenedor.innerHTML = html;
            // Foto que no carga: la genérica (sin onerror en línea).
            contenedor.querySelectorAll('.search-sugerencia-img').forEach(img => {
                img.addEventListener('error', () => { img.src = 'img/placeholder-repuesto.svg'; }, { once: true });
            });
            contenedor.classList.add('open');
        }

        function resaltarItem(items) {
            items.forEach(i => i.classList.remove('is-resaltado'));
            if (items[indiceResaltado]) {
                items[indiceResaltado].classList.add('is-resaltado');
                items[indiceResaltado].scrollIntoView({ block: 'nearest' });
            }
        }

        function ocultarSugerencias() {
            numeroBusqueda++; // descarta cualquier búsqueda en curso
            contenedor.classList.remove('open');
            contenedor.innerHTML = '';
            indiceResaltado = -1;
        }
    })();

    // =========================================================
    // Aviso de reposición en la tarjeta y la ficha (F3-11, E2-H9, RF-016)
    // =========================================================
    document.addEventListener('submit', async (evento) => {
        const form = evento.target.closest('form.aviso-stock');
        if (!form) return;
        evento.preventDefault();
        const input = form.querySelector('input[name="contacto"]');
        const boton = form.querySelector('button[type="submit"]');
        const mensaje = form.querySelector('.aviso-stock-msg');
        const contacto = input.value.trim();
        const digitos = contacto.replace(/\D/g, '');
        const esCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contacto);
        const esCelular = /^(56)?9\d{8}$/.test(digitos);
        mensaje.classList.remove('is-error', 'is-ok');
        if (!esCorreo && !esCelular) {
            mensaje.textContent = 'Escribe un correo o un celular chileno (por ejemplo +56 9 1234 5678).';
            mensaje.classList.add('is-error');
            input.focus();
            return;
        }
        let sesionId = '';
        try { sesionId = localStorage.getItem('gmg_chat_sesion_id') || ''; } catch (e) { /* sin localStorage */ }
        boton.disabled = true;
        mensaje.textContent = 'Enviando...';
        try {
            const resp = await fetch('backend/registrar_aviso_stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: parseInt(form.dataset.productoId, 10), contacto: contacto, sesion_id: sesionId })
            });
            const data = await resp.json();
            mensaje.textContent = data.mensaje || 'No se pudo registrar el aviso. Intenta de nuevo.';
            if (data.status === 'success') {
                mensaje.classList.add('is-ok');
                input.value = '';
                input.disabled = true;
            } else {
                mensaje.classList.add('is-error');
                boton.disabled = false;
            }
        } catch (e) {
            mensaje.textContent = 'No pudimos registrar el aviso en este momento. Inténtalo de nuevo o escríbenos por WhatsApp.';
            mensaje.classList.add('is-error');
            boton.disabled = false;
        }
    });

    // =========================================================
    // Mini carrusel de imágenes en cada tarjeta de producto
    // (solo aparece si el producto tiene más de una foto cargada)
    // =========================================================
    (function carruselesProducto() {
        document.querySelectorAll('.producto-img-wrap').forEach(wrap => {
            const imagenes = wrap.querySelectorAll('.producto-img');
            const dots = wrap.querySelectorAll('.producto-img-dot');
            const btnPrev = wrap.querySelector('.producto-img-prev');
            const btnNext = wrap.querySelector('.producto-img-next');
            if (imagenes.length <= 1) return;

            let indice = 0;

            function mostrar(nuevoIndice) {
                indice = (nuevoIndice + imagenes.length) % imagenes.length;
                imagenes.forEach(img => img.classList.remove('producto-img-activa'));
                dots.forEach(d => d.classList.remove('is-active'));
                imagenes[indice].classList.add('producto-img-activa');
                if (dots[indice]) dots[indice].classList.add('is-active');
            }

            if (btnPrev) {
                btnPrev.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrar(indice - 1);
                });
            }
            if (btnNext) {
                btnNext.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrar(indice + 1);
                });
            }
            dots.forEach((dot, i) => {
                dot.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrar(i);
                });
            });
        });
    })();

});
