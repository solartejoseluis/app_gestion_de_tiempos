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
}
