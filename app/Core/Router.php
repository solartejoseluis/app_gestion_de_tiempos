<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $method): void
    {
        $this->routes['GET'][$path] = [$controller, $method];
    }

    public function post(string $path, string $controller, string $method): void
    {
        $this->routes['POST'][$path] = [$controller, $method];
    }

    public function patch(string $path, string $controller, string $method): void
    {
        $this->routes['PATCH'][$path] = [$controller, $method];
    }

    public function delete(string $path, string $controller, string $method): void
    {
        $this->routes['DELETE'][$path] = [$controller, $method];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        // Soporte para _method en formularios HTML (PUT, PATCH, DELETE)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Rutas definidas en config/app.php
        $this->registerRoutes();

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{[a-z]+\}/', '([^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
                array_shift($matches);
                [$controllerName, $action] = $handler;
                $controller = new $controllerName();
                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo '404 — Página no encontrada';
    }

    private function registerRoutes(): void
    {
        // Autenticación
        $this->get('/',              'AuthController',    'showLogin');
        $this->get('/login',         'AuthController',    'showLogin');
        $this->post('/login',        'AuthController',    'login');
        $this->get('/logout',        'AuthController',    'logout');

        // Dashboard
        $this->get('/dashboard',       'InboxController', 'index');

        // Inbox
        $this->get('/inbox/lista',     'InboxController', 'lista');
        $this->get('/inbox',           'InboxController', 'index');
        $this->post('/inbox/store',    'InboxController', 'store');
        $this->post('/inbox/delete',   'InboxController', 'destroy');
        $this->post('/inbox',          'InboxController', 'store');
        $this->delete('/inbox/{id}',   'InboxController', 'destroy');
        $this->patch('/inbox/{id}',    'InboxController', 'procesar');

        // Procesamiento de ítems
        $this->post('/procesar/areas',      'ProcesamientoController', 'areas');
        $this->post('/procesar/proyectos',  'ProcesamientoController', 'proyectos');
        $this->post('/procesar/personas',   'ProcesamientoController', 'personas');
        $this->post('/procesar/contextos',  'ProcesamientoController', 'contextos');
        $this->post('/procesar/eliminar',   'ProcesamientoController', 'eliminar');
        $this->post('/procesar/completar',  'ProcesamientoController', 'completar');
        $this->post('/procesar/incubar',    'ProcesamientoController', 'incubar');
        $this->post('/procesar/referencia', 'ProcesamientoController', 'referencia');
        $this->post('/procesar/programar',  'ProcesamientoController', 'programar');
        $this->post('/procesar/delegar',    'ProcesamientoController', 'delegar');
        $this->post('/procesar/proyecto',      'ProcesamientoController', 'proyecto');
        $this->post('/procesar/nueva-accion', 'ProcesamientoController', 'nuevaAccion');

        // Próximas acciones
        $this->get('/acciones',                  'AccionesController', 'index');
        $this->post('/acciones/completar',       'AccionesController', 'completar');
        $this->patch('/acciones/{id}',           'AccionesController', 'update');
        $this->patch('/acciones/{id}/completar', 'AccionesController', 'completar');
        $this->delete('/acciones/{id}',          'AccionesController', 'destroy');

        // Proyectos
        $this->get('/proyectos/stats',              'ProyectosController', 'stats');
        $this->get('/proyectos',                    'ProyectosController', 'index');
        $this->post('/proyectos/crear',              'ProyectosController', 'crear');
        $this->post('/proyectos/completar',         'ProyectosController', 'completar');
        $this->post('/proyectos/pausar',            'ProyectosController', 'pausar');
        $this->post('/proyectos/reactivar',         'ProyectosController', 'reactivar');
        $this->post('/proyectos',                   'ProyectosController', 'store');
        $this->patch('/proyectos/{id}',             'ProyectosController', 'update');
        $this->patch('/proyectos/{id}/completar',   'ProyectosController', 'completar');
        $this->patch('/proyectos/{id}/pausar',      'ProyectosController', 'pausar');

        // En espera de
        $this->get('/espera',                'EsperaController', 'index');
        $this->post('/espera/recibido',      'EsperaController', 'recibido');
        $this->post('/espera/posponer',      'EsperaController', 'posponer');
        $this->post('/espera/convertir',     'EsperaController', 'convertir');

        // Algún día
        $this->get('/someday',              'SomedayController', 'index');
        $this->post('/someday/activar',     'SomedayController', 'activar');
        $this->post('/someday/posponer',    'SomedayController', 'posponer');
        $this->post('/someday/eliminar',    'SomedayController', 'eliminar');

        // Referencia
        $this->get('/referencia',                   'ReferenciaController', 'index');
        $this->post('/referencia/eliminar',         'ReferenciaController', 'eliminar');
        $this->post('/referencia/editar-etiquetas', 'ReferenciaController', 'editarEtiquetas');
        $this->post('/referencia/activar',          'ReferenciaController', 'activar');

        // Completadas
        $this->get('/completadas',         'InboxController',      'completadas');

        // Revisión semanal
        $this->get('/revision',                        'RevisionController', 'index');
        $this->get('/revision/historial',              'RevisionController', 'historial');
        $this->get('/revision/cierre',                 'RevisionController', 'cierre');
        $this->get('/revision/paso/{paso}',            'RevisionController', 'verPaso');
        $this->post('/revision/iniciar',               'RevisionController', 'iniciar');
        $this->post('/revision/paso/{paso}/completar', 'RevisionController', 'completarPaso');
        $this->post('/revision/completar',             'RevisionController', 'completarRevision');

        // Configuración
        $this->get('/config/exportar',                 'ConfigController', 'exportar');
        $this->get('/config',                          'ConfigController', 'index');
        $this->post('/config/areas',                   'ConfigController', 'crearArea');
        $this->patch('/config/areas/{id}',             'ConfigController', 'editarArea');
        $this->post('/config/areas/{id}/archivar',     'ConfigController', 'archivarArea');
        $this->post('/config/areas/{id}/restaurar',    'ConfigController', 'restaurarArea');
        $this->delete('/config/areas/{id}',            'ConfigController', 'eliminarArea');
        $this->post('/config/contextos/sugeridos',         'ConfigController', 'cargarContextosSugeridos');
        $this->post('/config/contextos',                   'ConfigController', 'crearContexto');
        $this->patch('/config/contextos/{id}',             'ConfigController', 'editarContexto');
        $this->post('/config/contextos/{id}/archivar',     'ConfigController', 'archivarContexto');
        $this->post('/config/contextos/{id}/restaurar',    'ConfigController', 'restaurarContexto');
        $this->delete('/config/contextos/{id}',            'ConfigController', 'eliminarContexto');
        $this->post('/config/personas',                    'ConfigController', 'crearPersona');
        $this->patch('/config/personas/{id}',              'ConfigController', 'editarPersona');
        $this->post('/config/personas/{id}/archivar',      'ConfigController', 'archivarPersona');
        $this->post('/config/personas/{id}/restaurar',     'ConfigController', 'restaurarPersona');
        $this->delete('/config/personas/{id}',             'ConfigController', 'eliminarPersona');
    }
}
