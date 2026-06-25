# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Proyecto

Aplicación de gestión de tiempos basada en la metodología GTD (Getting Things Done) de David Allen.
Stack: PHP 8.4 + MariaDB 11.4 + Apache 2.4 + Bootstrap 5 + DataTables. Arquitectura MVC. Entorno de desarrollo con Docker.

## Comandos de desarrollo

```bash
# Iniciar entorno
docker compose up -d

# Detener entorno
docker compose down

# Reconstruir imagen tras cambios en Dockerfile
docker compose up -d --build

# Acceder al contenedor PHP
docker compose exec app bash

# Ver logs de la app
docker compose logs -f app
```

**URLs:**
- Aplicación: http://localhost
- phpMyAdmin: http://localhost:8080

**Setup inicial:**
```bash
cp .env.example .env
# Editar .env con los valores del entorno local
docker compose up -d --build
```

No hay framework de tests ni linter configurado actualmente.

## Arquitectura

### Flujo de una petición

```
HTTP request
  → public/.htaccess   (redirige todo a index.php)
  → public/index.php   (front controller: carga .env, autoloader, config, sesión)
  → app/Core/Router.php::dispatch()  (hace match de ruta y llama al controller)
  → app/Controllers/XxxController    (lógica, llama al Model, devuelve view o json)
  → app/Views/xxx/yyy.php            (HTML + PHP, recibe variables via extract())
```

### Autoloader

Sin Composer. El autoloader en `public/index.php` busca clases en este orden:
`app/Controllers/`, `app/Models/`, `app/Core/`. Los nombres de clase deben coincidir exactamente con el nombre de archivo.

### Router (`app/Core/Router.php`)

Rutas definidas en `registerRoutes()` (método privado). Soporta `{param}` en la ruta (convierte a regex). Los formularios HTML pueden emular PATCH/DELETE con `<input name="_method" value="PATCH">`.

### Controller base (`app/Core/Controller.php`)

Métodos disponibles en todos los controllers:
- `$this->view('modulo.archivo', $data)` — renderiza `app/Views/modulo/archivo.php` con `extract($data)`
- `$this->json($data, $status)` — respuesta JSON con envelope `{ok, data}`
- `$this->error($message, $status)` — respuesta JSON con envelope `{ok, error}`
- `$this->redirect('/ruta')` — redirige usando `APP_URL`
- `$this->requireAuth()` — redirige a `/login` si no hay `$_SESSION['usuario_id']`
- `$this->input('key', $default)` — lee de `$_POST` o `$_GET`

### Model base (`app/Core/Model.php`)

Cada Model define `protected string $table`. Métodos heredados:
- `findAll(where?, params?)`, `findOne(where, params)` — SELECT
- `insert(data)` → devuelve el `lastInsertId`
- `update(id, data)` → UPDATE por `id`
- `softDelete(id)` → pone `deleted_at = NOW()`
- `query(sql, params)` → para consultas personalizadas

## Reglas de trabajo

### Git — MUY IMPORTANTE
- **NO hacer commits automáticamente**. El desarrollador los hace manualmente.
- **NO hacer push**. El desarrollador decide cuándo.
- Puedes sugerir el mensaje de commit pero nunca ejecutar `git commit` ni `git push`.
- Formato de commit: `tipo(alcance): descripción`. Tipos: `feat`, `fix`, `refactor`, `style`, `docs`, `chore`, `test`.

### Base de datos
- Usar **siempre PDO con prepared statements**. Nunca concatenar SQL con variables de usuario.
- La conexión se obtiene con `Database::connection()` (singleton).
- Las consultas que no encajan en los métodos base usan `$this->query($sql, $params)`.

### Seguridad
- Usar `$this->requireAuth()` en todos los controllers que requieren sesión activa.
- Las contraseñas se hashean con `password_hash()` y se verifican con `password_verify()`.

### Estilo de código PHP
- Siempre declarar `declare(strict_types=1)` al inicio de cada archivo.
- Usar tipos en parámetros y retornos de funciones cuando sea posible.
- Nombres de clases: PascalCase. Métodos y variables: camelCase.

### Frontend
- Bootstrap 5 para layout. DataTables para tablas. jQuery/vanilla JS para interactividad.
- Los modales de Bootstrap se controlan desde archivos JS en `public/js/`.
- Los assets estáticos viven en `public/css/`, `public/js/`, `public/assets/`.

### Variables de entorno
- Nunca hardcodear credenciales. Siempre usar `$_ENV['VARIABLE']`.
- El archivo `.env` no se sube a Git.

### Changelog
- Sugerir la entrada del `CHANGELOG.md` para cada funcionalidad completada.
- El desarrollador decide si la agrega y cuándo.

## Contexto GTD

- Los ítems tienen 8 tipos: `inbox`, `accion`, `proyecto_accion`, `delegada`, `incubada`, `referencia`, `completada`, `eliminada`.
- El campo `contexto` (`@`) es obligatorio para tipos: `accion`, `proyecto_accion`, `delegada`. **No aplica a proyectos.**
- El calendario solo recibe ítems con `tipo_tiempo = 'cita'` (día y hora fijos).
- El árbol de decisión GTD tiene 5 bifurcaciones (ver Especificación Técnica Funcional / Anexo A).
- **Flujo GTD corregido:** cuando un ítem del inbox se convierte en proyecto, el ítem original queda con `tipo = 'completada'` (no `proyecto_accion`). Solo las acciones hijas del proyecto son `proyecto_accion`.

## Estado de módulos

### Completados (0–9)

| #  | Ruta base      | Controller                  | Estado     |
|----|----------------|-----------------------------|------------|
| 0  | —              | —                           | Estructura MVC, Docker, Router, Core |
| 1  | `/auth`        | `AuthController`            | Login / logout con sesión |
| 2  | `/dashboard`   | —                           | Layout base con sidebar y badges |
| 3  | `/inbox`       | `InboxController`           | Captura y listado con soft-delete |
| 4  | `/inbox` (modal) | `ProcesamientoController` | Modal GTD completo (11 endpoints, cascada) |
| 5  | `/acciones`    | `AccionesController`        | Próximas acciones, filtros, chips de contexto, completar |
| 6  | `/proyectos`   | `ProyectosController`       | Proyectos activos/completados, colapso por área, stats en tiempo real |
| 7  | `/espera`      | `EsperaController`          | En espera de, filtros, vencidos |
| 8  | `/someday`     | `SomedayController`         | Algún día / tal vez |
| 9  | `/referencia`  | `ReferenciaController`      | Material de referencia |

### Pendientes (10–12)

| #  | Ruta base   | Controller           | Función GTD              |
|----|-------------|----------------------|--------------------------|
| 10 | `/revision` | `RevisionController` | Revisión semanal         |
| 11 | `/config`   | `ConfigController`   | Áreas, contextos, personas |
| 12 | —           | —                    | Calendario (citas)       |

## Decisiones técnicas establecidas

### Flujo de procesamiento GTD
- Ítem que se convierte en **proyecto**: el ítem original queda `tipo = 'completada'`; se crea un registro nuevo en la tabla `proyectos`.
- El modal de procesamiento tiene **modo `agregar-accion`** cuando se abre desde la vista de proyectos (añade una `proyecto_accion` al proyecto padre, sin re-procesar el ítem).
- El campo `contexto` **no aplica** a proyectos (solo a `accion`, `proyecto_accion`, `delegada`).

### Schema — columnas añadidas
- `items`: `etiquetas` (JSON/TEXT)
- `items`: `fecha_revision` (DATE)
- `items`: `resultado_deseado` (TEXT)
- `areas`: `color` (VARCHAR)
- `contextos`: `color` (VARCHAR)
- `personas`: `rol` (VARCHAR)

### Frontend / Bootstrap
- `Bootstrap.Modal` **no se instancia al inicio del IIFE**. Usar siempre `bootstrap.Modal.getOrCreateInstance(el)` en el momento de uso para evitar dobles listeners.
- Delegación de eventos **sobre el contenedor/lista padre** (no `querySelector` directo sobre ítems renderizados por PHP), para que funcione tras re-render dinámico.

## Patrones establecidos

| Patrón | Dónde aplica |
|--------|--------------|
| `$this->layout('modulo.vista', $data)` | Toda vista que use el dashboard con sidebar |
| `SidebarCounters::get($userId)` | Llamada única dentro de `layout()` para los badges del sidebar |
| Alias explícitos en todos los `JOIN` | Modelos con consultas multi-tabla (evitar columnas ambiguas) |
| `POST` para acciones AJAX | Toda llamada fetch/XHR que muta estado |
| `GET` para vistas | Todas las rutas que devuelven HTML |
