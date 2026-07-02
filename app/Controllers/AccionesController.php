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

        if (array_key_exists('area_id', $body)) {
            $sets[]   = 'area_id = ?';
            $params[] = (int) ($body['area_id'] ?? 0) ?: null;
        }

        if (array_key_exists('contexto_id', $body)) {
            $sets[]   = 'contexto_id = ?';
            $params[] = (int) ($body['contexto_id'] ?? 0) ?: null;
        }

        if (array_key_exists('proyecto_id', $body)) {
            $sets[]   = 'proyecto_id = ?';
            $params[] = (int) ($body['proyecto_id'] ?? 0) ?: null;
        }

        if (array_key_exists('fecha_accion', $body)) {
            $sets[]   = 'fecha_accion = ?';
            $params[] = trim((string) ($body['fecha_accion'] ?? '')) ?: null;
        }

        if (array_key_exists('notas', $body)) {
            $sets[]   = 'notas = ?';
            $params[] = $body['notas'] === '' ? null : $body['notas'];
        }

        if (array_key_exists('hora_inicio', $body)) {
            $sets[]   = 'hora_inicio = ?';
            $params[] = trim((string) ($body['hora_inicio'] ?? '')) ?: null;
        }

        if (array_key_exists('hora_fin', $body)) {
            $sets[]   = 'hora_fin = ?';
            $params[] = trim((string) ($body['hora_fin'] ?? '')) ?: null;
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

    private function tipoTiempoValido(string $valor): string
    {
        return in_array($valor, ['ninguno', 'dia', 'cita'], true) ? $valor : 'ninguno';
    }

    public function crear(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $titulo     = trim($this->input('titulo', ''));
        $fecha      = trim($this->input('fecha_accion', ''));
        $tipoTiempo = $this->tipoTiempoValido($this->input('tipo_tiempo', 'ninguno'));
        $horaIni    = trim($this->input('hora_inicio', '')) ?: null;
        $horaFin    = trim($this->input('hora_fin', ''))    ?: null;
        $contextoId = (int) $this->input('contexto_id', 0)  ?: null;
        $proyectoId = (int) $this->input('proyecto_id', 0)  ?: null;
        $areaId     = (int) $this->input('area_id', 0)      ?: null;

        if ($titulo === '') {
            $this->error('El título es obligatorio.');
        }

        $tipo = $proyectoId ? 'proyecto_accion' : 'accion';

        $db = Database::connection();
        $db->prepare('
            INSERT INTO items
            (usuario_id, titulo, tipo, fecha_accion, tipo_tiempo,
             hora_inicio, hora_fin, contexto_id, proyecto_id, area_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $uid, $titulo, $tipo, $fecha ?: null, $tipoTiempo,
            $horaIni, $horaFin, $contextoId, $proyectoId, $areaId,
        ]);

        $this->json(['id' => $db->lastInsertId()]);
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
