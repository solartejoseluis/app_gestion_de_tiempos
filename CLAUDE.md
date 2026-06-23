# Instrucciones para Claude Code — GTD App

## Descripción del proyecto
Aplicación de gestión de tiempos basada en la metodología GTD (Getting Things Done) de David Allen.
Stack: PHP 8.4 + MariaDB 11.4 + Bootstrap 5 + JavaScript + DataTables. Arquitectura MVC.

## Reglas de trabajo

### Git — MUY IMPORTANTE
- **NO hacer commits automáticamente**. El desarrollador los hace manualmente.
- **NO hacer push**. El desarrollador decide cuándo.
- Puedes sugerir el mensaje de commit pero nunca ejecutar `git commit` ni `git push`.

### Arquitectura MVC
- Los Controllers viven en `app/Controllers/`. Extienden la clase `Controller`.
- Los Models viven en `app/Models/`. Extienden la clase `Model`.
- Las Views viven en `app/Views/`. Son archivos PHP con HTML.
- El Core del sistema está en `app/Core/` — modificar solo si es estrictamente necesario.
- Las rutas se definen en `app/Core/Router.php` método `registerRoutes()`.

### Base de datos
- Usar **siempre PDO con prepared statements**. Nunca concatenar SQL con variables de usuario.
- La conexión se obtiene con `Database::connection()`.
- Los Models heredan de la clase `Model` que ya tiene métodos base: `findAll`, `findOne`, `insert`, `update`, `softDelete`.

### Seguridad
- Validar y sanitizar todos los inputs antes de procesarlos.
- Usar `$this->requireAuth()` en todos los controllers que requieren sesión activa.
- Las contraseñas se hashean con `password_hash()` y se verifican con `password_verify()`.

### Estilo de código PHP
- Siempre declarar `declare(strict_types=1)` al inicio de cada archivo.
- Usar tipos en parámetros y retornos de funciones cuando sea posible.
- Nombres de clases: PascalCase. Métodos y variables: camelCase.

### Frontend
- Bootstrap 5 para layout y componentes base.
- DataTables para tablas con filtrado, ordenamiento y paginación.
- JavaScript vanilla o jQuery para interactividad.
- Los modales de Bootstrap se controlan desde los archivos JS en `public/js/`.

### Variables de entorno
- Nunca hardcodear credenciales. Siempre usar `$_ENV['VARIABLE']`.
- El archivo `.env` no se sube a Git (está en .gitignore).

### Changelog
- Sugerir la entrada del CHANGELOG.md para cada funcionalidad completada.
- El desarrollador decide si la agrega y cuándo.

## Estructura de archivos
```
app/Controllers/   — un archivo por módulo GTD
app/Models/        — un archivo por entidad de datos
app/Views/         — subdirectorios por módulo
app/Core/          — Router, Database, Controller, Model
config/            — app.php (constantes globales)
database/          — schema.sql y seeds
public/            — index.php, .htaccess, css/, js/, assets/
```

## Contexto GTD importante
- El árbol de decisión GTD tiene 5 bifurcaciones. Ver Anexo A del proyecto.
- Los ítems tienen 8 tipos: inbox, accion, proyecto_accion, delegada, incubada, referencia, completada, eliminada.
- El contexto (@) es obligatorio para acciones tipo 'accion', 'proyecto_accion' y 'delegada'.
- El calendario solo recibe ítems con tipo_tiempo = 'cita' (día y hora fijos).
