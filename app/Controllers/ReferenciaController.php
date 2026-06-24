<?php
declare(strict_types=1);

class ReferenciaController extends Controller
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

        $model = new ReferenciaModel();
        $items = $model->getReferencia($uid);

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

        $this->layout('referencia.index', [
            'pageTitle'    => 'Referencia',
            'currentRoute' => '/referencia',
            'items'        => $items,
            'areas'        => $areas,
            'proyectos'    => $proyectos,
        ]);
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

    public function editarEtiquetas(): void
    {
        $this->requireAuth();
        $id        = (int) ($_POST['id'] ?? 0);
        $etiquetas = trim($_POST['etiquetas'] ?? '');
        if ($id <= 0) $this->error('ID inválido.');
        $this->assertItem($id);

        $normalized = $etiquetas !== ''
            ? implode(',', array_filter(array_map('trim', explode(',', $etiquetas))))
            : null;

        Database::connection()
            ->prepare('UPDATE items SET etiquetas = ? WHERE id = ?')
            ->execute([$normalized, $id]);

        $this->json(['etiquetas' => $normalized ?? '']);
    }

    public function activar(): void
    {
        $this->requireAuth();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) $this->error('ID inválido.');
        $this->assertItem($id);

        Database::connection()
            ->prepare("UPDATE items SET tipo = 'inbox' WHERE id = ?")
            ->execute([$id]);

        $this->json(null);
    }
}
