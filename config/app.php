<?php
declare(strict_types=1);

// Configuración global de la aplicación
define('APP_NAME',    $_ENV['APP_NAME']  ?? 'GTD App');
define('APP_ENV',     $_ENV['APP_ENV']   ?? 'development');
define('APP_DEBUG',  ($_ENV['APP_DEBUG'] ?? 'false') === 'true');
define('APP_URL',     $_ENV['APP_URL']   ?? 'http://localhost');

// Manejo de errores según entorno
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Zona horaria
date_default_timezone_set('America/Bogota');
