# GTD App

Aplicación de gestión de tiempos basada en la metodología **Getting Things Done** (GTD) de David Allen.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.4 |
| Frontend | Bootstrap 5 + JavaScript + DataTables |
| Base de datos | MariaDB 11.4 |
| Servidor web | Apache 2.4 |
| Entorno de desarrollo | Docker + Docker Compose |
| Control de versiones | Git + GitHub (repositorio privado) |

## Arquitectura

El proyecto sigue el patrón **MVC (Modelo-Vista-Controlador)** con un front controller único (`public/index.php`) y un router personalizado.

## Requisitos previos

- Docker >= 24
- Docker Compose >= 2
- Git

## Instalación y arranque

```bash
# 1. Clonar el repositorio
git clone https://github.com/[usuario]/gtd-app.git
cd gtd-app

# 2. Copiar variables de entorno
cp .env.example .env
# Editar .env con tus valores

# 3. Construir e iniciar los contenedores
docker compose up -d --build

# 4. Verificar que los contenedores estén corriendo
docker compose ps
```

## URLs de desarrollo

| Servicio | URL |
|---|---|
| Aplicación | http://localhost |
| phpMyAdmin | http://localhost:8080 |

## Comandos útiles Docker

```bash
# Iniciar contenedores
docker compose up -d

# Detener contenedores
docker compose down

# Ver logs de la app
docker compose logs -f app

# Acceder al contenedor PHP
docker compose exec app bash

# Reconstruir imagen tras cambios en Dockerfile
docker compose up -d --build
```

## Estructura del proyecto

```
gtd-app/
├── app/
│   ├── Controllers/    # Lógica de cada módulo
│   ├── Models/         # Acceso a datos
│   ├── Views/          # Plantillas PHP
│   └── Core/           # Router, Database, Controller base, Model base
├── config/             # Configuración de la app
├── database/           # Schema SQL y seeds
├── docker/             # Dockerfile y configuración Apache
├── public/             # Front controller, assets, .htaccess
├── .env.example        # Variables de entorno de referencia
├── CHANGELOG.md        # Control de cambios
└── docker-compose.yml
```

## Documentación del proyecto

| Documento | Descripción |
|---|---|
| Especificación Técnica Funcional v1.0 | Flujos, reglas de negocio, módulos |
| Anexo A — Referencia técnica | Esquema SQL, API REST, estados de ítems |
| Anexo B — Hoja de ruta tecnológica | Evolución futura: React y Flutter |

## Control de versiones

Los commits se realizan manualmente siguiendo este formato:

```
tipo(alcance): descripción breve

Descripción detallada opcional.

Refs: #issue
```

Tipos: `feat`, `fix`, `refactor`, `style`, `docs`, `chore`, `test`

## Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para el historial completo de cambios.
