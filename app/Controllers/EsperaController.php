<?php
declare(strict_types=1);

class EsperaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

        $model = new EsperaModel();
        $items = $model->getEspera($uid);

        $stmt = $db->prepare(
            'SELECT id, nombre FROM personas
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $personas = $stmt->fetchAll();

        $stmt = $db->prepare(
            'SELECT id, nombre FROM areas
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $areas = $stmt->fetchAll();

        $this->layout('espera.index', [
            'pageTitle'    => 'En espera de',
            'currentRoute' => '/espera',
            'items'        => $items,
            'personas'     => $personas,
            'areas'        => $areas,
        ]);
    }

    public function recibido(): void
    {
        $this->requireAuth();
        $id  = (int) ($_POST['id'] ?? 0);
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

    public function convertir(): void
    {
        $this->requireAuth();
        $id  = (int) ($_POST['id'] ?? 0);
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
            "UPDATE items SET tipo = 'accion', persona_id = NULL WHERE id = ?"
        )->execute([$id]);

        $this->json(null);
    }

    public function posponer(): void
    {
        $this->requireAuth();
        $id          = (int) ($_POST['id'] ?? 0);
        $fechaAccion = trim($_POST['fecha_accion'] ?? '');
        $uid         = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if ($fechaAccion === '') {
            $this->error('La fecha es obligatoria.');
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
            'UPDATE items SET fecha_accion = ? WHERE id = ?'
        )->execute([$fechaAccion, $id]);

        $this->json(null);
    }
}
