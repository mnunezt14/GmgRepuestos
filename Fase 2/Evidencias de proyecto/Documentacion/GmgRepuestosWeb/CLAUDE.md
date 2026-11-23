# GMG Repuestos: instrucciones del proyecto

Tienda en línea de repuestos Suzuki con Asistente Virtual IA (proyecto Capstone, Duoc UC, sección 003D).
Equipo: Vicente Santibañez (Product Owner) y Martín Núñez. Cliente: GMG Repuestos SpA.
Fecha de término del proyecto: 18-12-2026.

## Qué hacer al empezar cada sesión

0. Durante el cierre del proyecto, sigue `docs/INSTRUCCIONES_CLAUDE_CODE.md` (puntos de commit, acciones de Vicente y orden de prioridad).
1. Lee `docs/PLAN_PRODUCCION.md` y toma la primera tarea sin marcar de la fase en curso (la primera vez es F0-0: instalar las reglas de `docs/claude-setup/` en `.claude/`).
2. Si la tarea toca el chat o la IA, lee también `docs/FIX_ASISTENTE_IA.md`.
3. Durante el cierre no pidas permiso entre commits (lo dice `docs/INSTRUCCIONES_CLAUDE_CODE.md`). Fuera del cierre, antes de editar propón un plan corto (archivos, cambios, cómo se prueba) y espera el visto bueno.
4. Al terminar, marca la tarea con `[x]` en el plan y anota en una línea qué se probó.

## Stack y estructura

- PHP 8 sin framework, `mysqli`, MariaDB/MySQL (`utf8mb4_unicode_ci`), HTML, CSS y JavaScript sin frameworks ni build.
- Hosting compartido Hostinger (LiteSpeed/Apache con `.htaccess`). Esta carpeta es el contenido de `public_html`.
- IA: OpenAI Responses API, modelo `gpt-5.6-luna`, con la herramienta `buscar_productos` (function calling).

| Carpeta | Contenido |
| --- | --- |
| raíz | Páginas públicas (`index.php`, `checkout.php`, `mi-pedido.php`, páginas legales) |
| `admin/` | Panel (cada página llama `requerirAdmin('../')` al inicio) |
| `backend/` | Lógica: `config.php`, `entorno.php` (lee `.env`), `datos.php` (consultas), `chat*.php` (asistente), `admin_ui.php` (CSRF y cabecera del panel) |
| `js/`, `css/` | `chat.js`, `cart.js`, `tienda.js`, `model-carousel.js` y sus estilos |
| `BD_v2/` | Scripts SQL numerados (`01_estructura.sql`, `02_datos_catalogo.sql`, ...) y `catalogo_repuestos.csv` |
| `docs/` | Plan, fix de la IA, instrucciones del cierre y pendientes de Vicente |
| `tests/` | Pruebas automáticas (búsqueda del chat, CP-42, CP-47) y Apache de prueba (no se publica) |
| `arquitectura/` | Documentación 4+1 en HTML (no se publica) |

## Comandos útiles

- Revisar sintaxis de un archivo: `php -l ruta/archivo.php`
- Pruebas de la búsqueda del chat: `php tests/chat_busqueda_test.php`
- Búsqueda contra el catálogo real (necesita `pdo_sqlite`): `php tests/chat_busqueda_sqlite_test.php BD_v2/catalogo_repuestos.csv`
- Búsqueda, historial y límites contra MariaDB local (no deja cambios): `php tests/chat_busqueda_mariadb_test.php`
- Herramienta `buscar_productos` del chat contra MariaDB (no deja cambios): `php tests/chat_herramienta_test.php`
- Chat de punta a punta con una API de OpenAI falsa (`tests/fake_openai.php`, sin gastar tokens): `php tests/chat_historial_test.php` (historial), `php tests/chat_api_test.php` (tiempos, reintento, rondas y carrito; tarda unos 40 s) y `php tests/chat_abuso_test.php` (fotos, campos y límite por IP)
- CSRF del panel (CP-42): `bash tests/cp42_csrf_panel.sh`; bloqueos del `.htaccess` con Apache real (CP-47): `bash tests/cp47_htaccess.sh`
- Tarjetas del chat en un navegador real (Edge o Chrome sin ventana, con captura a 360 px): `php tests/chat_tarjetas_test.php`
- Disponibilidad en las tarjetas de la tienda: `php tests/tienda_disponibilidad_test.php`
- Stock en el checkout (CP-15, bloqueo `FOR UPDATE`, sin llegar a Mercado Pago): `php tests/checkout_stock_test.php`; tope de stock en el carrito (Edge sin ventana): `php tests/carrito_stock_test.php`
- Descuento de stock al aprobar un pedido en el panel (F2-3, idempotente y con `FOR UPDATE`): `php tests/pedido_aprobar_test.php`
- Ficha de producto (F2-4: contenido, SEO, 404, escape y, en Edge, chat y carrito a 360 y 1366 px; deja la base como estaba): `php tests/producto_ficha_test.php`
- Buscador y sugerencias de la tienda (F2-5: CP-03 a CP-06, con Edge; deja la base como estaba): `php tests/buscador_test.php`
- Paginación del listado de a 24 (F2-6, con Edge a 360 px): `php tests/paginacion_test.php`
- Teléfono chileno en servidor, `cart.js` (con Node) y checkout (F2-7): `php tests/telefono_test.php`
- Panel de productos: fotos subidas, edición rápida, CP-40, CP-41 y CP-46 (F2-8; borra los archivos de prueba al final): `php tests/panel_productos_test.php`
- Fase 3 y 4 (todas terminan en "TODO OK"): `login_seguro_test` (F3-1), `limites_ip_test` (F3-3), `sql_preparado_test` (F3-4), `csp_test` (F3-5, Edge), `whatsapp_test` (F3-6, Edge), `privacidad_chat_test` (F3-7), `config_chatbot_test` (F3-8), `reposicion_test` (F3-9), `busquedas_vacias_test` (F3-10), `aviso_stock_test` (F3-11, Edge), `seo_test` (F4-1), `filtros_test` (F4-4), `accesibilidad_test` (F4-8, Edge): `php tests/<nombre>.php`
- Fase 3 y 4, segunda parte: `cuentas_test` (F3-2 y F4-10), `chat_pedido_test` (F3-12), `pedido_correos_test` (F4-6), `importar_csv_test` (F4-5), `analitica_test` (F4-3, Edge), `auditoria_publica_test` (mi pedido y feedback); F4-2 (URLs legibles) se prueba en `cp47_htaccess.sh`
- Foto con la cámara del celular en el chat (capture y reducción a 1600 px, Edge): `php tests/chat_camara_test.php`
- Correos (SMTP falso `tests/fake_smtp.php`, remitente, base64, errores visibles en el panel y pantalla Correo): `php tests/correo_test.php`
- Prueba de carga (F5-2, no es automática): `k6 run -e SITIO=https://gmgrepuestos.cl tests/k6/carga.js`
- Paquete para Hostinger: `dist/subida_final/` (se rearma copiando todo menos `.claude`, `CLAUDE.md`, `docs`, `BD_v2`, `arquitectura`, `tests`, `.git`, `.env*`, `.git*`, `dist` y `skills-lock.json`); guía para subirlo y para todo lo que hace una persona: `docs/GUIA_HOSTINGER_PASO_A_PASO.md`
- Skills instaladas en `.claude/skills/` (Ponytail y Emil Kowalski) con `npx skills add`; `skills-lock.json` permite reinstalarlas
- `cp42_csrf_panel.sh` necesita `mysql` en el PATH: `export PATH="/c/xampp/php:/c/xampp/mysql/bin:$PATH"` antes de correrlo
- Ver una página a 360 px reales con Edge sin ventana (no baja de unos 500 px): abrir `/tests/vista_ancho.html?ancho=360&url=/index.php%3Fq%3Dfiltro` y sacar la captura
- Asistente con la IA REAL (gasta la clave del `.env` local; pedir permiso a Vicente antes): `php tests/chat_ia_real_test.php --confirmo-gasto` (informe en `docs/evidencias/resultados_F1-9.md`)
- Servidor local: `php -S localhost:8000` desde esta carpeta, con una base local cargada desde `BD_v2/` y un `.env` de pruebas (`APP_ENV=pruebas`).
- Armar el ambiente local en un computador nuevo (XAMPP, base `gmg_local` con stock de prueba): `docs/OTRO_COMPUTADOR.md`.
- Levantar el sitio en localhost para otra persona del equipo (sin Claude Code): `docs/GUIA_LOCALHOST_PASO_A_PASO.md`

## Reglas que no se rompen

- **Secretos:** nunca leas, muestres ni copies el contenido de `.env`. Si necesitas saber qué claves existen, usa `.env.example`.
- **Git:** no ejecutes ningún comando de Git (ni siquiera `git status`). Al terminar cada tarea, detente y entrega los comandos (`git add -A`, `git status`, `git commit -m "..."`, `git push`) para que Vicente los ejecute; espera su "listo".
- **Mercado Pago está fuera de alcance:** no modifiques `mercadopago_return.php`, `backend/pagos.php` ni la creación de la preferencia en `checkout.php`, salvo que una tarea del plan lo pida.
- **Sin frameworks nuevos:** nada de Composer, npm, React, Tailwind ni librerías de animación. Todo en PHP, CSS y JS nativos.
- **Base de datos:** todo cambio de esquema va en un script nuevo y numerado en `BD_v2/` (nunca editar los ya ejecutados). Indica si hay que ejecutarlo en producción.
- **Producción:** `.claude/`, `CLAUDE.md`, `docs/`, `BD_v2/`, `arquitectura/` y `tests/` no se suben a `public_html`.

## Convenciones de código

- Nombres en español; funciones globales con prefijo `gmg` (sitio) o `chat` (asistente).
- SQL siempre con sentencias preparadas (`prepare` y `bind_param`). No agregar `real_escape_string` nuevo.
- Toda salida dinámica se escapa: `gmgH()` en páginas públicas, `gmgAdminH()` en el panel, `escapeHTML()` en JS.
- Todo `POST` del panel valida `gmgCsrfValido()` y cada formulario imprime `gmgCsrfCampo()`.
- Errores al log (`error_log('[GMG ...] ...')`), nunca al usuario. Mensajes al cliente: cortos, en español de Chile, con salida a WhatsApp.
- Un archivo nuevo de `backend/` que no deba abrirse desde el navegador se agrega a la lista de `backend/.htaccess`.
- Cambios mínimos: arregla lo que pide la tarea, sin refactorizar de paso. Si ves otra cosa, anótala en el plan.

## Estilo de los textos

- Nunca uses guiones largos para intercalar texto; usa paréntesis o comas.
- Textos de la interfaz y del asistente: español de Chile, cercano y breve.

## Skills del proyecto

- **Ponytail** (`/ponytail`, `/ponytail-review`, `/ponytail-audit`): úsalo en todo el backend para mantener el código mínimo y revisar el diff antes de cerrar una tarea.
- **Emil Kowalski** (`emil-design-eng`, `review-animations`, `improve-animations`, `break-ui`, `mobile-native`): úsalo solo en tareas de interfaz (`css/`, `js/`, vistas). Aplica sus principios con CSS y JS nativos, sin agregar librerías.

## Contexto que no está en el código

- GMG no hace despachos: la entrega se coordina por WhatsApp (afecta E3-H4 y E7-H2).
- Se eliminaron a propósito la pantalla de Auditoría y la de historial del chat del panel; el registro de auditoría en la base se mantiene.
- Los requisitos están en el SRS (126 requisitos) y el backlog tiene 99 historias (525 puntos); el plan las cita por ID (E2-H5, RF-013, INC-04, CP-26).
