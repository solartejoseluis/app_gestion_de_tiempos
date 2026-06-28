<?php
declare(strict_types=1);

class CompletadasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $areaId     = (int) ($_GET['area_id']     ?? 0);
        $proyectoId = (int) ($_GET['proyecto_id'] ?? 0);
        $contextoId = (int) ($_GET['contexto_id'] ?? 0);
        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        $vista      = $_GET['vista'] ?? 'todo';

        if (!in_array($vista, ['acciones', 'proyectos', 'todo'], true)) {
            $vista = 'todo';
        }

        $filtros = [];
        if ($areaId > 0)         $filtros['area_id']      = $areaId;
        if ($proyectoId > 0)     $filtros['proyecto_id']  = $proyectoId;
        if ($contextoId > 0)     $filtros['contexto_id']  = $contextoId;
        if ($fechaDesde !== '')  $filtros['fecha_desde']  = $fechaDesde;
        if ($fechaHasta !== '')  $filtros['fecha_hasta']  = $fechaHasta;

        $model     = new CompletadasModel();
        $items     = [];
        $proyectos = [];

        if ($vista !== 'proyectos') {
            $items = $model->getItems($uid, $filtros);
        }
        if ($vista !== 'acciones') {
            $proyectos = $model->getProyectos($uid, $filtros);
        }

        $selectores = $model->getSelectores($uid);

        $this->layout('completadas.index', [
            'pageTitle'    => 'Completadas',
            'currentRoute' => '/completadas',
            'items'        => $items,
            'proyectos'    => $proyectos,
            'selectores'   => $selectores,
            'filtros'      => $_GET,
            'vista'        => $vista,
        ]);
    }

    public function recuperarItem(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

        $stmt = $db->prepare(
            'SELECT id, proyecto_id FROM items
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        $item = $stmt->fetch();
        if (!$item) {
            $this->error('No autorizado.', 403);
        }

        $tipo = $item['proyecto_id'] ? 'proyecto_accion' : 'accion';

        $db->prepare(
            'UPDATE items SET tipo = ?, fecha_completada = NULL WHERE id = ?'
        )->execute([$tipo, $id]);

        $this->json(null);
    }

    public function eliminarItem(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

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

    public function recuperarProyecto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

        $stmt = $db->prepare(
            'SELECT id FROM proyectos
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) {
            $this->error('No autorizado.', 403);
        }

        $db->prepare(
            "UPDATE proyectos SET estado = 'activo' WHERE id = ?"
        )->execute([$id]);

        $this->json(null);
    }

    public function eliminarProyecto(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];
        $db  = Database::connection();

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
}
