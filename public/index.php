<?php
/**
 * GTD App — Punto de entrada único (Front Controller)
 * Todas las peticiones pasan por aquí gracias al .htaccess
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Cargar variables de entorno
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Autoloader simple (sin Composer por ahora)
spl_autoload_register(function (string $class): void {
    $paths = [
        BASE_PATH . '/app/Controllers/' . $class . '.php',
        BASE_PATH . '/app/Models/'      . $class . '.php',
        BASE_PATH . '/app/Core/'        . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Cargar configuración
require_once BASE_PATH . '/config/app.php';

// Iniciar sesión
session_name($_ENV['SESSION_NAME'] ?? 'gtd_session');
session_start();

// Iniciar el router
require_once BASE_PATH . '/app/Core/Router.php';
$router = new Router();
require_once BASE_PATH . '/config/app.php';
$router->dispatch();
