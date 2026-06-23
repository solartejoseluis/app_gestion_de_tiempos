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

        $this->layout('acciones.index', [
            'pageTitle'    => 'Próximas acciones',
            'currentRoute' => '/acciones',
            'items'        => $items,
            'contextos'    => $contextos,
            'areas'        => $areas,
            'proyectos'    => $proyectos,
        ]);
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
}
