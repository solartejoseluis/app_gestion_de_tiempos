<?php
declare(strict_types=1);

class AgendaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        // Calcular lunes de la semana
        $semanaParam = $_GET['semana'] ?? null;
        if ($semanaParam) {
            $lunes = new DateTime($semanaParam);
            if ($lunes->format('N') !== '1') {
                $lunes->modify('last monday');
            }
        } else {
            $lunes = new DateTime();
            if ($lunes->format('N') !== '1') {
                $lunes->modify('last monday');
            }
        }
        $domingo  = (clone $lunes)->modify('+6 days');
        $lunFecha = $lunes->format('Y-m-d');
        $domFecha = $domingo->format('Y-m-d');

        $datos = (new AgendaModel())->getSemana($uid, $lunFecha, $domFecha);

        // Expandir bloques por índice de día (0=lun … 6=dom)
        $bloquesPorDia = array_fill(0, 7, []);
        foreach ($datos['bloques'] as $bloque) {
            foreach (explode(',', $bloque['dias_semana']) as $dia) {
                $idx = (int) $dia - 1;
                if (isset($bloquesPorDia[$idx])) {
                    $bloquesPorDia[$idx][] = $bloque;
                }
            }
        }

        // Organizar ítems por día
        $itemsPorDia = array_fill(0, 7, ['con_hora' => [], 'sin_hora' => []]);
        foreach ($datos['items'] as $item) {
            $dt  = new DateTime($item['fecha_accion']);
            $idx = (int) $dt->format('N') - 1;
            if ($item['hora_inicio'] || $item['tipo_tiempo'] === 'cita') {
                $itemsPorDia[$idx]['con_hora'][] = $item;
            } else {
                $itemsPorDia[$idx]['sin_hora'][] = $item;
            }
        }

        // Organizar completadas por día
        $completadasPorDia = array_fill(0, 7, []);
        foreach ($datos['completadas'] as $item) {
            if (!$item['fecha_accion']) continue;
            $dt  = new DateTime($item['fecha_accion']);
            $idx = (int) $dt->format('N') - 1;
            $completadasPorDia[$idx][] = $item;
        }

        $this->layout('agenda.index', [
            'pageTitle'         => 'Agenda',
            'currentRoute'      => '/agenda',
            'lunes'             => $lunes,
            'domingo'           => $domingo,
            'lunFecha'          => $lunFecha,
            'domFecha'          => $domFecha,
            'bloquesPorDia'     => $bloquesPorDia,
            'itemsPorDia'       => $itemsPorDia,
            'completadasPorDia' => $completadasPorDia,
        ]);
    }
}
