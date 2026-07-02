# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Proyecto

Aplicación de gestión de tiempos personal basada en GTD (Getting Things Done) de David Allen.

**Stack:** PHP 8.4 · MariaDB 11.4 · Apache 2.4 · Bootstrap 5 · Vanilla JS  
**Arquitectura:** MVC sin framework · Docker Compose (desarrollo) · cPanel (producción)  
**Producción:** https://gtd.aurusmind.com (cPanel aurusmin, DB: aurusmin_gtd)  
**Repo:** github.com/solartejoseluis/app_gestion_de_tiempos  
**Último commit estable:** 0f51ad1

---

## Comandos de desarrollo

```bash
docker compose up -d              # Iniciar entorno
docker compose down               # Detener entorno
docker compose up -d --build      # Reconstruir tras cambios en Dockerfile
docker compose exec app bash      # Acceder al contenedor PHP
docker compose logs -f app        # Ver logs
```

URLs locales: App → http://localhost · phpMyAdmin → http://localhost:8080  
Credenciales de prueba locales: admin@gtd.local / admin123

---

## Arquitectura
HTTP request → public/.htaccess → public/index.php (front controller)
→ app/Core/Router.php::dispatch()
→ app/Controllers/XxxController
→ app/Views/xxx/yyy.php (via $this->layout())

- **Autoloader** (sin Composer): busca en app/Controllers/, app/Models/, app/Core/ por nombre de clase.
- **Router:** rutas en registerRoutes(), soporta {param}, emula PATCH/DELETE con _method.
- **Controller base:** layout(), json(), error(), redirect(), requireAuth(), input()
- **Model base:** $table, findAll(), findOne(), insert() protected, update() protected, softDelete() protected, query()
- **SidebarCounters::get($userId)** se carga automáticamente en layout()

---

## Reglas de trabajo — NUNCA violar

- **Git:** NO hacer commits ni push automáticos. Solo sugerir mensaje.
- **BD:** siempre PDO con prepared statements.
- **Modelos:** insert/update/softDelete son protected — usar wrappers públicos.
- **Bootstrap Modal:** siempre bootstrap.Modal.getOrCreateInstance(el) en el momento de uso.
- **Eventos JS:** delegación sobre contenedor padre, nunca querySelector directo sobre ítems PHP.
- **AJAX mutaciones:** POST. Vistas: GET.
- **Vistas dashboard:** $this->layout('modulo.vista', $data)
- **Credenciales:** nunca hardcodear, siempre $_ENV['VARIABLE']
- **declare(strict_types=1)** en todos los archivos PHP
- **Endpoints compartidos:** `EsperaController::posponer()` (POST /espera/posponer) parece no usarse desde /espera (su modal fue eliminado) pero lo consume revision/paso3_espera.php de forma independiente (paso 3 de revisión semanal, revision.js, clase btn-posponer-espera) — no eliminar.

---

## Contexto GTD — dominio

- **8 tipos de ítem:** inbox, accion, proyecto_accion, delegada, incubada, referencia, completada, eliminada
- **Contexto (@):** obligatorio en accion, proyecto_accion, delegada. NO aplica a proyectos.
- **Calendario:** solo recibe ítems con tipo_tiempo = 'cita' (sagrado en GTD).
- **Flujo procesamiento:** inbox → proyecto convierte el ítem original en 'completada'; las acciones hijas son proyecto_accion.
- **Semana:** empieza el lunes. Revisión semanal debe llegar a inbox=0 antes de continuar.

---

## Schema — tablas principales

- **items:** id, usuario_id, titulo, notas, tipo (enum 8 valores), tipo_tiempo (ninguno/dia/cita), area_id, contexto_id, proyecto_id, persona_id, fecha_accion, hora_inicio, hora_fin, bloque_id, fecha_cita, fecha_completada, duracion_minutos, energia, deleted_at
  - ⚠️ `fecha_cita` y `duracion_minutos` son columnas legacy **sin uso activo en el código** — ningún controlador las escribe desde la migración 009 (reemplazadas por `hora_inicio`/`hora_fin`). No eliminadas del schema, pero no usarlas en código nuevo.
- **proyectos:** id, usuario_id, nombre, resultado_deseado, area_id, estado (activo/completado/archivado), deleted_at
- **areas:** id, usuario_id, nombre, descripcion, color, estado, deleted_at
- **contextos:** id, usuario_id, nombre, color, deleted_at
- **personas:** id, usuario_id, nombre, rol, deleted_at
- **bloques_tiempo:** id, usuario_id, nombre, color, dias_semana (ej "1,2,3,4,5"), hora_inicio, hora_fin, fecha_inicio, fecha_fin, estado, deleted_at
- **revisiones_semanales:** id, usuario_id, paso_actual, completada, foco_semana, deleted_at

**Migraciones aplicadas:** 001 al 009 (incluyendo 009_items_agenda.sql)

---

## Módulos — estado completo

Todos los módulos están completos y en producción:

| Módulo | Ruta | Estado |
|--------|------|--------|
| Auth | /login, /logout | ✅ |
| Inbox | /inbox | ✅ |
| Procesamiento GTD | modal global | ✅ |
| Próximas acciones | /acciones | ✅ |
| Proyectos | /proyectos | ✅ |
| Vista acciones de proyecto | /proyectos/{id}/acciones | ✅ |
| En espera de | /espera | ✅ |
| Algún día | /someday | ✅ |
| Referencia | /referencia | ✅ |
| Completadas | /completadas | ✅ |
| Revisión semanal | /revision | ✅ |
| Configuración | /config | ✅ |
| Plantilla de bloques | /plantilla | ✅ |
| Agenda semanal | /agenda | ✅ |
| Agenda diaria | /agenda/dia | ✅ |

---

## Funcionalidades implementadas

- **Chip de fecha mejorado:** día de semana + hora (si cita) + días restantes/pasados — en todas las vistas. Fuente de la hora: `hora_inicio` (columna TIME), no `fecha_cita`.
- **Vista /acciones en columnas:** layout de lista Creada | Acción | Fecha | Info | Hecho | Editar (reemplaza las tarjetas apiladas anteriores). Aplica igual en mobile y desktop, con scroll horizontal en pantallas angostas (mismo patrón que /agenda). Ordenamiento client-side por columna (Creada, Acción, Fecha) con íconos de dirección — mismo mecanismo que /config (data-* + Array.sort() + reordenar con appendChild). Orden por defecto: Creada descendente; persiste al aplicar filtros. Botón "Info" expandible por fila revela breadcrumb (área › contexto › proyecto), notas y hora inicio–fin. Botones Hecho/Editar consolidados a ícono-solo (mismo patrón que /proyectos). AccionModel sin cambios de columnas — `proyecto_color` no existe en la tabla `proyectos`.
- **Modo agenda en /acciones:** toggle lista/agenda por período temporal (vencidas, hoy, mañana, esta semana, próxima semana, más adelante, sin fecha). Unificado con la lista: el frente de cada tarjeta muestra exactamente lo mismo que la fila de lista (título + chip de fecha + chip de días relativos, mismos colores condicionales rojo/amarillo/verde) — contexto/área/proyecto ya NO aparecen en el frente (redundante con el filtro superior), solo dentro de "Info". Mismo panel Info expandible, mismo botón Editar (con Borrar) y mismo botón Hecho (`.btn-check-circular`) que la lista, reutilizando el HTML ya renderizado por PHP vía `outerHTML` en vez de duplicar lógica de renderizado en JS — garantiza paridad exacta de datos entre ambos modos. Delegación de eventos centralizada en `.acciones-wrapper` (contenedor común de lista y agenda) para que los elementos clonados en agenda disparen la misma lógica. Lee `data-hora-inicio` del ítem para el label de hora.
- **Notas inline:** autoguardado con debounce en /acciones, /proyectos/{id}/acciones, /espera, /someday
- **Modal de edición unificado:** componente global en dashboard con 7 campos (título, área, contexto, proyecto, fecha, hora inicio, hora fin) + botón "Borrar" en el footer. JS: public/js/editar_accion.js, window.abrirModalEditar(config), eventos accion:editada y accion:eliminada. Usado en /acciones (lista y modo agenda) y /espera (botón "Editar" en cada vista). Escuchado por acciones.js y espera.js. Limitación conocida: el listener de accion:editada solo refresca título y dataset del botón — el chip visual de fecha/hora no se actualiza sin recargar la página (el dato en BD sí queda correcto de inmediato).
  - ⚠️ **Corrección:** `/proyectos/{id}/acciones` NO usa este modal (nunca lo usó, a pesar de una mención incorrecta previa en este archivo). Tiene su propio flujo de borrado independiente: botón `.btn-eliminar-accion` + modal propio `#modalConfirmarEliminarAccion` (ver app/Views/proyectos/acciones.php). No tiene botón "Editar".
- **Borrar acción (modal unificado):** botón "Borrar" en modal_editar_accion.php usa window.confirmarAccion() y hace DELETE /acciones/{id}. Al confirmar, dispara accion:eliminada (mismo patrón que accion:editada) para que la vista que lo abrió remueva el ítem del DOM sin recargar.
- **Confirmación global:** app/Views/components/modal_confirmar.php + public/js/confirmar.js — window.confirmarAccion(texto, callback, opciones), con opciones = { titulo, textoBoton } opcional. Extraído del código antes duplicado en /completadas; /completadas ahora usa el componente global en vez de su copia inline.
- **Fecha + hora en modal de procesamiento:** los flujos Programar y Delegar (modal_procesamiento.php) capturan fecha (`type="date"`) y hora inicio/fin (`type="time"`) como campos separados, mismo patrón que el modal de edición. `ProcesamientoController` usa el helper privado `horaONull()` (mismo patrón que `fechaONull()`) en `programar()`, `delegar()` y `nuevaAccion()`.
- **Captura en /inbox (mobile):** barra de captura `position: fixed` en la parte inferior, siempre visible. Botón de guardar como ícono (`bi-send-fill`, `aria-label="Guardar"`). Placeholder "¿Qué tienes en mente?". Botones Procesar/Borrar de cada ficha como íconos con `aria-label`/`title` (vista PHP y `crearElemento()` en inbox.js). Márgenes laterales igualados a /acciones (24px totales).
- **Agenda grid semanal:** 7 columnas × 32 slots (05:00–21:00, 48px/slot). Bloques de tiempo (amarillo), acciones (violeta), citas (azul), completadas (verde). Línea hora actual.
- **Agenda vista día:** grid de 1 columna, creación desde slot (mini-modal), modal detalle con completar/editar
- **Modal de detalle y de creación unificados entre /agenda y /agenda/dia:** toda la lógica (antes duplicada inline por vista) vive en `public/js/agenda.js` compartido — scroll a hora actual, crear acción desde un slot del grid o desde la franja "todo el día", modal de detalle (completar/editar/cerrar), sincronización con `accion:editada`. IDs del modal de detalle unificados entre ambas vistas (`det-*`, `modal-evento-detalle`), ya que cada vista es una página completa independiente (sin riesgo de colisión). Los chips de "todo el día" (`tipo_tiempo='dia'`, franja superior del calendario) abren el mismo modal de detalle que los eventos con hora, mostrando "Todo el día" en vez de un rango horario. Clic en área vacía de la franja (`data-fecha` en `.agenda-allday-col`) abre el modal de crear con los campos de hora ocultos y un hint ("Acción de todo el día, sin hora específica.") — mismo modal que el clic en un slot del grid, que sigue precargando la hora normalmente. El handler de la franja ignora explícitamente clics dentro de `.agenda-chip-allday` (activos o completados) vía `e.target.closest()`, sin depender solo de `stopPropagation()`. En `dia.php` el contenedor de la franja ahora siempre se renderiza (antes se omitía si no había ítems ese día), con `data-fecha`, para que exista área clicable incluso en días vacíos.
  - ⚠️ **Fix:** `AgendaModel::getSemana()` no seleccionaba `contexto_id` ni `proyecto_id` (solo los nombres vía JOIN) — `data-contexto-id`/`data-proyecto-id` quedaban siempre vacíos, afectando también a los eventos con hora ya existentes (Editar no precargaba esos campos). Corregido.
  - ⚠️ **Fix:** `AccionesController::crear()` nunca leía ni persistía `tipo_tiempo` (quedaba siempre `NULL` sin importar el flujo). Ahora se calcula en `agenda.js` según si se proporcionó hora (`'cita'`) o no (`'dia'`), y se valida en el backend con el mismo criterio que `ProcesamientoController::tipoTiempoValido()`.
- **Plantilla semanal:** CRUD de bloques de tiempo recurrentes con días, horario, color, vigencia
- **Recuperar/eliminar completadas:** ítems y proyectos con confirmación
- **Responsive móvil completo:** sidebar hamburguesa, todas las vistas adaptadas, agenda con scroll horizontal
- **Caché de selects:** en procesamiento.js (_cache) y editar_accion.js (_cacheEdit)
- **Ordenamiento por columnas:** client-side en tabla de áreas (config)
- **Descripción de áreas:** campo editable inline con autoguardado
- **Vista /proyectos — mobile y botones:** márgenes mobile igualados a /acciones (header y lista, 24px totales). Botones "Ver acciones", "Agregar acción", "Pausar", "Reactivar", "Completar" son ícono-solo con `aria-label` (sin tooltip — mobile no lo soporta de forma confiable). Botón global para colapsar/expandir todas las categorías (áreas + "Proyectos completados") vía `bootstrap.Collapse` nativo — estado inicial: todas colapsadas; clase compartida `.proyecto-area-collapse` marca qué contenedores responden al control (`#btn-toggle-areas` en public/js/proyectos.js).

---

## Componentes globales (en dashboard.php)

- `app/Views/components/modal_procesamiento.php` + `public/js/procesamiento.js`
- `app/Views/components/modal_editar_accion.php` + `public/js/editar_accion.js` — incluye botón "Borrar" (dispara `accion:eliminada`)
- `app/Views/components/modal_confirmar.php` + `public/js/confirmar.js` — `window.confirmarAccion(texto, callback, opciones?)`, `opciones` acepta `{ titulo, textoBoton }`

**Endpoints de selects reutilizables (POST):**
- /procesar/areas · /procesar/contextos · /procesar/proyectos · /procesar/personas

---

## Agenda — fórmula de posicionamiento CSS
top    = ((hora - 5) * 60 + minutos) / 30 * 48                 px
height = ((hora_fin - hora_inicio) en minutos) / 30 * 48       px

Grid: CSS Grid con absolute positioning. 7 columnas × 32 slots × 48px = 1536px altura total.

La clase CSS del evento (`tipo-cita` azul vs `tipo-accion` violeta) se determina directamente por `tipo_tiempo === 'cita'` — no depende de `fecha_cita` ni `duracion_minutos` (columnas legacy, ver Schema).

---

## Patrones JS establecidos

- IIFE con 'use strict' en todos los archivos JS
- Delegación de eventos sobre contenedor padre
- fetchCached(key, url) para selects — evita requests repetidos
- window.abrirModalEditar(config) — función global para editar cualquier acción
- window.confirmarAccion(texto, callback, opciones?) — función global para confirmar acciones destructivas
- document.dispatchEvent(new CustomEvent('accion:editada', {detail})) — comunicación entre módulos
- document.dispatchEvent(new CustomEvent('accion:eliminada', {detail: {id}})) — mismo patrón que accion:editada, tras borrar desde el modal unificado
- Scroll automático en agenda: scrollTop = top - clientHeight/3
- Reutilizar HTML ya renderizado por PHP vía `.outerHTML` al construir vistas alternativas en JS (ej. modo agenda de /acciones clona `.btn-edit` y `.acciones-item-info-body` desde el DOM de lista) en vez de duplicar lógica de renderizado — garantiza paridad exacta de datos entre vistas

---

## Despliegue en producción

**Flujo local → producción:**
1. Desarrollar y verificar en local (Docker)
2. Commit manual por José Luis
3. Exportar BD: `docker compose exec -T db mariadb-dump -u root -proot_secret gtd_db > export.sql`
4. Comprimir: `tar --exclude='.git' --exclude='docker' -czf gtd_app.tar.gz app_gestion_de_tiempos`
5. Subir via Administrador de archivos cPanel a `/home/aurusmin/gtd.aurusmind.com/`
6. Importar BD en phpMyAdmin de cPanel
7. Verificar .env en servidor (DB_HOST=localhost, APP_ENV=production)

**.env producción:**
APP_ENV=production
APP_URL=https://gtd.aurusmind.com
APP_DEBUG=false
DB_HOST=localhost
DB_NAME=aurusmin_gtd
DB_USER=aurusmin_gtduser

---

## Deuda técnica

- **Zona horaria en modo agenda de /acciones:** la agrupación por período (vencidas/hoy/mañana/semana/etc.) usa `new Date()` del navegador (zona horaria del cliente), mientras el resto de la vista usa el "hoy" calculado en el servidor (PHP). Puede causar que un ítem límite (ej. justo alrededor de medianoche) aparezca en el período incorrecto si el cliente está en una zona horaria distinta a la del servidor. No corregido — pendiente de definir alcance en otra fase.
- **`/agenda/dia` no muestra completados sin hora en la franja "todo el día":** `$completadas` solo se pinta en el loop de "con hora" (`dia.php`); un ítem completado con `tipo_tiempo='dia'` no aparece en ningún lado de esa vista, a diferencia de `/agenda` (semanal) que sí lo hace. Gap preexistente, no corregido — pendiente de definir alcance en otra fase.

---

## Próximas fases

- **Fase 2:** React frontend + PHP REST API con JWT
- **Fase 3:** Flutter móvil/escritorio (consume la misma API)
- **UX pendiente:** sincronización con Google Calendar vía API

No hacer commits ni push — solo José Luis hace commits manualmente.
