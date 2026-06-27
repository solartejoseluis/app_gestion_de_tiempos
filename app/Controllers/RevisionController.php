<?php
declare(strict_types=1);

class RevisionController extends Controller
{
    private static array $vistas = [
        1 => 'revision.paso1_inbox',
        2 => 'revision.paso2_proyectos',
        3 => 'revision.paso3_espera',
        4 => 'revision.paso4_someday',
        5 => 'revision.paso5_calendario',
        6 => 'revision.paso6_foco',
    ];

    public function index(): void
    {
        $this->requireAuth();
        $uid   = (int) $_SESSION['usuario_id'];
        $model = new RevisionModel();

        $this->layout('revision.index', [
            'pageTitle'         => 'Revisión Semanal',
            'currentRoute'      => '/revision',
            'estado'            => $model->getEstado($uid),
            'revisionActiva'    => $model->getActiva($uid),
            'historialReciente' => $model->getHistorial($uid, 3),
        ]);
    }

    public function iniciar(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];
        (new RevisionModel())->iniciar($uid);
        $this->redirect('/revision/paso/1');
    }

    public function verPaso(string $paso): void
    {
        $this->requireAuth();
        $uid  = (int) $_SESSION['usuario_id'];
        $paso = (int) $paso;

        if ($paso < 1 || $paso > 6) {
            $this->redirect('/revision');
        }

        $model  = new RevisionModel();
        $activa = $model->getActiva($uid);

        if (!$activa) {
            $this->redirect('/revision');
        }

        if ($paso > (int) $activa['paso_actual'] + 1) {
            $this->redirect('/revision/paso/' . $activa['paso_actual']);
        }

        $data = [
            'pageTitle'      => 'Revisión — Paso ' . $paso . ' de 6',
            'currentRoute'   => '/revision',
            'revisionActiva' => $activa,
            'paso'           => $paso,
        ];

        if ($paso === 1) {
            $data['itemsInbox'] = (new ItemModel())->getInbox($uid);
        } elseif ($paso === 2) {
            $todos = (new ProyectoModel())->getProyectosConStats($uid);
            $data['proyectos'] = array_values(
                array_filter($todos, fn($p) => $p['estado'] === 'activo')
            );
        } elseif ($paso === 3) {
            $data['itemsEspera'] = (new EsperaModel())->getEspera($uid);
        } elseif ($paso === 4) {
            $data['itemsSomeday'] = (new SomedayModel())->getSomeday($uid);
        } elseif ($paso === 5) {
            $db = Database::connection();

            $lunPasada  = date('Y-m-d', strtotime('monday last week'));
            $domPasada  = date('Y-m-d', strtotime('sunday last week'));
            $lunActual  = date('Y-m-d', strtotime('monday this week'));
            $domActual  = date('Y-m-d', strtotime('sunday this week'));
            $lunProxima = date('Y-m-d', strtotime('next monday'));
            $domProxima = date('Y-m-d', strtotime('next monday +6 days'));

            $sql = "SELECT titulo, tipo, fecha_accion
                      FROM items
                     WHERE usuario_id = ?
                       AND fecha_accion BETWEEN ? AND ?
                       AND tipo IN ('accion','proyecto_accion','delegada')
                       AND deleted_at IS NULL
                     ORDER BY fecha_accion ASC";

            $stmtP = $db->prepare($sql);
            $stmtP->execute([$uid, $lunPasada, $domPasada]);

            $stmtA = $db->prepare($sql);
            $stmtA->execute([$uid, $lunActual, $domActual]);

            $stmtX = $db->prepare($sql);
            $stmtX->execute([$uid, $lunProxima, $domProxima]);

            $data['semana'] = [
                'pasada'  => $stmtP->fetchAll(),
                'actual'  => $stmtA->fetchAll(),
                'proxima' => $stmtX->fetchAll(),
            ];
        }

        $this->layout(self::$vistas[$paso], $data);
    }

    public function completarPaso(string $paso): void
    {
        $this->requireAuth();
        $uid  = (int) $_SESSION['usuario_id'];
        $paso = (int) $paso;

        if ($paso < 1 || $paso > 6) {
            $this->error('Paso inválido.');
        }

        $model  = new RevisionModel();
        $activa = $model->getActiva($uid);

        if (!$activa) {
            $this->error('No hay una revisión activa.');
        }

        if ($paso === 1) {
            $inbox = (new ItemModel())->getInbox($uid);
            if (count($inbox) > 0) {
                $this->error('Aún hay ítems en el inbox');
            }
        }

        $contadores = [];
        foreach (['items_procesados', 'proyectos_revisados', 'delegaciones_cerradas', 'incubadas_activadas'] as $campo) {
            $val = $this->input($campo, null);
            if ($val !== null) {
                $contadores[$campo] = (int) $val;
            }
        }

        if ($paso < 6) {
            $siguiente = $paso + 1;
            $model->avanzarPaso((int) $activa['id'], $siguiente, $contadores);
            $this->json(['siguiente' => $siguiente]);
        } else {
            $model->avanzarPaso((int) $activa['id'], 6, $contadores);
            $this->redirect('/revision/paso/6');
        }
    }

    public function completarRevision(): void
    {
        $this->requireAuth();
        $uid    = (int) $_SESSION['usuario_id'];
        $model  = new RevisionModel();
        $activa = $model->getActiva($uid);

        if (!$activa) {
            $this->error('No hay una revisión activa.');
        }

        $foco = trim($this->input('foco_semana', ''));
        $model->completar((int) $activa['id'], $foco);
        $this->json(null);
    }

    public function cierre(): void
    {
        $this->requireAuth();
        $uid      = (int) $_SESSION['usuario_id'];
        $historial = (new RevisionModel())->getHistorial($uid, 1);

        $this->layout('revision.cierre', [
            'pageTitle'       => 'Revisión completada',
            'currentRoute'    => '/revision',
            'ultimaRevision'  => $historial[0] ?? null,
        ]);
    }

    public function historial(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $this->layout('revision.historial', [
            'pageTitle'    => 'Historial de revisiones',
            'currentRoute' => '/revision',
            'historial'    => (new RevisionModel())->getHistorial($uid),
        ]);
    }
}
