<?php
declare(strict_types=1);

class ProyectosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid       = (int) $_SESSION['usuario_id'];
        $model     = new ProyectoModel();
        $proyectos = $model->getProyectosConStats($uid);

        // Agrupar por area_id en PHP
        $grouped = [];
        foreach ($proyectos as $p) {
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

        $totalActivos = count(array_filter($proyectos, fn($p) => $p['estado'] === 'activo'));

        $this->layout('proyectos.index', [
            'pageTitle'    => 'Proyectos',
            'currentRoute' => '/proyectos',
            'grouped'      => $grouped,
            'totalActivos' => $totalActivos,
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
        $model = new ProyectoModel();
        if (!$model->actualizarEstado($id, $uid, [
            'estado'     => 'completado',
            'deleted_at' => date('Y-m-d H:i:s'),
        ])) {
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
