document.addEventListener('DOMContentLoaded', () => {
    const cartToggle = document.getElementById('cart-toggle');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    const closeCart = document.getElementById('close-cart');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartGuardadosContainer = document.getElementById('cart-guardados');
    const cartRelacionadosContainer = document.getElementById('cart-relacionados');
    const cartCount = document.getElementById('cart-count');
    const cartSelectAllRow = document.getElementById('cartSelectAllRow');
    const cartSelectAll = document.getElementById('cartSelectAll');
    const cartSubtotalRow = document.getElementById('cartSubtotalRow');
    const cartSubtotalPrice = document.getElementById('cart-subtotal-price');
    const cartDescuentoRow = document.getElementById('cartDescuentoRow');
    const cartDescuentoPrice = document.getElementById('cart-descuento-price');
    const cartTotalPrice = document.getElementById('cart-total-price');
    const btnCheckout = document.getElementById('btn-checkout');
    const checkoutSpinner = document.getElementById('checkout-spinner');
    const promoInput = document.getElementById('promoInput');
    const btnAplicarPromo = document.getElementById('btnAplicarPromo');
    const promoMensaje = document.getElementById('promoMensaje');

    const clienteModalOverlay = document.getElementById('clienteModalOverlay');
    const clienteModalClose = document.getElementById('clienteModalClose');
    const clienteForm = document.getElementById('clienteForm');
    const clienteFormError = document.getElementById('clienteFormError');
    const clienteFormSpinner = document.getElementById('clienteFormSpinner');
    const btnConfirmarCliente = document.getElementById('btnConfirmarCliente');

    // Aviso breve de éxito o error (E9-H7). Se lee con lector de pantalla gracias a aria-live.
    const gmgToast = document.getElementById('gmgToast');
    let temporizadorToast = null;
    function mostrarToast(texto, tipo) {
        if (!gmgToast) return;
        gmgToast.textContent = texto;
        gmgToast.classList.toggle('gmg-toast-error', tipo === 'error');
        gmgToast.hidden = false;
        clearTimeout(temporizadorToast);
        temporizadorToast = setTimeout(() => { gmgToast.hidden = true; }, 3200);
    }
    window.gmgMostrarToast = mostrarToast;

    let cart = JSON.parse(localStorage.getItem('gmg_cart')) || [];
    // Compatibilidad con carritos guardados antes de esta actualización
    cart.forEach(item => { if (typeof item.selected === 'undefined') item.selected = true; });

    let guardados = JSON.parse(localStorage.getItem('gmg_guardado')) || [];

    // Código de descuento aplicado en esta sesión (se revalida siempre en el
    // servidor al momento de pagar, así que aquí solo es para mostrar el total).
    let promoAplicado = null; // { codigo, descuento }

    // Modo de compra: 'carrito' (botón del carrito) o 'directo' ("Comprar ahora",
    // compra un solo producto sin tocar el carrito guardado).
    let modoCompra = 'carrito';
    let itemDirecto = null;

    let relacionadosCategoriaCargada = null;

    // Initialize cart UI
    updateCartUI();

    // Event Listeners for Cart Sidebar
    // Minimiza el widget de chat (si está abierto) sin borrar la conversación,
    // para que nunca quede tapando al carrito.
    function minimizeChatIfOpen() {
        if (window.gmgChatMinimize) window.gmgChatMinimize();
    }

    function abrirCarrito() {
        minimizeChatIfOpen();
        cartSidebar.classList.add('open');
        cartOverlay.classList.add('open');
        cargarRelacionados();
    }

    function cerrarCarrito() {
        cartSidebar.classList.remove('open');
        cartOverlay.classList.remove('open');
    }

    if (cartToggle) {
        cartToggle.addEventListener('click', (e) => {
            e.preventDefault();
            abrirCarrito();
        });
    }

    if (closeCart) {
        closeCart.addEventListener('click', cerrarCarrito);
    }

    if (cartOverlay) {
        cartOverlay.addEventListener('click', cerrarCarrito);
    }

    // Add to Cart buttons
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            const producto = leerDatosProducto(btn);
            if (!addToCart(producto)) return;
            abrirCarrito();
            mostrarToast('Agregaste "' + producto.nombre + '" al carrito.', 'exito');
        });
    });

    // Stock (F2-2, E3-H2, RF-020): el carrito no deja superar el stock conocido.
    // Si no se conoce (producto guardado antes de este cambio), decide el servidor al pagar.
    function cabeEnStock(item, cantidadNueva) {
        return !Number.isFinite(item.stock) || cantidadNueva <= item.stock;
    }
    function avisarStock(item) {
        mostrarToast(item.stock > 0
            ? 'Solo quedan ' + item.stock + (item.stock === 1 ? ' unidad' : ' unidades') + ' de "' + item.nombre + '".'
            : '"' + item.nombre + '" no tiene stock.', 'error');
    }

    // Botones "Comprar ahora": compran un único producto de inmediato,
    // sin agregarlo al carrito persistente.
    document.querySelectorAll('.btn-comprar-ahora').forEach(btn => {
        btn.addEventListener('click', () => {
            const producto = leerDatosProducto(btn);
            modoCompra = 'directo';
            itemDirecto = { ...producto, quantity: 1 };
            abrirModalCliente();
        });
    });

    function leerDatosProducto(btn) {
        return {
            id: btn.getAttribute('data-id'),
            nombre: btn.getAttribute('data-nombre'),
            precio: parseFloat(btn.getAttribute('data-precio')),
            imagen: btn.getAttribute('data-imagen'),
            categoria: btn.getAttribute('data-categoria') || '',
            stock: parseInt(btn.getAttribute('data-stock'), 10)
        };
    }

    /** Agrega 1 unidad. Devuelve false si ya no queda stock para otra. */
    function addToCart(product) {
        const existingItem = cart.find(item => String(item.id) === String(product.id));
        const item = existingItem || { ...product, quantity: 0, selected: true };
        if (Number.isFinite(product.stock)) item.stock = product.stock; // el dato más reciente de la página
        if (!cabeEnStock(item, item.quantity + 1)) {
            avisarStock(item);
            return false;
        }
        item.quantity += 1;
        if (!existingItem) cart.push(item);
        promoAplicado = null;
        saveCart();
        updateCartUI();
        if (window.gmgEvento) window.gmgEvento('add_to_cart', { currency: 'CLP', value: Number(item.precio) || 0, items: [window.gmgItemAnalitica(item, 1)] }); // F4-3
        return true;
    }

    function removeFromCart(id) {
        cart = cart.filter(item => String(item.id) !== String(id));
        promoAplicado = null;
        saveCart();
        updateCartUI();
    }

    function toggleSelected(id, seleccionado) {
        const item = cart.find(i => String(i.id) === String(id));
        if (item) item.selected = seleccionado;
        promoAplicado = null;
        saveCart();
        updateCartUI();
    }

    function guardarParaDespues(id) {
        const idx = cart.findIndex(item => String(item.id) === String(id));
        if (idx === -1) return;
        const [item] = cart.splice(idx, 1);
        delete item.quantity;
        delete item.selected;
        if (!guardados.find(g => String(g.id) === String(item.id))) {
            guardados.push(item);
        }
        promoAplicado = null;
        saveCart();
        saveGuardados();
        updateCartUI();
    }

    function moverAlCarrito(id) {
        const idx = guardados.findIndex(item => String(item.id) === String(id));
        if (idx === -1) return;
        const existing = cart.find(i => String(i.id) === String(id));
        if (existing && !cabeEnStock(existing, existing.quantity + 1)) {
            avisarStock(existing);
            return;
        }
        const [item] = guardados.splice(idx, 1);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({ ...item, quantity: 1, selected: true });
        }
        saveCart();
        saveGuardados();
        updateCartUI();
    }

    function quitarGuardado(id) {
        guardados = guardados.filter(item => String(item.id) !== String(id));
        saveGuardados();
        updateCartUI();
    }

    function saveCart() {
        localStorage.setItem('gmg_cart', JSON.stringify(cart));
    }

    function saveGuardados() {
        localStorage.setItem('gmg_guardado', JSON.stringify(guardados));
    }

    // Exponer función para que el bot la pueda llamar desde chat.js
    window.addToCartFromChat = function(product, quantity) {
        const existingItem = cart.find(item => String(item.id) === String(product.id));
        const item = existingItem || { ...product, quantity: 0, selected: true };
        if (Number.isFinite(product.stock)) item.stock = product.stock;
        if (!cabeEnStock(item, item.quantity + quantity)) {
            avisarStock(item);
            return;
        }
        item.quantity += quantity;
        if (!existingItem) cart.push(item);
        promoAplicado = null;
        saveCart();
        updateCartUI();
        if (window.gmgEvento) window.gmgEvento('add_to_cart', { currency: 'CLP', value: (Number(item.precio) || 0) * quantity, items: [window.gmgItemAnalitica(item, quantity)], origen: 'chat' }); // F4-3
        abrirCarrito();
        mostrarToast('Agregaste "' + product.nombre + '" al carrito.', 'exito');
    };

    function itemsSeleccionados() {
        return cart.filter(item => item.selected);
    }

    function calcularSubtotal(items) {
        return items.reduce((acc, item) => acc + (item.precio * item.quantity), 0);
    }

    function updateCartUI() {
        renderCartItems();
        renderGuardados();
        renderTotales();
    }

    function renderCartItems() {
        cartItemsContainer.innerHTML = '';
        let totalCount = 0;
        cart.forEach(item => totalCount += item.quantity);

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p style="text-align:center; color:#999; margin-top:20px;">Tu carrito está vacío.</p>';
            cartSelectAllRow.style.display = 'none';
        } else {
            cartSelectAllRow.style.display = 'flex';
            const todosSeleccionados = cart.every(item => item.selected);
            const algunoSeleccionado = cart.some(item => item.selected);
            cartSelectAll.checked = todosSeleccionados;
            cartSelectAll.indeterminate = !todosSeleccionados && algunoSeleccionado;

            cart.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item';
                itemDiv.innerHTML = `
                    <label class="cart-item-check">
                        <input type="checkbox" class="cart-item-checkbox" data-id="${item.id}" ${item.selected ? 'checked' : ''}>
                    </label>
                    <img src="${item.imagen}" alt="${item.nombre}" onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                    <div class="cart-item-details">
                        <div class="cart-item-title">${item.nombre}</div>
                        <div class="cart-item-price">$${item.precio.toLocaleString('es-CL')} CLP x ${item.quantity}</div>
                        <div class="cart-item-links">
                            <button class="cart-item-remove" data-id="${item.id}">Eliminar</button>
                            <button class="cart-item-guardar" data-id="${item.id}">Guardar para después</button>
                        </div>
                    </div>
                `;
                cartItemsContainer.appendChild(itemDiv);
            });

            cartItemsContainer.querySelectorAll('.cart-item-remove').forEach(btn => {
                btn.addEventListener('click', (e) => removeFromCart(e.target.getAttribute('data-id')));
            });
            cartItemsContainer.querySelectorAll('.cart-item-guardar').forEach(btn => {
                btn.addEventListener('click', (e) => guardarParaDespues(e.target.getAttribute('data-id')));
            });
            cartItemsContainer.querySelectorAll('.cart-item-checkbox').forEach(chk => {
                chk.addEventListener('change', (e) => toggleSelected(e.target.getAttribute('data-id'), e.target.checked));
            });
        }

        if (cartCount) cartCount.innerText = totalCount;
    }

    function renderGuardados() {
        if (guardados.length === 0) {
            cartGuardadosContainer.innerHTML = '';
            cartGuardadosContainer.style.display = 'none';
            return;
        }
        cartGuardadosContainer.style.display = 'block';
        let html = '<div class="cart-guardados-heading">Guardado para después (' + guardados.length + ')</div>';
        guardados.forEach(item => {
            html += `
                <div class="cart-item cart-item-guardado">
                    <img src="${item.imagen}" alt="${item.nombre}" onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                    <div class="cart-item-details">
                        <div class="cart-item-title">${item.nombre}</div>
                        <div class="cart-item-price">$${item.precio.toLocaleString('es-CL')} CLP</div>
                        <div class="cart-item-links">
                            <button class="cart-item-mover" data-id="${item.id}">Mover al carrito</button>
                            <button class="cart-item-remove" data-id="${item.id}">Quitar</button>
                        </div>
                    </div>
                </div>
            `;
        });
        cartGuardadosContainer.innerHTML = html;
        cartGuardadosContainer.querySelectorAll('.cart-item-mover').forEach(btn => {
            btn.addEventListener('click', (e) => moverAlCarrito(e.target.getAttribute('data-id')));
        });
        cartGuardadosContainer.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => quitarGuardado(e.target.getAttribute('data-id')));
        });
    }

    function renderTotales() {
        const seleccionados = itemsSeleccionados();
        const subtotal = calcularSubtotal(seleccionados);
        let descuento = 0;

        if (promoAplicado) {
            descuento = promoAplicado.descuento;
            if (descuento > subtotal) descuento = subtotal;
        }

        const total = subtotal - descuento;

        if (promoAplicado && descuento > 0) {
            cartSubtotalRow.style.display = 'flex';
            cartDescuentoRow.style.display = 'flex';
            cartSubtotalPrice.innerText = `$${subtotal.toLocaleString('es-CL')} CLP`;
            cartDescuentoPrice.innerText = `-$${descuento.toLocaleString('es-CL')} CLP`;
        } else {
            cartSubtotalRow.style.display = 'none';
            cartDescuentoRow.style.display = 'none';
        }

        cartTotalPrice.innerText = `$${total.toLocaleString('es-CL')} CLP`;

        const habilitado = seleccionados.length > 0;
        btnCheckout.disabled = !habilitado;
        btnCheckout.style.opacity = habilitado ? '1' : '0.5';
        actualizarWhatsappCarrito(seleccionados, total);
    }

    // "Finalizar por WhatsApp" (F3-6, E3-H11, RF-029): el pedido armado con productos, cantidades y total.
    function actualizarWhatsappCarrito(items, total) {
        const btnWhatsappCarrito = document.getElementById('btn-whatsapp-carrito');
        if (!btnWhatsappCarrito) return;
        const base = btnWhatsappCarrito.href.split('?')[0];
        const lineas = items.map(i => '- ' + i.quantity + ' x ' + i.nombre + ': $' + (i.precio * i.quantity).toLocaleString('es-CL'));
        const texto = 'Hola, quiero comprar estos repuestos:\n' + lineas.join('\n') + '\nTotal: $' + total.toLocaleString('es-CL') + ' CLP'
            + (promoAplicado ? ' (con el código ' + promoAplicado.codigo + ')' : '');
        btnWhatsappCarrito.href = base + '?text=' + encodeURIComponent(texto);
        btnWhatsappCarrito.hidden = items.length === 0;
    }

    // --- Seleccionar todos ---
    if (cartSelectAll) {
        cartSelectAll.addEventListener('change', () => {
            cart.forEach(item => item.selected = cartSelectAll.checked);
            promoAplicado = null;
            saveCart();
            updateCartUI();
        });
    }

    // --- Código de descuento ---
    if (btnAplicarPromo) {
        btnAplicarPromo.addEventListener('click', async () => {
            const codigo = (promoInput.value || '').trim();
            const subtotal = calcularSubtotal(itemsSeleccionados());

            if (!codigo) {
                mostrarMensajePromo('Ingresa un código.', false);
                return;
            }
            if (subtotal <= 0) {
                mostrarMensajePromo('Selecciona al menos un producto del carrito.', false);
                return;
            }

            btnAplicarPromo.disabled = true;
            try {
                const resp = await fetch('backend/validar_codigo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ codigo, subtotal })
                });
                const data = await resp.json();
                if (data.valido) {
                    promoAplicado = { codigo: data.codigo, descuento: data.descuento };
                    mostrarMensajePromo(data.mensaje, true);
                } else {
                    promoAplicado = null;
                    mostrarMensajePromo(data.mensaje || 'Código inválido.', false);
                }
                renderTotales();
            } catch (err) {
                mostrarMensajePromo('Error al validar el código.', false);
            }
            btnAplicarPromo.disabled = false;
        });
    }

    function mostrarMensajePromo(texto, exito) {
        promoMensaje.innerText = texto;
        promoMensaje.className = 'cart-promo-msg ' + (exito ? 'cart-promo-msg-exito' : 'cart-promo-msg-error');
    }

    // --- Productos relacionados ("Complemente su compra") ---
    async function cargarRelacionados() {
        if (cart.length === 0) {
            cartRelacionadosContainer.innerHTML = '';
            cartRelacionadosContainer.style.display = 'none';
            relacionadosCategoriaCargada = null;
            return;
        }
        const categoria = cart[0].categoria || '';
        if (relacionadosCategoriaCargada === categoria + '|' + cart.map(i => i.id).join(',')) {
            return; // ya está cargado para este mismo estado del carrito
        }
        try {
            const excluir = cart.map(i => i.id).join(',');
            const resp = await fetch(`backend/relacionados.php?categoria=${encodeURIComponent(categoria)}&excluir=${encodeURIComponent(excluir)}`);
            const data = await resp.json();
            if (!data.productos || data.productos.length === 0) {
                cartRelacionadosContainer.innerHTML = '';
                cartRelacionadosContainer.style.display = 'none';
                return;
            }
            let html = '<div class="cart-relacionados-heading">Complemente su compra</div><div class="cart-relacionados-track">';
            data.productos.forEach(p => {
                html += `
                    <div class="cart-relacionado-card">
                        <img src="${p.imagen}" alt="${p.nombre}" onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                        <div class="cart-relacionado-nombre">${p.nombre}</div>
                        <div class="cart-relacionado-precio">$${p.precio.toLocaleString('es-CL')}</div>
                        <button type="button" class="cart-relacionado-add" data-id="${p.id}" data-nombre="${p.nombre}" data-precio="${p.precio}" data-imagen="${p.imagen}">+ Agregar</button>
                    </div>
                `;
            });
            html += '</div>';
            cartRelacionadosContainer.innerHTML = html;
            cartRelacionadosContainer.style.display = 'block';
            relacionadosCategoriaCargada = categoria + '|' + cart.map(i => i.id).join(',');

            cartRelacionadosContainer.querySelectorAll('.cart-relacionado-add').forEach(btn => {
                btn.addEventListener('click', () => {
                    addToCart(leerDatosProducto(btn));
                    cargarRelacionados();
                });
            });
        } catch (err) {
            // Si falla, la sección no se muestra (no es esencial para comprar),
            // pero se deja constancia en la consola para poder revisarlo.
            console.warn('No se pudieron cargar los productos relacionados.', err);
            cartRelacionadosContainer.innerHTML = '';
            cartRelacionadosContainer.style.display = 'none';
        }
    }

    // --- Modal de datos del cliente ---
    // Resumen de la compra dentro del formulario: el cliente ve el total y el
    // despacho antes de pagar (Ley 19.496, historia E9-H1).
    function renderResumenCompra() {
        const resumen = document.getElementById('clienteResumen');
        if (!resumen) return;
        let total;
        let cantidad;
        if (modoCompra === 'directo' && itemDirecto) {
            total = itemDirecto.precio * itemDirecto.quantity;
            cantidad = itemDirecto.quantity;
        } else {
            const seleccionados = itemsSeleccionados();
            const subtotal = calcularSubtotal(seleccionados);
            const descuento = promoAplicado ? Math.min(promoAplicado.descuento, subtotal) : 0;
            total = subtotal - descuento;
            cantidad = seleccionados.reduce((acc, i) => acc + i.quantity, 0);
        }
        resumen.innerHTML = `<div><strong>${cantidad}</strong> ${cantidad === 1 ? 'producto' : 'productos'} · Total a pagar: <strong>$${total.toLocaleString('es-CL')} CLP</strong></div>`
            + '<div>Despacho: se coordina por WhatsApp (sin cobro adicional en este pago).</div>';
    }

    function limpiarErroresCliente() {
        clienteFormError.style.display = 'none';
        clienteForm.querySelectorAll('.campo-error').forEach(el => { el.textContent = ''; });
        clienteForm.querySelectorAll('.campo-invalido').forEach(el => {
            el.classList.remove('campo-invalido');
            el.removeAttribute('aria-invalid');
        });
    }

    // Muestra el mensaje de error pegado al campo correspondiente (E9-H6).
    function mostrarErrorCampo(idCampo, idMensaje, texto) {
        const campo = document.getElementById(idCampo);
        const mensaje = document.getElementById(idMensaje);
        if (mensaje) mensaje.textContent = texto;
        if (campo) {
            campo.classList.add('campo-invalido');
            campo.setAttribute('aria-invalid', 'true');
        }
    }

    function abrirModalCliente() {
        if (window.gmgEvento) { // F4-3: el cliente empezó a pagar
            const items = itemsSeleccionados();
            window.gmgEvento('begin_checkout', { currency: 'CLP', value: calcularSubtotal(items), items: items.map(i => window.gmgItemAnalitica(i, i.quantity)) });
        }
        // El aviso de "agregaste al carrito" no debe tapar el formulario.
        if (gmgToast) { clearTimeout(temporizadorToast); gmgToast.hidden = true; }
        limpiarErroresCliente();
        renderResumenCompra();
        clienteModalOverlay.classList.add('open');
        const primerCampo = document.getElementById('clienteNombre');
        if (primerCampo) setTimeout(() => primerCampo.focus(), 50);
    }

    function cerrarModalCliente() {
        clienteModalOverlay.classList.remove('open');
        modoCompra = 'carrito';
        itemDirecto = null;
    }

    if (clienteModalClose) {
        clienteModalClose.addEventListener('click', cerrarModalCliente);
    }
    if (clienteModalOverlay) {
        clienteModalOverlay.addEventListener('click', (e) => {
            if (e.target === clienteModalOverlay) cerrarModalCliente();
        });
    }

    // Checkout process: abrir el carrito -> pedir datos del cliente -> pagar
    if (btnCheckout) {
        btnCheckout.addEventListener('click', () => {
            if (itemsSeleccionados().length === 0) return;
            modoCompra = 'carrito';
            itemDirecto = null;
            abrirModalCliente();
        });
    }

    // Exponer para que el chatbot pueda iniciar el checkout guiado cuando el
    // cliente le pide pagar (etiqueta [START_CHECKOUT], ver chat.js). Devuelve
    // true si pudo abrir el formulario, o false si el carrito está vacío (en
    // ese caso chat.js le avisa al cliente que primero agregue un producto).
    window.gmgIniciarCheckoutDesdeChat = function () {
        if (itemsSeleccionados().length === 0) {
            return false;
        }
        modoCompra = 'carrito';
        itemDirecto = null;
        abrirCarrito();
        abrirModalCliente();
        return true;
    };

    // Exponer si el carrito tiene items (para el aviso de carrito abandonado
    // en chat.js), sin exponer el contenido completo del carrito.
    window.gmgCarritoTieneItems = function () {
        return cart.length > 0;
    };

    // RUT chileno: formato 12.345.678-9 y dígito verificador correcto (módulo 11).
    function validarRut(rut) {
        const limpio = rut.replace(/[.\s]/g, '').toUpperCase();
        if (!/^\d{6,9}-[\dK]$/.test(limpio)) return false;
        const [cuerpo, dv] = limpio.split('-');
        let suma = 0;
        let multiplicador = 2;
        for (let i = cuerpo.length - 1; i >= 0; i--) {
            suma += parseInt(cuerpo[i], 10) * multiplicador;
            multiplicador = multiplicador === 7 ? 2 : multiplicador + 1;
        }
        const resto = 11 - (suma % 11);
        const esperado = resto === 11 ? '0' : (resto === 10 ? 'K' : String(resto));
        return dv === esperado;
    }

    // Celular chileno (F2-7, RF-021): misma regla que gmgTelefonoChile() en el servidor.
    // Acepta +56 9 1234 5678, 56912345678 o 912345678, con o sin espacios ni guiones.
    function validarTelefono(tel) {
        let digitos = tel.replace(/\D/g, '');
        if (digitos.length === 11 && digitos.startsWith('56')) digitos = digitos.slice(2);
        return /^9\d{8}$/.test(digitos);
    }

    function validarEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    if (clienteForm) {
        clienteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clienteFormError.style.display = 'none';

            limpiarErroresCliente();

            const nombre = document.getElementById('clienteNombre').value.trim();
            const apellido = document.getElementById('clienteApellido').value.trim();
            const rut = document.getElementById('clienteRut').value.trim();
            const telefono = document.getElementById('clienteTelefono').value.trim();
            const email = document.getElementById('clienteEmail').value.trim();
            const acepta = document.getElementById('clienteAcepta').checked;

            // Cada error se muestra junto a su campo; el primero recibe el foco.
            const errores = [];
            if (!nombre) errores.push(['clienteNombre', 'errClienteNombre', 'Escribe tu nombre.']);
            if (!apellido) errores.push(['clienteApellido', 'errClienteApellido', 'Escribe tu apellido.']);
            if (!rut) errores.push(['clienteRut', 'errClienteRut', 'Escribe tu RUT.']);
            else if (!validarRut(rut)) errores.push(['clienteRut', 'errClienteRut', 'El RUT no es válido. Revisa que esté completo y con guion (ej: 12.345.678-9).']);
            if (!telefono) errores.push(['clienteTelefono', 'errClienteTelefono', 'Escribe tu teléfono.']);
            else if (!validarTelefono(telefono)) errores.push(['clienteTelefono', 'errClienteTelefono', 'Ingresa un celular chileno, por ejemplo +56 9 1234 5678.']);
            if (!email) errores.push(['clienteEmail', 'errClienteEmail', 'Escribe tu correo electrónico.']);
            else if (!validarEmail(email)) errores.push(['clienteEmail', 'errClienteEmail', 'El correo no es válido (ej: nombre@correo.cl).']);
            if (!acepta) errores.push(['clienteAcepta', 'errClienteAcepta', 'Para continuar debes aceptar los términos y condiciones y la política de privacidad.']);

            if (errores.length > 0) {
                errores.forEach(([campo, mensaje, texto]) => mostrarErrorCampo(campo, mensaje, texto));
                const primero = document.getElementById(errores[0][0]);
                if (primero) primero.focus();
                return;
            }

            const cliente = { nombre, apellido, rut, telefono, email };

            let itemsParaComprar;
            if (modoCompra === 'directo' && itemDirecto) {
                itemsParaComprar = [{ id: itemDirecto.id, quantity: itemDirecto.quantity }];
            } else {
                itemsParaComprar = itemsSeleccionados().map(item => ({ id: item.id, quantity: item.quantity }));
            }

            if (itemsParaComprar.length === 0) {
                mostrarErrorCliente('No hay productos para comprar.');
                return;
            }

            const payload = { items: itemsParaComprar, cliente, acepta_terminos: true };
            if (modoCompra === 'carrito' && promoAplicado) {
                payload.codigo_descuento = promoAplicado.codigo;
            }

            btnConfirmarCliente.style.display = 'none';
            clienteFormSpinner.style.display = 'block';

            try {
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success && result.init_point) {
                    if (modoCompra === 'carrito') {
                        // Solo se limpian del carrito los productos que efectivamente se compraron
                        const idsComprados = itemsParaComprar.map(i => String(i.id));
                        cart = cart.filter(item => !idsComprados.includes(String(item.id)));
                        saveCart();
                    }
                    window.location.href = result.init_point;
                } else {
                    // Si faltó stock, el carrito toma el stock real para no volver a pasarse.
                    if (Array.isArray(result.sin_stock)) {
                        result.sin_stock.forEach(f => {
                            const item = cart.find(i => String(i.id) === String(f.id));
                            if (item) item.stock = parseInt(f.disponible, 10) || 0;
                        });
                        saveCart();
                    }
                    mostrarErrorServidor(result.message || 'Hubo un error al procesar el pago.');
                    btnConfirmarCliente.style.display = 'block';
                    clienteFormSpinner.style.display = 'none';
                }
            } catch (error) {
                mostrarErrorCliente('No pudimos conectarnos para procesar el pago. Revisa tu conexión a Internet e inténtalo de nuevo.');
                btnConfirmarCliente.style.display = 'block';
                clienteFormSpinner.style.display = 'none';
            }
        });
    }

    function mostrarErrorCliente(texto) {
        clienteFormError.innerText = texto;
        clienteFormError.style.display = 'block';
    }

    // Si el servidor rechaza un dato puntual, el mensaje se muestra junto a ese
    // campo; si es un error general (por ejemplo del pago), arriba del botón.
    function mostrarErrorServidor(texto) {
        const t = texto.toLowerCase();
        if (t.indexOf('rut') !== -1) {
            mostrarErrorCampo('clienteRut', 'errClienteRut', texto);
        } else if (t.indexOf('correo') !== -1 || t.indexOf('electrónico') !== -1) {
            mostrarErrorCampo('clienteEmail', 'errClienteEmail', texto);
        } else if (t.indexOf('términos') !== -1) {
            mostrarErrorCampo('clienteAcepta', 'errClienteAcepta', texto);
        } else {
            mostrarErrorCliente(texto);
        }
    }

    // Al corregir un campo, su mensaje de error desaparece.
    if (clienteForm) {
        clienteForm.addEventListener('input', (e) => {
            const campo = e.target;
            if (campo && campo.classList && campo.classList.contains('campo-invalido')) {
                campo.classList.remove('campo-invalido');
                campo.removeAttribute('aria-invalid');
                const idMensaje = campo.getAttribute('aria-describedby');
                const mensaje = idMensaje ? document.getElementById(idMensaje) : null;
                if (mensaje) mensaje.textContent = '';
            }
        });
        clienteForm.addEventListener('change', (e) => {
            if (e.target && e.target.id === 'clienteAcepta' && e.target.checked) {
                e.target.classList.remove('campo-invalido');
                const mensaje = document.getElementById('errClienteAcepta');
                if (mensaje) mensaje.textContent = '';
            }
        });
    }
});
