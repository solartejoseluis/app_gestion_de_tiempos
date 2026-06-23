# Changelog — GTD App

Todos los cambios relevantes del proyecto se documentan en este archivo.
El formato sigue [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).
El versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [Sin lanzar]

### Añadido
- Estructura inicial del proyecto MVC
- Configuración Docker: PHP 8.4 + Apache + MariaDB 11.4 + phpMyAdmin
- Router MVC con soporte GET, POST, PATCH, DELETE
- Clases Core: Database (PDO), Controller base, Model base
- Archivo .env.example con todas las variables de entorno
- Esquema SQL completo de la base de datos (Anexo A)
- .gitignore configurado para el stack PHP+Docker

---

## Convenciones de este archivo

Cada entrada de cambio debe incluir:
- **Versión** en formato [MAYOR.MENOR.PARCHE]
- **Fecha** en formato YYYY-MM-DD
- **Tipo de cambio**:
  - `Añadido` — nuevas funcionalidades
  - `Modificado` — cambios en funcionalidades existentes
  - `Corregido` — corrección de bugs
  - `Eliminado` — funcionalidades removidas
  - `Seguridad` — correcciones de vulnerabilidades
  - `Infraestructura` — cambios en Docker, configuración, dependencias

---
