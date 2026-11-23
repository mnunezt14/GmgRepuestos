/**
 * Carrusel de modelos (portada) - "Selecciona tu modelo Suzuki"
 *
 * Carrusel MANUAL: no tiene avance automático. Se mueve con:
 * - las flechas (una "página" de tarjetas por clic),
 * - los puntos de abajo (salta a una página),
 * - el teclado (flechas izquierda/derecha con el carrusel enfocado),
 * - deslizando con el dedo o el trackpad (scroll nativo del navegador).
 *
 * El desplazamiento y el "encaje" de cada tarjeta lo hace el navegador con
 * CSS scroll-snap (ver css/model-carousel.css); este script solo calcula
 * las páginas, mueve el scroll y actualiza flechas y puntos.
 * JavaScript plano, compatible con navegadores actuales (sin librerías).
 */
(function () {
    'use strict';

    function initModelCarousel(root) {
        if (!root) return;

        var track = root.querySelector('.mc-track');
        var prev = root.querySelector('.mc-nav--prev');
        var next = root.querySelector('.mc-nav--next');
        var dotsWrap = root.querySelector('.mc-dots');
        var cards = Array.prototype.slice.call(root.querySelectorAll('.mc-card'));
        if (!track || cards.length === 0) return;

        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var dots = [];
        var progressFill = null;
        var MAX_PUNTOS = 8; // con más páginas que esto se muestra una barra de avance
        var ticking = false;

        function gap() {
            var estilo = window.getComputedStyle(track);
            return parseFloat(estilo.columnGap || estilo.gap) || 0;
        }

        // Ancho de una "página" = lo que se ve + el espacio entre tarjetas
        function pageWidth() {
            return track.clientWidth + gap();
        }

        function maxScroll() {
            return Math.max(0, track.scrollWidth - track.clientWidth);
        }

        function pageCount() {
            var max = maxScroll();
            if (max <= 1) return 1;
            return Math.ceil(max / pageWidth() - 0.01) + 1;
        }

        function currentPage() {
            var max = maxScroll();
            if (track.scrollLeft >= max - 2) return pageCount() - 1;
            return Math.round(track.scrollLeft / pageWidth());
        }

        function goToPage(n) {
            var destino = Math.min(n * pageWidth(), maxScroll());
            track.scrollTo({ left: destino, behavior: reduceMotion ? 'auto' : 'smooth' });
        }

        function buildDots() {
            if (!dotsWrap) return;
            var total = pageCount();
            dotsWrap.innerHTML = '';
            dots = [];
            progressFill = null;
            if (total <= 1) {
                dotsWrap.hidden = true;
                return;
            }
            dotsWrap.hidden = false;
            if (total > MAX_PUNTOS) {
                var barra = document.createElement('span');
                barra.className = 'mc-progress';
                barra.setAttribute('aria-hidden', 'true');
                progressFill = document.createElement('span');
                progressFill.className = 'mc-progress__fill';
                barra.appendChild(progressFill);
                dotsWrap.appendChild(barra);
                return;
            }
            for (var i = 0; i < total; i++) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'mc-dot';
                dot.setAttribute('aria-label', 'Ir a la página ' + (i + 1) + ' de ' + total);
                (function (pagina) {
                    dot.addEventListener('click', function () { goToPage(pagina); });
                })(i);
                dotsWrap.appendChild(dot);
                dots.push(dot);
            }
        }

        // Centra las flechas a la altura de la foto de las tarjetas
        function positionArrows() {
            var media = cards[0].querySelector('.mc-card__media');
            if (!media) return;
            // offsetTop de la foto es relativo a .model-carousel (position: relative)
            var top = media.offsetTop + media.offsetHeight / 2;
            root.style.setProperty('--mc-nav-top', top + 'px');
            if (prev) prev.style.top = 'calc(var(--mc-nav-top) - ' + (prev.offsetHeight / 2) + 'px)';
            if (next) next.style.top = 'calc(var(--mc-nav-top) - ' + (next.offsetHeight / 2) + 'px)';
        }

        function update() {
            ticking = false;
            var hayScroll = maxScroll() > 1;
            if (prev) {
                prev.hidden = !hayScroll;
                prev.disabled = track.scrollLeft <= 2;
            }
            if (next) {
                next.hidden = !hayScroll;
                next.disabled = track.scrollLeft >= maxScroll() - 2;
            }
            var activa = currentPage();
            if (progressFill) {
                var max = maxScroll();
                var visible = track.clientWidth / track.scrollWidth;
                var avance = max > 0 ? track.scrollLeft / max : 1;
                progressFill.style.width = Math.round((visible + (1 - visible) * avance) * 100) + '%';
            }
            dots.forEach(function (d, i) {
                var esActiva = i === activa;
                d.classList.toggle('is-active', esActiva);
                if (esActiva) {
                    d.setAttribute('aria-current', 'true');
                } else {
                    d.removeAttribute('aria-current');
                }
            });
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        if (prev) {
            prev.addEventListener('click', function () { goToPage(Math.max(0, currentPage() - 1)); });
        }
        if (next) {
            next.addEventListener('click', function () { goToPage(Math.min(pageCount() - 1, currentPage() + 1)); });
        }

        // Teclado: con el carrusel (o una tarjeta) enfocado, las flechas
        // izquierda/derecha avanzan una tarjeta.
        root.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            var paso = cards[0].offsetWidth + gap();
            track.scrollBy({ left: e.key === 'ArrowRight' ? paso : -paso, behavior: reduceMotion ? 'auto' : 'smooth' });
        });

        track.addEventListener('scroll', onScroll, { passive: true });

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                buildDots();
                positionArrows();
                update();
            }, 120);
        });

        buildDots();
        positionArrows();
        update();

        // Las fotos se cargan en diferido: al terminar puede cambiar el alto.
        window.addEventListener('load', function () {
            positionArrows();
            update();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initModelCarousel(document.getElementById('modelCarousel'));
    });
})();
