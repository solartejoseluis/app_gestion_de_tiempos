<?php
declare(strict_types=1);

class AccionesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

        $model = new AccionModel();
        $items = $model->getAcciones($uid);

        $stmt = $db->prepare(
            'SELECT id, nombre, color FROM contextos
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $contextos = $stmt->fetchAll();

        $stmt = $db->prepare(
            'SELECT id, nombre FROM areas
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $areas = $stmt->fetchAll();

        $stmt = $db->prepare(
            "SELECT id, nombre FROM proyectos
             WHERE usuario_id = ? AND estado = 'activo' AND deleted_at IS NULL ORDER BY nombre"
        );
        $stmt->execute([$uid]);
        $proyectos = $stmt->fetchAll();

        $filtroProyectoId = (int) ($_GET['proyecto_id'] ?? 0);

        $this->layout('acciones.index', [
            'pageTitle'         => 'Próximas acciones',
            'currentRoute'      => '/acciones',
            'items'             => $items,
            'contextos'         => $contextos,
            'areas'             => $areas,
            'proyectos'         => $proyectos,
            'filtroProyectoId'  => $filtroProyectoId,
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

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        // Soporta form-urlencoded (_method=PATCH desde POST) y JSON
        $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
        $body   = $isJson
            ? (array) json_decode(file_get_contents('php://input'), true)
            : $_POST;

        $sets   = [];
        $params = [];

        if (array_key_exists('titulo', $body)) {
            $titulo = trim((string) ($body['titulo'] ?? ''));
            if ($titulo === '') {
                $this->error('El título es obligatorio.');
            }
            $sets[]   = 'titulo = ?';
            $params[] = $titulo;
        }

        if (array_key_exists('contexto_id', $body)) {
            $sets[]   = 'contexto_id = ?';
            $params[] = (int) ($body['contexto_id'] ?? 0) ?: null;
        }

        if (array_key_exists('fecha_accion', $body)) {
            $sets[]   = 'fecha_accion = ?';
            $params[] = trim((string) ($body['fecha_accion'] ?? '')) ?: null;
        }

        if (array_key_exists('notas', $body)) {
            $sets[]   = 'notas = ?';
            $params[] = $body['notas'] === '' ? null : $body['notas'];
        }

        if (empty($sets)) {
            $this->error('No hay campos a actualizar.');
        }

        $params[] = $id;
        $db->prepare(
            'UPDATE items SET ' . implode(', ', $sets) . ' WHERE id = ?'
        )->execute($params);

        $this->json(null);
    }

    public function reactivar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $db->prepare(
            "UPDATE items SET tipo = 'proyecto_accion', fecha_completada = NULL WHERE id = ?"
        )->execute([$id]);

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

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $db->prepare(
            "UPDATE items SET tipo = 'completada', fecha_completada = NOW() WHERE id = ?"
        )->execute([$id]);

        $this->json(null);
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $db->prepare(
            'UPDATE items SET deleted_at = NOW() WHERE id = ?'
        )->execute([$id]);

        $this->json(null);
    }
}
