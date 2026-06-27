<?php
$meses      = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
               'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
               'Nov'=>'nov','Dec'=>'dic'];
$diasSemana = ['Sun'=>'domingo','Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles',
               'Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado'];
$fmt = static function (string $ts) use ($meses, $diasSemana): string {
    $d = new DateTime($ts);
    return $diasSemana[$d->format('D')] . ', '
         . $d->format('j') . ' '
         . $meses[$d->format('M')] . ' '
         . $d->format('Y');
};
?>

<div class="p-4" style="max-width:960px">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h5 class="mb-0">Historial de revisiones</h5>
        <a href="/revision" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <?php if (empty($historial)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-arrow-repeat fs-1 d-block mb-2 opacity-50"></i>
            <p class="mb-0">Aún no has completado ninguna revisión semanal.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-center">Duración</th>
                        <th class="text-center">Ítems</th>
                        <th class="text-center">Proyectos</th>
                        <th class="text-center">Delegaciones</th>
                        <th class="text-center">Activadas</th>
                        <th>Foco</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $r):
                    $min = (int) $r['duracion_minutos'];
                    $dur = $min < 60 ? $min . ' min' : floor($min / 60) . 'h ' . ($min % 60) . 'min';
                ?>
                    <tr>
                        <td class="small"><?= $fmt($r['fecha_inicio']) ?></td>
                        <td class="text-center text-muted small"><?= $dur ?></td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-50 text-secondary">
                                <?= (int) $r['items_procesados'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-50 text-secondary">
                                <?= (int) $r['proyectos_revisados'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-50 text-secondary">
                                <?= (int) $r['delegaciones_cerradas'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-50 text-secondary">
                                <?= (int) $r['incubadas_activadas'] ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?php if (!empty($r['foco_semana'])): ?>
                                <span title="<?= htmlspecialchars($r['foco_semana']) ?>">
                                    <?= htmlspecialchars(mb_substr($r['foco_semana'], 0, 60))
                                        . (mb_strlen($r['foco_semana']) > 60 ? '…' : '') ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
