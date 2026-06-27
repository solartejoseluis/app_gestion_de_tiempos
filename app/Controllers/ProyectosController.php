<?php
declare(strict_types=1);

class ProyectosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid   = (int) $_SESSION['usuario_id'];
        $db    = Database::connection();
        $model = new ProyectoModel();
        $todos = $model->getProyectosConStats($uid);

        // Separar completados de activos/pausados
        $proyectosCompletados = array_values(array_filter($todos, fn($p) => $p['estado'] === 'completado'));
        $activos              = array_filter($todos, fn($p) => $p['estado'] !== 'completado');

        // Agrupar activos/pausados por área
        $grouped = [];
        foreach ($activos as $p) {
            $key = $p['area_id'] ?? 0;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'area_nombre' => $p['area_nombre'] ?? 'Sin área',
                    'area_color'  => $p['area_color']  ?? '#999999',
                    'proyectos'   => [],
                ];
            }
            $grouped[$key]['proyectos'][] = $p;
        }

        $totalActivos = count(array_filter($activos, fn($p) => $p['estado'] === 'activo'));

        $stmt = $db->prepare(
            'SELECT id, nombre FROM areas WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $areas = $stmt->fetchAll();

        $this->layout('proyectos.index', [
            'pageTitle'            => 'Proyectos',
            'currentRoute'         => '/proyectos',
            'grouped'              => $grouped,
            'totalActivos'         => $totalActivos,
            'proyectosCompletados' => $proyectosCompletados,
            'areas'                => $areas,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }

        $body = (array) json_decode(file_get_contents('php://input'), true);

        $sets   = [];
        $params = [];

        if (array_key_exists('nombre', $body)) {
            $nombre = trim((string) ($body['nombre'] ?? ''));
            if ($nombre === '') {
                $this->error('El nombre es obligatorio.');
            }
            $sets[]   = 'nombre = ?';
            $params[] = $nombre;
        }

        if (array_key_exists('resultado_deseado', $body)) {
            $sets[]   = 'resultado_deseado = ?';
            $params[] = trim((string) ($body['resultado_deseado'] ?? '')) ?: null;
        }

        if (array_key_exists('area_id', $body)) {
            $areaId   = $body['area_id'] === null || $body['area_id'] === ''
                        ? null
                        : (int) $body['area_id'];
            $sets[]   = 'area_id = ?';
            $params[] = $areaId;
        }

        if (empty($sets)) {
            $this->error('No hay campos a actualizar.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM proyectos
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $params[] = $id;
        $db->prepare(
            'UPDATE proyectos SET ' . implode(', ', $sets) . ' WHERE id = ?'
        )->execute($params);

        $this->json(null);
    }

    public function completar(): void
    {
        $this->requireAuth();
        $id  = (int) $this->input('id', 0);
        $uid = (int) $_SESSION['usuario_id'];
        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        $model = new ProyectoModel();
        if (!$model->actualizarEstado($id, $uid, ['estado' => 'completado'])) {
            $this->error('No autorizado.', 403);
        }
        $this->json(null);
    }

    public function pausar(): void
    {
        $this->requireAuth();
        $id  = (int) $this->input('id', 0);
        $uid = (int) $_SESSION['usuario_id'];
        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        $model = new ProyectoModel();
        if (!$model->actualizarEstado($id, $uid, ['estado' => 'pausa'])) {
            $this->error('No autorizado.', 403);
        }
        $this->json(null);
    }

    public function reactivar(): void
    {
        $this->requireAuth();
        $id  = (int) $this->input('id', 0);
        $uid = (int) $_SESSION['usuario_id'];
        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        $model = new ProyectoModel();
        if (!$model->actualizarEstado($id, $uid, ['estado' => 'activo'])) {
            $this->error('No autorizado.', 403);
        }
        $this->json(null);
    }

    public function eliminar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM proyectos
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $db->beginTransaction();
        try {
            $db->prepare(
                'UPDATE items SET deleted_at = NOW()
                 WHERE proyecto_id = ? AND deleted_at IS NULL'
            )->execute([$id]);

            $db->prepare(
                'UPDATE proyectos SET deleted_at = NOW()
                 WHERE id = ? AND usuario_id = ?'
            )->execute([$id, $uid]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->error('Error al eliminar el proyecto.');
        }

        $this->json(null);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $nombre = trim($this->input('nombre', ''));
        if ($nombre === '') {
            $this->error('El nombre del proyecto es obligatorio.');
        }

        $areaId           = (int) $this->input('area_id', 0) ?: null;
        $resultadoDeseado = trim($this->input('resultado_deseado', '')) ?: null;

        $db = Database::connection();
        $db->prepare(
            'INSERT INTO proyectos (usuario_id, nombre, area_id, resultado_deseado, estado)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$uid, $nombre, $areaId, $resultadoDeseado, 'activo']);

        $id = (int) $db->lastInsertId();

        $areaNombre = '';
        if ($areaId) {
            $stmt = $db->prepare('SELECT nombre FROM areas WHERE id = ? AND usuario_id = ? LIMIT 1');
            $stmt->execute([$areaId, $uid]);
            $area = $stmt->fetch();
            $areaNombre = $area ? $area['nombre'] : '';
        }

        $this->json(['id' => $id, 'nombre' => $nombre, 'area_nombre' => $areaNombre]);
    }

    public function verAcciones(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id, nombre, resultado_deseado, estado, area_id
             FROM proyectos
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        $proyecto = $stmt->fetch();

        if (!$proyecto) {
            $this->redirect('/proyectos');
        }

        $stmt = $db->prepare(
            "SELECT i.*, c.nombre AS contexto_nombre, c.color AS contexto_color
             FROM items i
             LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
             WHERE i.proyecto_id = ?
               AND i.usuario_id = ?
               AND i.tipo = 'proyecto_accion'
               AND i.deleted_at IS NULL
             ORDER BY ISNULL(i.fecha_accion) ASC, i.fecha_accion ASC, i.created_at DESC"
        );
        $stmt->execute([$id, $uid]);
        $acciones = $stmt->fetchAll();

        $stmt = $db->prepare(
            "SELECT i.*, c.nombre AS contexto_nombre
             FROM items i
             LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
             WHERE i.proyecto_id = ?
               AND i.usuario_id = ?
               AND i.tipo = 'completada'
               AND i.deleted_at IS NULL
             ORDER BY i.fecha_completada DESC
             LIMIT 50"
        );
        $stmt->execute([$id, $uid]);
        $completadas = $stmt->fetchAll();

        $this->layout('proyectos.acciones', [
            'pageTitle'    => $proyecto['nombre'],
            'currentRoute' => '/proyectos',
            'proyecto'     => $proyecto,
            'acciones'     => $acciones,
            'completadas'  => $completadas,
        ]);
    }

    public function stats(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];
        $id  = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->error('ID inválido.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare("
            SELECT
                SUM(CASE WHEN i.id IS NOT NULL AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                       AS total_items,
                SUM(CASE WHEN i.id IS NOT NULL AND i.tipo = 'completada'
                              AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                       AS items_completados,
                SUM(CASE WHEN i.id IS NOT NULL
                              AND i.tipo IN ('accion','proyecto_accion')
                              AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                       AS proximas_acciones
            FROM proyectos p
            LEFT JOIN items i ON i.proyecto_id = p.id
            WHERE p.id = ? AND p.usuario_id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$id, $uid]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->error('No encontrado.', 404);
        }

        $this->json([
            'total_items'       => (int) $row['total_items'],
            'items_completados' => (int) $row['items_completados'],
            'proximas_acciones' => (int) $row['proximas_acciones'],
        ]);
    }
}
