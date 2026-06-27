<?php
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$formatFechaCorta = static function(string $ts) use ($meses): string {
    $d = new DateTime($ts);
    return $d->format('j') . ' ' . $meses[$d->format('M')] . ' ' . $d->format('Y');
};

$dias   = (int) $estado['dias_desde_ultima'];
$inbox  = (int) $estado['items_inbox'];
$sinAcc = (int) $estado['proyectos_sin_accion'];
$delVen = (int) $estado['delegaciones_vencidas'];

// Colores de las cards de salud
$colorDias  = $dias <= 7  ? 'bg-success' : ($dias <= 14 ? 'bg-warning text-dark' : 'bg-danger');
$colorInbox = $inbox === 0 ? 'bg-success' : 'bg-warning text-dark';
$colorSin   = $sinAcc === 0 ? 'bg-success' : 'bg-danger';
$colorDel   = $delVen === 0 ? 'bg-success' : 'bg-warning text-dark';
?>

<div class="p-4" style="max-width:800px">

    <!-- Encabezado -->
    <div class="mb-5">
        <h4 class="mb-1">Revisión Semanal</h4>
        <p class="text-muted mb-0">El hábito que hace que todo lo demás funcione.</p>
    </div>

    <!-- Panel de salud del sistema -->
    <h6 class="text-uppercase text-muted fw-semibold small mb-3 letter-spacing-1">
        Estado del sistema
    </h6>
    <div class="row g-3 mb-5">

        <!-- Días desde última revisión -->
        <div class="col-6 col-md-3">
            <div class="card border-0 text-white <?= $colorDias ?> h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="fw-bold fs-4 mb-1">
                        <?= $dias === 999 ? '—' : $dias ?>
                    </div>
                    <div class="small opacity-90">
                        <?= $dias === 999 ? 'Primera revisión' : ($dias === 1 ? 'día' : 'días') ?>
                    </div>
                    <div class="small mt-1 opacity-75">desde última revisión</div>
                </div>
            </div>
        </div>

        <!-- Ítems en inbox -->
        <div class="col-6 col-md-3">
            <div class="card border-0 text-white <?= $colorInbox ?> h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="fw-bold fs-4 mb-1"><?= $inbox ?></div>
                    <div class="small opacity-90">
                        <?= $inbox === 0 ? 'Al día' : ($inbox === 1 ? 'pendiente' : 'pendientes') ?>
                    </div>
                    <div class="small mt-1 opacity-75">en inbox</div>
                </div>
            </div>
        </div>

        <!-- Proyectos sin próxima acción -->
        <div class="col-6 col-md-3">
            <div class="card border-0 text-white <?= $colorSin ?> h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="fw-bold fs-4 mb-1"><?= $sinAcc ?></div>
                    <div class="small opacity-90">
                        <?= $sinAcc === 0 ? 'Todo cubierto' : ($sinAcc === 1 ? 'proyecto' : 'proyectos') ?>
                    </div>
                    <div class="small mt-1 opacity-75">sin próxima acción</div>
                </div>
            </div>
        </div>

        <!-- Delegaciones vencidas -->
        <div class="col-6 col-md-3">
            <div class="card border-0 text-white <?= $colorDel ?> h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="fw-bold fs-4 mb-1"><?= $delVen ?></div>
                    <div class="small opacity-90">
                        <?= $delVen === 0 ? 'Al día' : ($delVen === 1 ? 'vencida' : 'vencidas') ?>
                    </div>
                    <div class="small mt-1 opacity-75">delegaciones</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Botón principal -->
    <div class="mb-5">
        <?php if ($revisionActiva): ?>
            <a href="/revision/paso/<?= (int) $revisionActiva['paso_actual'] ?>"
               class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle me-2"></i>Continuar revisión
            </a>
            <span class="ms-3 text-muted small">
                Paso <?= (int) $revisionActiva['paso_actual'] ?> de 6 en curso
            </span>
        <?php else: ?>
            <form method="POST" action="/revision/iniciar" class="d-inline">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-play-circle me-2"></i>Iniciar revisión semanal
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Historial reciente -->
    <?php if (!empty($historialReciente)): ?>
        <div>
            <h6 class="text-uppercase text-muted fw-semibold small mb-3">
                Revisiones recientes
            </h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th class="text-center">Duración</th>
                            <th>Foco de la semana</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historialReciente as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= $formatFechaCorta($r['fecha_inicio']) ?></td>
                            <td class="text-center text-muted small">
                                <?php
                                $min = (int) $r['duracion_minutos'];
                                echo $min < 60 ? $min . ' min' : floor($min / 60) . 'h ' . ($min % 60) . 'min';
                                ?>
                            </td>
                            <td class="small">
                                <?= $r['foco_semana']
                                    ? htmlspecialchars(mb_substr($r['foco_semana'], 0, 80))
                                    : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="/revision/historial" class="small text-decoration-none">
                Ver historial completo →
            </a>
        </div>
    <?php endif; ?>

</div>
