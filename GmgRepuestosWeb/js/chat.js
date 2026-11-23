/**
 * Chatbot IA - GMG Repuestos
 * Script frontend para interacción en tiempo real: memoria de conversación
 * persistente, múltiples fotos, checkout guiado, avisos de stock, feedback,
 * dictado por voz y proactividad.
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatToggleBtn = document.getElementById('gmgChatToggle');
    const chatWindow = document.getElementById('gmgChatWindow');
    const chatCloseBtn = document.getElementById('gmgChatClose');
    const chatMessages = document.getElementById('gmgChatMessages');
    const chatInput = document.getElementById('gmgChatInput');
    const chatSendBtn = document.getElementById('gmgChatSend');
    const chatFileInput = document.getElementById('gmgChatFileInput');
    const chatCameraInput = document.getElementById('gmgChatCameraInput'); // cámara del celular
    const chatFileBtn = document.getElementById('gmgChatFileBtn');
    const chatMicBtn = document.getElementById('gmgChatMicBtn');
    const previewBar = document.getElementById('gmgChatPreviewBar');
    const previewList = document.getElementById('gmgChatPreviewList');
    const quickChips = document.querySelectorAll('.gmg-chip');
    const chatAviso = document.getElementById('gmgChatAviso');
    // Cualquier botón con data-abrir-chat (o el de la portada) abre el chat.
    const botonesAbrirChat = document.querySelectorAll('[data-abrir-chat], #gmgHomeChatCTA');

    const MAX_IMAGENES = 3;
    const MAX_BYTES_IMAGEN = 4 * 1024 * 1024;
    const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/webp'];
    const MAX_HISTORIAL_GUARDADO = 24; // turnos guardados en localStorage
    const STORAGE_KEY_SESION = 'gmg_chat_sesion_id';
    const STORAGE_KEY_HISTORIAL = 'gmg_chat_historial';

    let currentSelectedFiles = []; // [{file, dataUrl}]
    let isWaitingResponse = false;
    let chatHasBeenOpened = false;

    // =========================================================
    // 0. Identidad de sesión + memoria persistente del chat
    // =========================================================
    function obtenerOCrearSesionId() {
        try {
            let id = localStorage.getItem(STORAGE_KEY_SESION);
            if (!id) {
                id = (window.crypto && typeof window.crypto.randomUUID === 'function')
                    ? window.crypto.randomUUID()
                    : 'sess_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 12);
                localStorage.setItem(STORAGE_KEY_SESION, id);
            }
            return id;
        } catch (e) {
            return 'sess_' + Date.now().toString(36);
        }
    }
    const sesionId = obtenerOCrearSesionId();

    function cargarHistorialGuardado() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY_HISTORIAL);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function guardarHistorial(historial) {
        try {
            const recortado = historial.slice(-MAX_HISTORIAL_GUARDADO);
            localStorage.setItem(STORAGE_KEY_HISTORIAL, JSON.stringify(recortado));
        } catch (e) {
            // Si localStorage falla (privado, lleno, etc.) simplemente no persiste
        }
    }

    let historialConversacion = cargarHistorialGuardado();

    // Repintar la conversación guardada al cargar la página (sin animación)
    function restaurarConversacion() {
        if (historialConversacion.length === 0) return;
        historialConversacion.forEach(turno => {
            if (turno.rol === 'bot') {
                appendBotMessage(turno.texto, turno.productos || [], turno.mensajeId || null, { animar: false });
            } else {
                appendUserMessage(turno.texto, turno.imagenes || []);
            }
        });
        scrollToBottom();
    }
    restaurarConversacion();

    // =========================================================
    // 1. Abrir y Cerrar Chat
    // =========================================================
    function toggleChat() {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active') && window.gmgEvento) window.gmgEvento('chat_abierto'); // F4-3
        if (chatToggleBtn) chatToggleBtn.setAttribute('aria-expanded', chatWindow.classList.contains('active') ? 'true' : 'false');
        if (chatWindow.classList.contains('active')) {
            chatHasBeenOpened = true;
            ocultarBurbujaProactiva();
            chatInput.focus();
            scrollToBottom();
        }
    }

    function abrirChat() {
        if (!chatWindow.classList.contains('active') && window.gmgEvento) window.gmgEvento('chat_abierto'); // F4-3
        chatWindow.classList.add('active');
        if (chatToggleBtn) chatToggleBtn.setAttribute('aria-expanded', 'true');
        chatHasBeenOpened = true;
        ocultarBurbujaProactiva();
        scrollToBottom();
    }

    if (chatToggleBtn) chatToggleBtn.addEventListener('click', toggleChat);
    if (chatCloseBtn) chatCloseBtn.addEventListener('click', toggleChat);
    botonesAbrirChat.forEach(boton => boton.addEventListener('click', () => {
        abrirChat();
        // Ficha de producto (F2-4): deja escrita la consulta con el nombre y el SKU para que el
        // asistente sepa de qué repuesto se habla; el cliente la completa y la envía.
        const pregunta = boton.dataset.chatPregunta;
        if (pregunta && chatInput && !chatInput.value.trim()) {
            chatInput.value = pregunta;
            chatInput.focus();
            chatInput.setSelectionRange(pregunta.length, pregunta.length);
        }
    }));

    // El botón flotante es un <button> (F4-8): Enter y Espacio funcionan solos, sin código extra.

    // Avisos breves dentro del chat (reemplazan a las ventanas emergentes del navegador).
    let temporizadorAviso = null;
    function mostrarAvisoChat(texto) {
        if (!chatAviso) return;
        chatAviso.textContent = texto;
        chatAviso.hidden = false;
        clearTimeout(temporizadorAviso);
        temporizadorAviso = setTimeout(() => { chatAviso.hidden = true; }, 5000);
    }

    // En celulares (menos de 768 px) el chat ocupa toda la pantalla (E9-H3).
    // Cuando aparece el teclado virtual, la pantalla "visible" se achica: se
    // ajusta la altura del chat a esa zona para que el campo de texto quede
    // siempre visible por encima del teclado.
    function ajustarChatAlTeclado() {
        if (!chatWindow) return;
        const vv = window.visualViewport;
        const esMovil = window.innerWidth < 768;
        if (!vv || !esMovil || !chatWindow.classList.contains('active')) {
            chatWindow.style.removeProperty('height');
            chatWindow.style.removeProperty('top');
            return;
        }
        chatWindow.style.height = vv.height + 'px';
        chatWindow.style.top = vv.offsetTop + 'px';
        scrollToBottom();
    }
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', ajustarChatAlTeclado);
        window.visualViewport.addEventListener('scroll', ajustarChatAlTeclado);
    }
    window.addEventListener('resize', ajustarChatAlTeclado);
    if (chatToggleBtn) chatToggleBtn.addEventListener('click', () => setTimeout(ajustarChatAlTeclado, 0));
    if (chatCloseBtn) chatCloseBtn.addEventListener('click', () => setTimeout(ajustarChatAlTeclado, 0));
    botonesAbrirChat.forEach(boton => boton.addEventListener('click', () => setTimeout(ajustarChatAlTeclado, 0)));

    // Minimizar el chat (sin perder la conversación) cuando el carrito necesita
    // ocupar el mismo espacio en pantalla.
    window.gmgChatMinimize = function() {
        if (chatWindow) chatWindow.classList.remove('active');
    };

    // =========================================================
    // 2. Manejo de fotos adjuntas (hasta 3)
    // =========================================================
    if (chatFileBtn) {
        chatFileBtn.addEventListener('click', () => {
            chatFileInput.click();
        });
    }

    // Las fotos del celular pesan 3 a 8 MB y el servidor acepta hasta 4 MB (F1-8): antes de adjuntarlas
    // se reducen a 1600 px por lado en JPEG (unos cientos de KB, suben rápido con datos móviles).
    // Una foto chica se deja tal cual. (Con accept de JPG, PNG y WebP, el iPhone ya entrega sus fotos en JPEG.)
    const LADO_MAXIMO = 1600;
    function reducirFoto(file) {
        const yaSirve = TIPOS_IMAGEN.includes(file.type) && file.size <= 1.5 * 1024 * 1024;
        if (yaSirve) return Promise.resolve(file);
        return new Promise((resolver) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                const escala = Math.min(1, LADO_MAXIMO / Math.max(img.naturalWidth, img.naturalHeight));
                const lienzo = document.createElement('canvas');
                lienzo.width = Math.round(img.naturalWidth * escala);
                lienzo.height = Math.round(img.naturalHeight * escala);
                lienzo.getContext('2d').drawImage(img, 0, 0, lienzo.width, lienzo.height);
                URL.revokeObjectURL(url);
                lienzo.toBlob((blob) => {
                    resolver(blob ? new File([blob], (file.name || 'foto').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }) : null);
                }, 'image/jpeg', 0.85);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolver(null); }; // formato que el navegador no puede abrir
            img.src = url;
        });
    }

    async function agregarFotos(input) {
        const files = Array.from(input.files || []);
        input.value = '';
        let agregadas = 0;
        let tipoInvalido = false;
        let noSeAbrio = false;
        for (const original of files) {
            if (currentSelectedFiles.length >= MAX_IMAGENES) break;
            // Lo mismo que revisa el servidor (F1-8): solo JPG, PNG o WebP, y de hasta 4 MB después de reducirla.
            if (!TIPOS_IMAGEN.includes(original.type)) {
                tipoInvalido = true;
                continue;
            }
            const file = await reducirFoto(original);
            if (!file || file.size > MAX_BYTES_IMAGEN) {
                noSeAbrio = true;
                continue;
            }
            currentSelectedFiles.push({ file, dataUrl: null });
            agregadas++;
        }
        if (tipoInvalido) {
            mostrarAvisoChat('Solo puedes adjuntar fotos JPG, PNG o WebP.');
        } else if (noSeAbrio) {
            mostrarAvisoChat('No pudimos abrir esa foto. Prueba con otra o sácala de nuevo.');
        } else if (files.length > agregadas) {
            mostrarAvisoChat('Puedes adjuntar un máximo de ' + MAX_IMAGENES + ' fotos por mensaje.');
        }
        renderPreviewList();
    }

    if (chatFileInput) chatFileInput.addEventListener('change', () => agregarFotos(chatFileInput));
    if (chatCameraInput) chatCameraInput.addEventListener('change', () => agregarFotos(chatCameraInput));

    function renderPreviewList() {
        if (!previewList) return;
        previewList.innerHTML = '';

        if (currentSelectedFiles.length === 0) {
            previewBar.classList.remove('has-images');
            return;
        }
        previewBar.classList.add('has-images');

        currentSelectedFiles.forEach((item, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'gmg-chat-preview-item';

            const img = document.createElement('img');
            img.className = 'gmg-chat-preview-thumb';
            wrap.appendChild(img);

            if (item.dataUrl) {
                img.src = item.dataUrl;
            } else {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    item.dataUrl = ev.target.result;
                    img.src = item.dataUrl;
                };
                reader.readAsDataURL(item.file);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'gmg-chat-preview-remove';
            removeBtn.title = 'Quitar imagen';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', () => {
                currentSelectedFiles.splice(idx, 1);
                renderPreviewList();
            });
            wrap.appendChild(removeBtn);

            previewList.appendChild(wrap);
        });
    }

    function limpiarImagenesSeleccionadas() {
        currentSelectedFiles = [];
        chatFileInput.value = '';
        renderPreviewList();
    }

    // =========================================================
    // 3. Chips de sugerencias rápidas
    // =========================================================
    quickChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const promptText = chip.getAttribute('data-prompt');
            if (promptText) {
                if (chip.getAttribute('data-action') === 'upload') {
                    (chatCameraInput || chatFileInput).click(); // en el celular abre la cámara
                } else {
                    // Los botones con data-atajo (Buscar repuesto, Despacho, Medios de pago)
                    // reciben una respuesta inmediata con la información configurada.
                    enviarMensaje(promptText, chip.getAttribute('data-atajo') || '');
                }
            }
        });
    });

    // =========================================================
    // 4. Eventos de Envío
    // =========================================================
    if (chatSendBtn) {
        chatSendBtn.addEventListener('click', () => {
            const text = chatInput.value.trim();
            if (text || currentSelectedFiles.length > 0) {
                enviarMensaje(text);
            }
        });
    }

    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const text = chatInput.value.trim();
                if (text || currentSelectedFiles.length > 0) {
                    enviarMensaje(text);
                }
            }
        });
    }

    // =========================================================
    // 5. Función principal de Envío
    // =========================================================
    async function enviarMensaje(texto, atajo) {
        if (isWaitingResponse) return;
        if (window.gmgEvento) window.gmgEvento('chat_mensaje', { atajo: atajo || '' }); // F4-3 (no se envía el texto)

        const filesToSend = currentSelectedFiles.slice();
        const dataUrlsToSend = filesToSend.map(f => f.dataUrl).filter(Boolean);

        chatInput.value = '';
        limpiarImagenesSeleccionadas();

        appendUserMessage(texto, dataUrlsToSend);
        registrarTurnoHistorial('user', texto, [], null, dataUrlsToSend);

        showTypingIndicator();
        isWaitingResponse = true;

        // Si se abre localmente con protocolo file:// sin servidor PHP activo
        if (window.location.protocol === 'file:') {
            setTimeout(() => {
                removeTypingIndicator();
                const sim = simularRespuestaCliente(texto, filesToSend.length > 0);
                appendBotMessage(sim.reply, sim.products, null, { animar: true });
                registrarTurnoHistorial('bot', sim.reply, sim.products, null);
                isWaitingResponse = false;
            }, 650);
            return;
        }

        const formData = new FormData();
        if (texto) formData.append('message', texto);
        filesToSend.forEach(item => formData.append('images[]', item.file));
        formData.append('sesion_id', sesionId);
        if (atajo) formData.append('atajo', atajo);
        // El historial ya no se envía: el servidor lo lee de la base (F1-4, INC-05).

        // Si el servidor no responde en 30 s se corta la espera (el servidor ya se limita a 25 s).
        const controlador = new AbortController();
        const limiteEspera = setTimeout(() => controlador.abort(), 30000);

        try {
            const response = await fetch('backend/chat.php', {
                method: 'POST',
                body: formData,
                signal: controlador.signal
            });

            if (!response.ok) {
                throw new Error(`Error de servidor: ${response.status}`);
            }

            const data = await response.json();
            removeTypingIndicator();

            if (data.status === 'success') {
                appendBotMessage(data.reply, data.products || [], data.mensaje_id || null, { animar: true });
                registrarTurnoHistorial('bot', data.reply, data.products || [], data.mensaje_id || null);

                if (data.auto_add_cart && data.auto_add_cart.length > 0 && window.addToCartFromChat) {
                    data.auto_add_cart.forEach(item => {
                        window.addToCartFromChat({
                            id: item.id,
                            nombre: item.nombre,
                            precio: parseFloat(item.precio),
                            imagen: item.imagen_url,
                            stock: item.stock === null || item.stock === undefined ? NaN : parseInt(item.stock, 10)
                        }, item.quantity);
                    });
                }

                if (data.start_checkout) {
                    manejarInicioCheckout();
                }

                if (data.notificar_stock_producto_id) {
                    mostrarFormularioAvisoStock(data.notificar_stock_producto_id);
                }
            } else {
                appendBotMessage(data.reply || 'Disculpa, no pude procesar tu solicitud.', [], null, { animar: true });
            }
        } catch (error) {
            console.error('Error al contactar con el backend (chat.php):', error);
            removeTypingIndicator();
            const enlaceWhatsApp = chatWindow && chatWindow.dataset.whatsapp
                ? ' [escríbenos por WhatsApp](' + chatWindow.dataset.whatsapp + ')'
                : ' escríbenos por WhatsApp';
            const aviso = error.name === 'AbortError'
                ? 'La respuesta está tardando más de lo normal. Inténtalo de nuevo en un momento o' + enlaceWhatsApp + '.'
                : '⚠️ No pude conectarme en este momento. Revisa tu conexión a Internet e inténtalo de nuevo. Si el problema sigue,' + enlaceWhatsApp + '.';
            appendBotMessage(aviso, [], null, { animar: false });
        } finally {
            clearTimeout(limiteEspera);
            isWaitingResponse = false;
            if (chatInput) chatInput.focus();
        }
    }

    // Seguir por WhatsApp con un resumen de la conversación (F3-6, E5-H7, RF-042):
    // lo que preguntó el cliente (modelo, año, repuesto) y los repuestos que le mostró el asistente.
    function resumenWhatsApp() {
        const preguntas = historialConversacion.filter(t => t.rol === 'user' && t.texto).slice(-3).map(t => t.texto.trim());
        const conProductos = historialConversacion.filter(t => t.rol === 'bot' && Array.isArray(t.productos) && t.productos.length).slice(-1)[0];
        let texto = 'Hola, vengo del asistente de la página web.';
        if (preguntas.length) texto += '\nMi consulta: ' + preguntas.join(' / ').slice(0, 400);
        if (conProductos) {
            texto += '\nRepuestos que me mostró: ' + conProductos.productos.slice(0, 3)
                .map(p => p.nombre + (p.sku ? ' (SKU ' + p.sku + ')' : '')).join('; ');
        }
        return texto;
    }
    const chatWhatsappBtn = document.getElementById('gmgChatWhatsapp');
    if (chatWhatsappBtn) {
        chatWhatsappBtn.addEventListener('click', () => {
            const base = chatWhatsappBtn.href.split('?')[0];
            chatWhatsappBtn.href = base + '?text=' + encodeURIComponent(resumenWhatsApp());
        });
    }

    function registrarTurnoHistorial(rol, texto, productos, mensajeId, imagenes) {
        historialConversacion.push({
            rol: rol,
            texto: texto || '',
            productos: productos || [],
            mensajeId: mensajeId || null,
            imagenes: imagenes || [],
            hora: Date.now()
        });
        guardarHistorial(historialConversacion);
    }

    function manejarInicioCheckout() {
        let pudoAbrir = false;
        if (window.gmgIniciarCheckoutDesdeChat) {
            pudoAbrir = window.gmgIniciarCheckoutDesdeChat();
        }
        if (!pudoAbrir) {
            appendBotMessage('Antes de pagar necesitas tener al menos un producto en tu carrito. ¡Agrégalo y vuelve a decirme cuando quieras pagar! 🛒', [], null, { animar: true });
        }
    }

    // =========================================================
    // 6. Aviso de stock (etiqueta [NOTIFICAR_STOCK:ID] del bot)
    // =========================================================
    function mostrarFormularioAvisoStock(productoId) {
        const wrap = document.createElement('div');
        wrap.className = 'gmg-msg gmg-msg-bot';
        wrap.innerHTML = `
            <div class="gmg-msg-bubble">
                <p>Déjame tu WhatsApp o correo y te aviso apenas vuelva a haber stock:</p>
                <div style="display:flex; gap:6px; margin-top:6px;">
                    <input type="text" class="gmg-chat-input" style="flex:1;" placeholder="Ej: +56 9 1234 5678" data-aviso-contacto>
                    <button type="button" class="gmg-chat-send-btn" data-aviso-enviar title="Enviar">✓</button>
                </div>
            </div>
            <span class="gmg-msg-time">${obtenerHoraActual()}</span>
        `;
        chatMessages.appendChild(wrap);
        scrollToBottom();

        const input = wrap.querySelector('[data-aviso-contacto]');
        const btn = wrap.querySelector('[data-aviso-enviar]');

        async function enviarAviso() {
            const contacto = input.value.trim();
            if (!contacto) { input.focus(); return; }
            btn.disabled = true;
            try {
                const resp = await fetch('backend/registrar_aviso_stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ producto_id: productoId, contacto: contacto, sesion_id: sesionId })
                });
                const data = await resp.json();
                wrap.querySelector('.gmg-msg-bubble').innerHTML = `<p>${escapeHTML(data.mensaje || 'Listo, te avisaremos.')}</p>`;
            } catch (e) {
                wrap.querySelector('.gmg-msg-bubble').innerHTML = '<p>No pude registrar el aviso, intenta más tarde.</p>';
            }
        }

        btn.addEventListener('click', enviarAviso);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') enviarAviso(); });
    }

    // =========================================================
    // 7. Funciones de Renderizado en el Chat
    // =========================================================
    function appendUserMessage(text, imageSrcs) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'gmg-msg gmg-msg-user';

        let innerHTML = '<div class="gmg-msg-bubble">';
        if (imageSrcs && imageSrcs.length > 0) {
            innerHTML += '<div class="gmg-msg-images-user">';
            imageSrcs.forEach(src => {
                innerHTML += `<img src="${src}" class="gmg-msg-image-user" alt="Foto adjunta">`;
            });
            innerHTML += '</div>';
        }
        if (text) {
            innerHTML += `<p>${escapeHTML(text)}</p>`;
        }
        innerHTML += `</div><span class="gmg-msg-time">${obtenerHoraActual()}</span>`;

        msgDiv.innerHTML = innerHTML;
        chatMessages.appendChild(msgDiv);
        scrollToBottom();
    }

    // Disponibilidad de la tarjeta (RF-013): la que manda el servidor o, en conversaciones
    // guardadas antes de este cambio, la que corresponde al stock.
    function disponibilidadDe(p) {
        if (p.disponibilidad) return p.disponibilidad;
        if (p.stock === undefined || p.stock === null || p.stock === '') return '';
        const stock = parseInt(p.stock, 10) || 0;
        if (stock <= 0) return 'Sin stock';
        if (stock <= 3) return 'Últimas unidades';
        return 'Disponible';
    }

    // Tarjetas de producto (F1-7). Todo dato va escapado y los botones no llevan código
    // en línea: los atiende un solo listener por data-accion (ver más abajo).
    function construirHTMLProductos(products) {
        if (!products || products.length === 0) return '';
        const formatoCLP = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
        let html = '<div class="gmg-chat-products">';
        products.forEach(p => {
            const img = p.imagen_url || 'img/placeholder-repuesto.svg';
            const disponibilidad = disponibilidadDe(p);
            const agotado = disponibilidad === 'Sin stock';
            const claseDisponibilidad = agotado ? 'agotado' : (disponibilidad === 'Últimas unidades' ? 'pocas' : 'ok');
            const meta = [p.modelo, p.sku ? 'SKU: ' + p.sku : ''].filter(Boolean).join(' | ');
            // "Ver ficha" solo con rutas del propio sitio (nunca javascript: ni otro dominio).
            const urlFicha = (p.url_ficha && !/^\s*([a-z][a-z0-9+.-]*:|\/\/)/i.test(p.url_ficha)) ? p.url_ficha : '';
            html += `
                <div class="gmg-chat-product-card">
                    <img src="${escapeHTML(img)}" class="gmg-chat-product-img" alt="${escapeHTML(p.nombre)}" loading="lazy" width="50" height="50">
                    <div class="gmg-chat-product-details">
                        <div class="gmg-chat-product-title">${escapeHTML(p.nombre)}</div>
                        <div class="gmg-chat-product-meta">${escapeHTML(meta)}</div>
                        <div class="gmg-chat-product-price">${formatoCLP.format(Number(p.precio) || 0)}</div>
                        ${disponibilidad ? `<span class="gmg-disponibilidad gmg-disponibilidad--${claseDisponibilidad}">${escapeHTML(disponibilidad)}</span>` : ''}
                    </div>
                    <div class="gmg-chat-product-actions">
                        <button type="button" class="gmg-chat-btn-add" data-accion="agregar"
                                data-id="${escapeHTML(p.id)}" data-nombre="${escapeHTML(p.nombre)}"
                                data-precio="${escapeHTML(p.precio)}" data-imagen="${escapeHTML(img)}"
                                data-stock="${p.stock === undefined || p.stock === null ? '' : escapeHTML(p.stock)}"
                                ${agotado ? 'disabled' : `aria-label="Añadir ${escapeHTML(p.nombre)} al carrito"`}>${agotado ? 'Sin stock' : '🛒 Añadir'}</button>
                        ${urlFicha ? `<a class="gmg-chat-btn-ficha" href="${escapeHTML(urlFicha)}">Ver ficha</a>` : ''}
                    </div>
                </div>`;
        });
        html += '</div>';
        return html;
    }

    // Un solo listener para todas las tarjetas, nuevas o restauradas del historial.
    if (chatMessages) {
        chatMessages.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion="agregar"]');
            if (!boton || boton.disabled || !window.addToCartFromChat) return;
            window.addToCartFromChat({
                id: boton.dataset.id,
                nombre: boton.dataset.nombre,
                precio: parseFloat(boton.dataset.precio) || 0,
                imagen: boton.dataset.imagen,
                stock: parseInt(boton.dataset.stock, 10) // NaN si no se conoce: decide el servidor al pagar
            }, 1);
        });
        // Foto que no carga: se cambia por la genérica (el evento "error" no burbujea, se captura).
        chatMessages.addEventListener('error', (evento) => {
            const img = evento.target;
            if (img && img.classList && img.classList.contains('gmg-chat-product-img') && !img.dataset.sinFoto) {
                img.dataset.sinFoto = '1';
                img.src = 'img/placeholder-repuesto.svg';
            }
        }, true);
    }

    function construirHTMLFeedback(mensajeId) {
        if (!mensajeId) return '';
        return `
            <div class="gmg-msg-feedback" data-mensaje-id="${mensajeId}">
                <button type="button" class="gmg-feedback-btn" data-feedback="positivo">👍 Útil</button>
                <button type="button" class="gmg-feedback-btn" data-feedback="negativo">👎 No ayudó</button>
            </div>
        `;
    }

    function activarFeedbackBotones(container) {
        const botones = container.querySelectorAll('.gmg-feedback-btn');
        botones.forEach(btn => {
            btn.addEventListener('click', async () => {
                const wrap = btn.closest('.gmg-msg-feedback');
                const mensajeId = wrap.getAttribute('data-mensaje-id');
                const feedback = btn.getAttribute('data-feedback');
                wrap.querySelectorAll('.gmg-feedback-btn').forEach(b => b.disabled = true);
                btn.classList.add('is-selected');
                try {
                    await fetch('backend/feedback_mensaje.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ mensaje_id: mensajeId, feedback: feedback, sesion_id: sesionId })
                    });
                } catch (e) {
                    // Silencioso: el feedback es un extra, no bloquea la conversación
                }
            });
        });
    }

    function appendBotMessage(text, products, mensajeId, opts) {
        const animar = !opts || opts.animar !== false;
        const msgDiv = document.createElement('div');
        msgDiv.className = 'gmg-msg gmg-msg-bot';
        const bubble = document.createElement('div');
        bubble.className = 'gmg-msg-bubble';
        msgDiv.appendChild(bubble);

        const timeSpan = document.createElement('span');
        timeSpan.className = 'gmg-msg-time';
        timeSpan.textContent = obtenerHoraActual();
        msgDiv.appendChild(timeSpan);

        chatMessages.appendChild(msgDiv);
        scrollToBottom();

        function finalizar() {
            bubble.innerHTML = formatMarkdown(text) + construirHTMLProductos(products) + construirHTMLFeedback(mensajeId);
            activarFeedbackBotones(bubble);
            scrollToBottom();
        }

        // Sin animación de escritura si el usuario pidió reducir el movimiento (RNF-026).
        const reducirMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!animar || !text || reducirMovimiento) {
            finalizar();
            return;
        }

        const textSpan = document.createElement('span');
        bubble.appendChild(textSpan);
        let i = 0;
        const totalPasos = 50;
        const paso = Math.max(1, Math.ceil(text.length / totalPasos));
        const timer = setInterval(() => {
            i += paso;
            textSpan.textContent = text.slice(0, i);
            scrollToBottom();
            if (i >= text.length) {
                clearInterval(timer);
                finalizar();
            }
        }, 14);
    }

    function showTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'gmgTypingIndicator';
        indicator.className = 'gmg-msg gmg-msg-bot';
        indicator.innerHTML = `
            <div class="gmg-typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        chatMessages.appendChild(indicator);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('gmgTypingIndicator');
        if (indicator) indicator.remove();
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function obtenerHoraActual() {
        const now = new Date();
        return now.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', hour12: false });
    }

    function escapeHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMarkdown(text) {
        let html = escapeHTML(text);
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Los enlaces (por ejemplo el de WhatsApp) se pueden tocar. [texto](https://...)
        // muestra solo el texto; una dirección suelta se muestra completa. Se resuelven
        // en una sola pasada para no envolver dos veces la dirección de un enlace.
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)|(https?:\/\/[^\s<]+)/g, function (coincidencia, texto, urlConTexto, urlSuelta) {
            const url = urlConTexto || urlSuelta;
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + (texto || url) + '</a>';
        });
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    // Respuesta simulada, solo para cuando la página se abre directamente con
    // el protocolo file:// (sin servidor PHP activo) durante pruebas locales.
    // En un despliegue real (Hostinger, http/https) nunca se usa esta rama.
    function simularRespuestaCliente(texto, tieneImagen) {
        if (tieneImagen) {
            return {
                reply: 'Modo de vista previa local (sin servidor PHP): no puedo analizar fotos aquí. Sube el sitio a tu hosting con PHP y MySQL activos para usar el Asistente real.',
                products: []
            };
        }
        return {
            reply: 'Estás viendo una vista previa local sin servidor PHP activo, así que no puedo consultar el catálogo real todavía. Ejecuta el sitio en tu hosting (o un servidor PHP local) para hablar con el Asistente Virtual de verdad.',
            products: []
        };
    }

    // =========================================================
    // 8. Dictado por voz (Web Speech API, cuando el navegador la soporta)
    // =========================================================
    const SpeechRecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (chatMicBtn && SpeechRecognitionAPI) {
        chatMicBtn.style.display = 'flex';
        const recognition = new SpeechRecognitionAPI();
        recognition.lang = 'es-CL';
        recognition.continuous = false;
        recognition.interimResults = true;
        let escuchando = false;

        recognition.addEventListener('start', () => {
            escuchando = true;
            chatMicBtn.classList.add('is-listening');
        });

        recognition.addEventListener('end', () => {
            escuchando = false;
            chatMicBtn.classList.remove('is-listening');
        });

        recognition.addEventListener('error', () => {
            escuchando = false;
            chatMicBtn.classList.remove('is-listening');
        });

        recognition.addEventListener('result', (event) => {
            let transcripcion = '';
            let esFinal = false;
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcripcion += event.results[i][0].transcript;
                if (event.results[i].isFinal) esFinal = true;
            }
            chatInput.value = transcripcion;
            if (esFinal && transcripcion.trim()) {
                enviarMensaje(transcripcion.trim());
            }
        });

        chatMicBtn.addEventListener('click', () => {
            if (escuchando) {
                recognition.stop();
            } else {
                try {
                    recognition.start();
                } catch (e) {
                    // Ya estaba escuchando o el navegador lo bloqueó
                }
            }
        });
    }

    // =========================================================
    // 9. Proactividad: inactividad y carrito abandonado
    // =========================================================
    const FLAG_INACTIVIDAD = 'gmg_chat_proactive_inactividad_v1';
    const FLAG_CARRITO = 'gmg_chat_proactive_carrito_v1';
    const TIEMPO_INACTIVIDAD_MS = 45000;
    const TIEMPO_CARRITO_MS = 25000;

    let burbujaActiva = null;
    let ultimaInteraccion = Date.now();

    ['mousemove', 'scroll', 'click', 'keydown', 'touchstart'].forEach(evento => {
        window.addEventListener(evento, () => { ultimaInteraccion = Date.now(); }, { passive: true });
    });

    function yaSeMostroEnEstaPestana(flag) {
        try {
            return sessionStorage.getItem(flag) === '1';
        } catch (e) {
            return false;
        }
    }

    function marcarMostradoEnEstaPestana(flag) {
        try {
            sessionStorage.setItem(flag, '1');
        } catch (e) { /* no-op */ }
    }

    function ocultarBurbujaProactiva() {
        if (burbujaActiva) {
            burbujaActiva.remove();
            burbujaActiva = null;
        }
    }

    function mostrarBurbujaProactiva(mensaje, flag, alHacerClick) {
        if (chatHasBeenOpened || chatWindow.classList.contains('active') || burbujaActiva) return;
        if (yaSeMostroEnEstaPestana(flag)) return;

        marcarMostradoEnEstaPestana(flag);

        const bubble = document.createElement('div');
        bubble.className = 'gmg-chat-proactive-bubble';
        bubble.innerHTML = `${escapeHTML(mensaje)}<span class="gmg-proactive-close" title="Cerrar">&times;</span>`;
        document.body.appendChild(bubble);
        burbujaActiva = bubble;

        bubble.querySelector('.gmg-proactive-close').addEventListener('click', (e) => {
            e.stopPropagation();
            ocultarBurbujaProactiva();
        });
        bubble.addEventListener('click', () => {
            abrirChat();
            if (alHacerClick) alHacerClick();
        });
    }

    const inicioPagina = Date.now();
    const chequeoProactivo = setInterval(() => {
        if (chatHasBeenOpened && chatWindow.classList.contains('active')) return;
        if (yaSeMostroEnEstaPestana(FLAG_INACTIVIDAD) && yaSeMostroEnEstaPestana(FLAG_CARRITO)) {
            clearInterval(chequeoProactivo);
            return;
        }

        const carritoConItems = window.gmgCarritoTieneItems ? window.gmgCarritoTieneItems() : false;
        const ahora = Date.now();

        if (carritoConItems && (ahora - inicioPagina) > TIEMPO_CARRITO_MS && !yaSeMostroEnEstaPestana(FLAG_CARRITO)) {
            mostrarBurbujaProactiva('Vimos que tienes productos en tu carrito 🛒 ¿Te ayudo a completar la compra?', FLAG_CARRITO, () => {
                appendBotMessage('¡Hola de nuevo! Puedo ayudarte a terminar tu compra ahora mismo. Cuando estés listo, dime "quiero pagar" o usa el botón de tu carrito. 🙂', [], null, { animar: true });
            });
        } else if ((ahora - ultimaInteraccion) > TIEMPO_INACTIVIDAD_MS && (ahora - inicioPagina) > TIEMPO_INACTIVIDAD_MS && !yaSeMostroEnEstaPestana(FLAG_INACTIVIDAD)) {
            mostrarBurbujaProactiva('¿Buscando algo puntual? Puedo ayudarte a encontrar tu repuesto Suzuki al instante 🔧', FLAG_INACTIVIDAD, null);
        }
    }, 5000);
});
