<?php
declare(strict_types=1);

class ConfigController extends Controller
{
    // ------------------------------------------------------------------ index

    public function index(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $areas     = (new AreaModel())->findAllWithStats($uid);
        $contextos = (new ContextoModel())->findAllWithStats($uid);
        $personas  = (new PersonaModel())->findAllWithStats($uid);

        $todos  = (new ProyectoModel())->getProyectosConStats($uid);
        $proyectosCompletados = array_values(array_filter($todos, fn($p) => $p['estado'] === 'completado'));
        $activos              = array_filter($todos, fn($p) => $p['estado'] !== 'completado');

        $proyectos = [];
        foreach ($activos as $p) {
            $key = $p['area_id'] ?? 0;
            if (!isset($proyectos[$key])) {
                $proyectos[$key] = [
                    'area_nombre' => $p['area_nombre'] ?? 'Sin área',
                    'area_color'  => $p['area_color']  ?? '#999999',
                    'proyectos'   => [],
                ];
            }
            $proyectos[$key]['proyectos'][] = $p;
        }

        $this->layout('config.index', [
            'pageTitle'            => 'Configuración',
            'currentRoute'         => '/config',
            'areas'                => $areas,
            'contextos'            => $contextos,
            'personas'             => $personas,
            'proyectos'            => $proyectos,
            'proyectosCompletados' => $proyectosCompletados,
        ]);
    }

    // --------------------------------------------------------------- exportar

    public function exportar(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $data = [
            'exportado_en' => date('Y-m-d\TH:i:s'),
            'app'          => 'GTD App',
            'areas'        => (new AreaModel())->findAllWithStats($uid),
            'contextos'    => (new ContextoModel())->findAllWithStats($uid),
            'personas'     => (new PersonaModel())->findAllWithStats($uid),
            'proyectos'    => (new ProyectoModel())->getProyectosConStats($uid),
        ];

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="gtd-config-' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------------------- áreas

    public function crearArea(): void
    {
        $this->requireAuth();
        $uid    = (int) $_SESSION['usuario_id'];
        $nombre      = trim($this->input('nombre', ''));
        $color       = trim($this->input('color', '#4a90d9'));
        $descripcion = trim($this->input('descripcion', ''));

        if ($nombre === '') {
            $this->error('El nombre del área es obligatorio.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM areas
              WHERE usuario_id = ? AND nombre = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$uid, $nombre]);
        if ($stmt->fetch()) {
            $this->error('Ya existe un área con ese nombre.');
        }

        $areaModel = new AreaModel();
        $id        = $areaModel->crear([
            'usuario_id'  => $uid,
            'nombre'      => $nombre,
            'descripcion' => $descripcion ?: null,
            'color'       => $color,
            'estado'      => 'activo',
        ]);

        $this->json(['id' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion ?: null, 'color' => $color]);
    }

    public function editarArea(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsArea($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        // Acepta tanto form-data (PATCH via _method) como JSON
        $body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents('php://input'), true);

        $campos = [];

        if (isset($body['nombre'])) {
            $nombre = trim($body['nombre']);
            if ($nombre === '') {
                $this->error('El nombre no puede estar vacío.');
            }
            $campos['nombre'] = $nombre;
        }
        if (isset($body['color'])) {
            $campos['color'] = $body['color'];
        }
        if (isset($body['estado']) && in_array($body['estado'], ['activo', 'archivado'], true)) {
            $campos['estado'] = $body['estado'];
        }
        if (isset($body['descripcion'])) {
            $campos['descripcion'] = trim($body['descripcion']) ?: null;
        }

        if (!empty($campos)) {
            (new AreaModel())->actualizar($id, $campos);
        }

        $this->json(null);
    }

    public function archivarArea(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsArea($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new AreaModel())->archivar($id);

        $this->json(null);
    }

    public function restaurarArea(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsArea($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new AreaModel())->restaurar($id);

        $this->json(null);
    }

    public function eliminarArea(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsArea($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $modelo  = new AreaModel();
        $activos = $modelo->tieneItemsActivos($id);

        if ($activos['proyectos'] > 0 || $activos['items'] > 0) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'ok'        => false,
                'error'     => 'No se puede eliminar un área con ítems activos.',
                'proyectos' => $activos['proyectos'],
                'items'     => $activos['items'],
            ]);
            exit;
        }

        $modelo->eliminar($id);

        $this->json(null);
    }

    // --------------------------------------------------------------- contextos

    public function crearContexto(): void
    {
        $this->requireAuth();
        $uid         = (int) $_SESSION['usuario_id'];
        $nombre      = trim($this->input('nombre', ''));
        $descripcion = trim($this->input('descripcion', ''));
        $color       = trim($this->input('color', '#6c757d'));

        if ($nombre === '') {
            $this->error('El nombre del contexto es obligatorio.');
        }

        $modelo = new ContextoModel();
        if ($modelo->existeNombre($nombre, $uid)) {
            $this->error('Ya existe un contexto con ese nombre.');
        }

        $id = $modelo->crear([
            'usuario_id'  => $uid,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'color'       => $color,
            'estado'      => 'activo',
        ]);

        $this->json([
            'id'          => $id,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'color'       => $color,
        ]);
    }

    public function editarContexto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsContexto($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents('php://input'), true);

        $campos = [];

        if (isset($body['nombre'])) {
            $nombre = trim($body['nombre']);
            if ($nombre === '') {
                $this->error('El nombre no puede estar vacío.');
            }
            if ((new ContextoModel())->existeNombre($nombre, $uid, $id)) {
                $this->error('Ya existe un contexto con ese nombre.');
            }
            $campos['nombre'] = $nombre;
        }
        if (isset($body['descripcion'])) {
            $campos['descripcion'] = trim($body['descripcion']);
        }
        if (isset($body['color'])) {
            $campos['color'] = $body['color'];
        }
        if (isset($body['estado']) && in_array($body['estado'], ['activo', 'archivado'], true)) {
            $campos['estado'] = $body['estado'];
        }

        if (!empty($campos)) {
            (new ContextoModel())->actualizar($id, $campos);
        }

        $this->json(null);
    }

    public function archivarContexto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsContexto($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new ContextoModel())->archivar($id);

        $this->json(null);
    }

    public function restaurarContexto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsContexto($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new ContextoModel())->restaurar($id);

        $this->json(null);
    }

    public function eliminarContexto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsContexto($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $modelo  = new ContextoModel();
        $activos = $modelo->tieneItemsActivos($id);

        if ($activos > 0) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'ok'      => false,
                'error'   => 'No se puede eliminar un contexto con acciones activas.',
                'activos' => $activos,
            ]);
            exit;
        }

        $modelo->eliminar($id);

        $this->json(null);
    }

    public function cargarContextosSugeridos(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $insertados = (new ContextoModel())->cargarSugeridos($uid);

        $this->json(['insertados' => $insertados]);
    }

    // --------------------------------------------------------------- personas

    public function crearPersona(): void
    {
        $this->requireAuth();
        $uid    = (int) $_SESSION['usuario_id'];
        $nombre = trim($this->input('nombre', ''));
        $rol    = trim($this->input('rol', ''));

        if ($nombre === '') {
            $this->error('El nombre de la persona es obligatorio.');
        }

        $modelo = new PersonaModel();
        if ($modelo->existeNombre($nombre, $uid)) {
            $this->error('Ya existe una persona con ese nombre.');
        }

        $id = $modelo->crear([
            'usuario_id' => $uid,
            'nombre'     => $nombre,
            'rol'        => $rol,
            'estado'     => 'activo',
        ]);

        $this->json([
            'id'              => $id,
            'nombre'          => $nombre,
            'rol'             => $rol,
            'estado'          => 'activo',
            'tareas_activas'  => 0,
            'tareas_vencidas' => 0,
        ]);
    }

    public function editarPersona(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsPersona($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents('php://input'), true);

        $campos = [];

        if (isset($body['nombre'])) {
            $nombre = trim($body['nombre']);
            if ($nombre === '') {
                $this->error('El nombre no puede estar vacío.');
            }
            if ((new PersonaModel())->existeNombre($nombre, $uid, $id)) {
                $this->error('Ya existe una persona con ese nombre.');
            }
            $campos['nombre'] = $nombre;
        }
        if (isset($body['rol'])) {
            $campos['rol'] = trim($body['rol']);
        }
        if (isset($body['estado']) && in_array($body['estado'], ['activo', 'archivado'], true)) {
            $campos['estado'] = $body['estado'];
        }

        if (!empty($campos)) {
            (new PersonaModel())->actualizar($id, $campos);
        }

        $this->json(null);
    }

    public function archivarPersona(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsPersona($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new PersonaModel())->archivar($id);

        $this->json(null);
    }

    public function restaurarPersona(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsPersona($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new PersonaModel())->restaurar($id);

        $this->json(null);
    }

    public function eliminarPersona(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsPersona($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $modelo = new PersonaModel();
        $tareas = $modelo->tieneTareasActivas($id);

        if ($tareas > 0) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'ok'     => false,
                'error'  => 'No se puede eliminar una persona con tareas activas.',
                'tareas' => $tareas,
            ]);
            exit;
        }

        $modelo->eliminar($id);

        $this->json(null);
    }

    // ----------------------------------------------------------------- helpers

    private function ownsArea(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM areas
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        return (bool) $stmt->fetch();
    }

    private function ownsContexto(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM contextos
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        return (bool) $stmt->fetch();
    }

    private function ownsPersona(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM personas
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        return (bool) $stmt->fetch();
    }
}
