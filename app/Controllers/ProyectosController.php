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
}
