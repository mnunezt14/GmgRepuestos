<?php
/**
 * Piezas que se repiten en todas las páginas públicas de la tienda:
 *   1. Botón y ventana del Asistente Virtual (chat flotante).
 *   2. Panel lateral del carrito.
 *   3. Formulario de datos del cliente antes de pagar.
 *
 * Se incluye desde backend/pagina_publica.php (función gmgPiePublico).
 * Variables esperadas: $modelosCortosTexto (texto con los modelos del catálogo).
 */
$modelosCortosTexto = $modelosCortosTexto ?? 'todos los modelos Suzuki';
$whatsappMensaje = $whatsappMensaje ?? 'Hola, necesito ayuda con un repuesto';
?>
    <!-- Botón flotante de WhatsApp (F3-6, E7-H4, RF-065): el mensaje depende de la página -->
    <a id="gmgWhatsappFlotante" class="gmg-wa-flotante" href="<?php echo gmgH(gmgEnlaceWhatsApp($whatsappMensaje)); ?>"
       target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp" title="Escríbenos por WhatsApp">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.6-.3z"/></svg>
    </a>

    <!-- Botón Flotante del Chatbot IA (historia E5-H1: visible en cualquier página pública) -->
    <button type="button" id="gmgChatToggle" class="gmg-chat-toggle" aria-label="Abrir Asistente Virtual GMG" aria-expanded="false" aria-controls="gmgChatWindow" title="Abrir Asistente Virtual GMG">
        <span class="chat-badge"></span>
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
    </button>

    <!-- Ventana del Chatbot IA -->
    <div id="gmgChatWindow" class="gmg-chat-window" role="dialog" aria-label="Asistente Virtual GMG"
         data-whatsapp="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, necesito ayuda con un repuesto')); ?>">
        <div class="gmg-chat-header">
            <div class="gmg-chat-header-info">
                <div class="gmg-chat-avatar">GMG</div>
                <div class="gmg-chat-title">
                    <h4>Asistente GMG (IA)</h4>
                    <p>Especialista en línea</p>
                </div>
            </div>
            <div class="gmg-chat-header-actions">
                <!-- Seguir por WhatsApp con un resumen de la conversación (F3-6, E5-H7, RF-042) -->
                <a id="gmgChatWhatsapp" class="gmg-chat-wa-btn" href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, necesito ayuda con un repuesto')); ?>"
                   target="_blank" rel="noopener" title="Seguir por WhatsApp con un resumen de esta conversación" aria-label="Seguir por WhatsApp con un resumen de esta conversación">WhatsApp</a>
                <button id="gmgChatClose" class="gmg-chat-close-btn" title="Minimizar chat" aria-label="Minimizar chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Aviso de privacidad al abrir el chat (F3-7, E5-H18, RNF-017) -->
        <p class="gmg-chat-privacidad">Guardamos esta conversación hasta 12 meses para atenderte mejor. No compartas claves ni datos de tarjetas. <a href="politica-de-privacidad.php">Privacidad</a></p>

        <div id="gmgChatMessages" class="gmg-chat-messages" aria-live="polite">
            <div class="gmg-msg gmg-msg-bot">
                <div class="gmg-msg-bubble">
                    <?php if (!empty($bienvenidaChat)): // F3-8: mensaje configurado en el panel ?>
                    <p><?php echo nl2br(gmgH($bienvenidaChat)); ?></p>
                    <?php else: ?>
                    <p>¡Hola! 👋 Soy tu <strong>Asistente Virtual GMG</strong> especialista en repuestos Suzuki (<?php echo htmlspecialchars($modelosCortosTexto); ?>).</p>
                    <p>¿En qué te puedo ayudar hoy? Puedes consultarme por precios, disponibilidad, subir hasta <strong>3 fotos</strong> de la pieza que necesitas, o dictar tu consulta por voz.</p>
                    <?php endif; ?>
                </div>
                <span class="gmg-msg-time">Ahora</span>
            </div>
        </div>

        <div id="gmgChatAviso" class="gmg-chat-aviso" role="status" aria-live="polite" hidden></div>

        <!-- Botones rápidos (E5-H1). Los que llevan data-atajo se responden al instante con la información configurada en el panel. -->
        <div class="gmg-quick-chips">
            <button type="button" class="gmg-chip" data-atajo="buscar" data-prompt="Buscar repuesto">🔍 Buscar repuesto</button>
            <button type="button" class="gmg-chip" data-atajo="despacho" data-prompt="Despacho">🚚 Despacho</button>
            <button type="button" class="gmg-chip" data-atajo="pagos" data-prompt="Medios de pago">💳 Medios de pago</button>
            <button type="button" class="gmg-chip" data-action="upload" data-prompt="Tomar foto">📸 Tomar foto</button>
        </div>

        <div id="gmgChatPreviewBar" class="gmg-chat-image-preview-bar">
            <div id="gmgChatPreviewList" class="gmg-chat-preview-list"></div>
        </div>

        <div class="gmg-chat-footer">
            <input type="file" id="gmgChatFileInput" aria-label="Elegir fotos de la pieza desde la galería (hasta 3)" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
            <!-- Cámara del celular (capture): abre la cámara trasera para fotografiar la pieza; en el computador abre el selector de archivos -->
            <input type="file" id="gmgChatCameraInput" aria-label="Tomar una foto de la pieza con la cámara" accept="image/jpeg,image/png,image/webp" capture="environment" style="display: none;">
            <button type="button" id="gmgChatFileBtn" class="gmg-chat-file-btn" title="Adjuntar fotos desde la galería (hasta 3)" aria-label="Adjuntar fotos desde la galería">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </button>
            <button type="button" id="gmgChatMicBtn" class="gmg-chat-file-btn gmg-chat-mic-btn" title="Consultar por voz" aria-label="Consultar por voz" style="display:none;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                    <line x1="12" y1="19" x2="12" y2="23"></line>
                    <line x1="8" y1="23" x2="16" y2="23"></line>
                </svg>
            </button>
            <input type="text" id="gmgChatInput" class="gmg-chat-input" placeholder="Escribe tu consulta..." autocomplete="off" aria-label="Escribe tu consulta">
            <button type="button" id="gmgChatSend" class="gmg-chat-send-btn" title="Enviar mensaje" aria-label="Enviar mensaje">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Panel Lateral del Carrito -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h3>Tu Carrito</h3>
            <button id="close-cart" class="close-cart" aria-label="Cerrar carrito">&times;</button>
        </div>
        <div class="cart-select-all-row" id="cartSelectAllRow" style="display:none;">
            <label class="cart-checkbox-label">
                <input type="checkbox" id="cartSelectAll" checked>
                Seleccionar todos
            </label>
        </div>
        <div id="cart-items" class="cart-items"></div>
        <div id="cart-guardados" class="cart-guardados"></div>
        <div id="cart-relacionados" class="cart-relacionados"></div>
        <div class="cart-footer">
            <div class="cart-promo">
                <input type="text" id="promoInput" placeholder="Código de descuento" autocomplete="off" aria-label="Código de descuento">
                <button type="button" id="btnAplicarPromo">Aplicar</button>
            </div>
            <div id="promoMensaje" class="cart-promo-msg" role="status" aria-live="polite"></div>
            <div class="cart-subtotal-row" id="cartSubtotalRow" style="display:none;">
                <span>Subtotal:</span><span id="cart-subtotal-price">$0</span>
            </div>
            <div class="cart-descuento-row" id="cartDescuentoRow" style="display:none;">
                <span>Descuento:</span><span id="cart-descuento-price">-$0</span>
            </div>
            <!-- Ley 19.496 (E9-H1): el cliente ve el despacho y las políticas antes de pagar -->
            <div class="cart-despacho-row">
                <span>Despacho:</span><span>Se coordina por WhatsApp</span>
            </div>
            <div class="cart-total">
                <span>Total a pagar:</span>
                <span id="cart-total-price">$0</span>
            </div>
            <p class="cart-legal">Precios en pesos chilenos (CLP). El pago en línea cubre solo los repuestos; el despacho no tiene cobro en este pago y se coordina contigo por WhatsApp. Revisa nuestras políticas de <a href="cambios-y-devoluciones.php">cambios y devoluciones</a>, <a href="cambios-y-devoluciones.php#garantia-legal">garantía legal</a> y <a href="cambios-y-devoluciones.php#derecho-de-retracto">derecho de retracto</a>.</p>
            <button id="btn-checkout" class="btn-checkout btn-mercadopago">Pagar con Mercado Pago</button>
            <!-- Alternativa sin pago en línea (F3-6, E3-H11, RF-029): el pedido va armado a WhatsApp -->
            <a id="btn-whatsapp-carrito" class="btn-checkout btn-whatsapp-carrito" href="<?php echo gmgH(gmgEnlaceWhatsApp('')); ?>" target="_blank" rel="noopener">Finalizar por WhatsApp</a>
            <div id="checkout-spinner" class="cart-spinner" role="status" aria-live="polite" style="display:none;">Procesando...</div>
        </div>
    </div>
    <div id="cart-overlay" class="cart-overlay"></div>

    <!-- Modal de datos del cliente (previo al pago) -->
    <div id="clienteModalOverlay" class="cliente-modal-overlay">
        <div class="cliente-modal" role="dialog" aria-modal="true" aria-labelledby="clienteModalTitulo">
            <button type="button" id="clienteModalClose" class="cliente-modal-close" aria-label="Cerrar">&times;</button>
            <h3 id="clienteModalTitulo">Un último paso</h3>
            <p class="cliente-modal-sub">Necesitamos estos datos para coordinar tu compra. GMG Repuestos no realiza envíos: coordinamos contigo la entrega por WhatsApp.</p>
            <!-- Datos personales (F4-9, E10-H6, RNF-037): para qué se usan y cómo pedir acceso, corrección o eliminación -->
            <p class="cliente-modal-privacidad">Usamos tus datos solo para esta compra y su seguimiento. Puedes pedir verlos, corregirlos o eliminarlos por <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, quiero ejercer mis derechos sobre mis datos personales (acceso, corrección o eliminación). Mi nombre y RUT son: ')); ?>" target="_blank" rel="noopener">WhatsApp</a> (detalle en <a href="politica-de-privacidad.php#tus-derechos" target="_blank" rel="noopener">Tus derechos</a>).</p>
            <div class="cliente-resumen" id="clienteResumen" aria-live="polite"></div>
            <form id="clienteForm" class="cliente-form" novalidate>
                <div class="cliente-form-row">
                    <div class="cliente-campo">
                        <label for="clienteNombre">Nombre</label>
                        <input type="text" id="clienteNombre" autocomplete="given-name" aria-describedby="errClienteNombre">
                        <small class="campo-error" id="errClienteNombre" role="alert"></small>
                    </div>
                    <div class="cliente-campo">
                        <label for="clienteApellido">Apellido</label>
                        <input type="text" id="clienteApellido" autocomplete="family-name" aria-describedby="errClienteApellido">
                        <small class="campo-error" id="errClienteApellido" role="alert"></small>
                    </div>
                </div>
                <div class="cliente-form-row">
                    <div class="cliente-campo">
                        <label for="clienteRut">RUT</label>
                        <input type="text" id="clienteRut" placeholder="12.345.678-9" inputmode="text" autocomplete="off" aria-describedby="errClienteRut">
                        <small class="campo-error" id="errClienteRut" role="alert"></small>
                    </div>
                    <div class="cliente-campo">
                        <label for="clienteTelefono">Teléfono</label>
                        <input type="tel" id="clienteTelefono" placeholder="+56 9 1234 5678" autocomplete="tel" aria-describedby="errClienteTelefono">
                        <small class="campo-error" id="errClienteTelefono" role="alert"></small>
                    </div>
                </div>
                <div class="cliente-form-row">
                    <div class="cliente-form-full cliente-campo">
                        <label for="clienteEmail">Correo electrónico</label>
                        <input type="email" id="clienteEmail" placeholder="tucorreo@ejemplo.cl" autocomplete="email" aria-describedby="errClienteEmail">
                        <small class="campo-error" id="errClienteEmail" role="alert"></small>
                    </div>
                </div>
                <p class="cliente-form-nota">Te avisaremos por este correo cuando tu pedido sea aprobado o si hay algún problema con el pago.</p>
                <div class="cliente-campo cliente-acepta">
                    <label class="cliente-acepta-label" for="clienteAcepta">
                        <input type="checkbox" id="clienteAcepta" aria-describedby="errClienteAcepta">
                        <span>Leí y acepto los <a href="terminos-y-condiciones.php" target="_blank" rel="noopener">Términos y condiciones</a> y la <a href="politica-de-privacidad.php" target="_blank" rel="noopener">Política de privacidad</a>. Sé que tengo <a href="cambios-y-devoluciones.php#derecho-de-retracto" target="_blank" rel="noopener">derecho de retracto</a> y <a href="cambios-y-devoluciones.php#garantia-legal" target="_blank" rel="noopener">garantía legal</a>.</span>
                    </label>
                    <small class="campo-error" id="errClienteAcepta" role="alert"></small>
                </div>
                <div id="clienteFormError" class="cliente-form-error" role="alert" style="display:none;"></div>
                <button type="submit" class="btn-checkout" id="btnConfirmarCliente">Continuar al pago</button>
                <div id="clienteFormSpinner" class="cart-spinner" role="status" aria-live="polite" style="display:none;">Procesando...</div>
            </form>
        </div>
    </div>

    <!-- Avisos breves de éxito o error (E9-H7) -->
    <div id="gmgToast" class="gmg-toast" role="status" aria-live="polite" hidden></div>
