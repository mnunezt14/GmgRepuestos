/**
 * Google Analytics 4 (F4-3, E8-H6, RF-071). Solo mide si el sitio cargó gtag (GA4_ID en el .env);
 * sin ID no hace nada. Eventos: view_item (ficha), add_to_cart y begin_checkout (los llama cart.js),
 * chat_abierto, chat_mensaje (los llama chat.js) y contacto_whatsapp (cualquier enlace a wa.me).
 */
(function () {
    function evento(nombre, datos) {
        if (typeof window.gtag === 'function') {
            window.gtag('event', nombre, datos || {});
        }
    }
    window.gmgEvento = evento;

    // Producto en el formato de GA4 (precio en CLP).
    window.gmgItemAnalitica = function (p, cantidad) {
        return { item_id: String(p.id), item_name: p.nombre, price: Number(p.precio) || 0, quantity: cantidad || 1 };
    };

    document.addEventListener('DOMContentLoaded', function () {
        const ficha = document.querySelector('.ficha-producto[data-ga-item]');
        if (ficha) {
            try {
                const item = JSON.parse(ficha.dataset.gaItem);
                evento('view_item', { currency: 'CLP', value: item.price, items: [item] });
            } catch (e) { /* dato inválido: no se mide */ }
        }
    });

    document.addEventListener('click', function (e) {
        const whatsapp = e.target.closest('a[href*="wa.me/"]');
        if (whatsapp) {
            evento('contacto_whatsapp', { ubicacion: whatsapp.id || whatsapp.className || 'enlace' });
        }
    });
})();
