<?php
declare(strict_types=1);

class SomedayController extends Controller
{
    private function uid(): int
    {
        return (int) $_SESSION['usuario_id'];
    }

    private function assertItem(int $id): void
    {
        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items
             WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $this->uid()]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }
    }

    public function index(): void
    {
        $this->requireAuth();
        $uid = $this->uid();
        $db  = Database::connection();

        $model = new SomedayModel();
        $items = $model->getSomeday($uid);

        $stmt = $db->prepare(
            'SELECT id, nombre FROM areas
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmt->execute([$uid]);
        $areas = $stmt->fetchAll();

        $this->layout('someday.index', [
            'pageTitle'    => 'Algún día / Quizás',
            'currentRoute' => '/someday',
            'items'        => $items,
            'areas'        => $areas,
        ]);
    }

    public function activar(): void
    {
        $this->requireAuth();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) $this->error('ID inválido.');
        $this->assertItem($id);

        Database::connection()
            ->prepare("UPDATE items SET tipo = 'accion' WHERE id = ? AND usuario_id = ?")
            ->execute([$id, $this->uid()]);

        $this->json(null);
    }

    public function posponer(): void
    {
        $this->requireAuth();
        $id            = (int) ($_POST['id'] ?? 0);
        $fechaRevision = trim($_POST['fecha_revision'] ?? '');
        if ($id <= 0)              $this->error('ID inválido.');
        if ($fechaRevision === '') $this->error('La fecha es obligatoria.');
        $this->assertItem($id);

        Database::connection()
            ->prepare('UPDATE items SET fecha_revision = ? WHERE id = ?')
            ->execute([$fechaRevision, $id]);

        $this->json(null);
    }

    public function eliminar(): void
    {
        $this->requireAuth();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) $this->error('ID inválido.');
        $this->assertItem($id);

        Database::connection()
            ->prepare('UPDATE items SET deleted_at = NOW() WHERE id = ?')
            ->execute([$id]);

        $this->json(null);
    }
}
