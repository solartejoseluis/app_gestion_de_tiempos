# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Proyecto

Aplicación de gestión de tiempos personal basada en GTD (Getting Things Done) de David Allen.

**Stack:** PHP 8.4 · MariaDB 11.4 · Apache 2.4 · Bootstrap 5 · Vanilla JS  
**Arquitectura:** MVC sin framework · Docker Compose (desarrollo) · cPanel (producción)  
**Producción:** https://gtd.aurusmind.com (cPanel aurusmin, DB: aurusmin_gtd)  
**Repo:** github.com/solartejoseluis/app_gestion_de_tiempos  
**Último commit estable:** b72d8a3

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
- **Modo agenda en /acciones:** toggle lista/agenda por período temporal (hoy, mañana, esta semana, etc.). Lee `data-hora-inicio` del ítem para el label de hora.
- **Notas inline:** autoguardado con debounce en /acciones, /proyectos/{id}/acciones, /espera, /someday
- **Modal de edición unificado:** componente global en dashboard con 7 campos (título, área, contexto, proyecto, fecha, hora inicio, hora fin). JS: public/js/editar_accion.js, window.abrirModalEditar(config), evento accion:editada. Usado en /acciones, /proyectos/{id}/acciones y /espera (botón "Editar" en cada vista). Limitación conocida: el listener de accion:editada solo refresca título y dataset del botón — el chip visual de fecha/hora no se actualiza sin recargar la página (el dato en BD sí queda correcto de inmediato).
- **Fecha + hora en modal de procesamiento:** los flujos Programar y Delegar (modal_procesamiento.php) capturan fecha (`type="date"`) y hora inicio/fin (`type="time"`) como campos separados, mismo patrón que el modal de edición. `ProcesamientoController` usa el helper privado `horaONull()` (mismo patrón que `fechaONull()`) en `programar()`, `delegar()` y `nuevaAccion()`.
- **Captura en /inbox (mobile):** barra de captura `position: fixed` en la parte inferior, siempre visible. Botón de guardar como ícono (`bi-send-fill`, `aria-label="Guardar"`). Placeholder "¿Qué tienes en mente?". Botones Procesar/Borrar de cada ficha como íconos con `aria-label`/`title` (vista PHP y `crearElemento()` en inbox.js). Márgenes laterales igualados a /acciones (24px totales).
- **Agenda grid semanal:** 7 columnas × 32 slots (05:00–21:00, 48px/slot). Bloques de tiempo (amarillo), acciones (violeta), citas (azul), completadas (verde). Línea hora actual.
- **Agenda vista día:** grid de 1 columna, creación desde slot (mini-modal), modal detalle con completar/editar
- **Plantilla semanal:** CRUD de bloques de tiempo recurrentes con días, horario, color, vigencia
- **Recuperar/eliminar completadas:** ítems y proyectos con confirmación
- **Responsive móvil completo:** sidebar hamburguesa, todas las vistas adaptadas, agenda con scroll horizontal
- **Caché de selects:** en procesamiento.js (_cache) y editar_accion.js (_cacheEdit)
- **Ordenamiento por columnas:** client-side en tabla de áreas (config)
- **Descripción de áreas:** campo editable inline con autoguardado

---

## Componentes globales (en dashboard.php)

- `app/Views/components/modal_procesamiento.php` + `public/js/procesamiento.js`
- `app/Views/components/modal_editar_accion.php` + `public/js/editar_accion.js`

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
- document.dispatchEvent(new CustomEvent('accion:editada', {detail})) — comunicación entre módulos
- Scroll automático en agenda: scrollTop = top - clientHeight/3

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

- **Código huérfano en /espera:** el modal `#modalPosponer` (app/Views/espera/index.php) y su JS asociado en public/js/espera.js (flujo viejo de "posponer", pre modal unificado) quedaron sin ningún botón que los dispare desde que /espera pasó a usar `abrirModalEditar()`. Pendiente eliminarlos en una fase de limpieza aparte.
- **`EsperaController::posponer()` (POST /espera/posponer) NO se debe eliminar** aunque el modal de /espera ya no lo use — lo sigue usando revision/paso3_espera.php (paso 3 de revisión semanal) de forma independiente, con su propia UI inline (revision.js, clase btn-posponer-espera).

---

## Próximas fases

- **Fase 2:** React frontend + PHP REST API con JWT
- **Fase 3:** Flutter móvil/escritorio (consume la misma API)
- **UX pendiente:** sincronización con Google Calendar vía API

No hacer commits ni push — solo José Luis hace commits manualmente.
