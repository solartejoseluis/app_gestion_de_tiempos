<?php
$meses      = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
               'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
               'Nov'=>'nov','Dec'=>'dic'];
$diasSemana = ['Sun'=>'domingo','Mon'=>'lunes','Tue'=>'martes','Wed'=>'miércoles',
               'Thu'=>'jueves','Fri'=>'viernes','Sat'=>'sábado'];

$proximaFecha = new DateTime('+7 days');
$proximaStr   = $diasSemana[$proximaFecha->format('D')] . ', '
              . $proximaFecha->format('j') . ' '
              . $meses[$proximaFecha->format('M')] . ' '
              . $proximaFecha->format('Y');

$fechaFin = null;
$dur      = null;
if ($ultimaRevision) {
    $min      = (int) $ultimaRevision['duracion_minutos'];
    $dur      = $min < 60 ? $min . ' min' : floor($min / 60) . 'h ' . ($min % 60) . 'min';
    $dt       = new DateTime($ultimaRevision['fecha_fin']);
    $fechaFin = $diasSemana[$dt->format('D')] . ', '
              . $dt->format('j') . ' '
              . $meses[$dt->format('M')] . ' '
              . $dt->format('Y') . ' · ' . $dt->format('H:i');
}
?>

<div class="p-4 text-center" style="max-width:640px;margin:0 auto">

    <!-- Trofeo -->
    <div class="mb-4 mt-2">
        <i class="bi bi-trophy-fill text-warning d-block mb-3" style="font-size:4rem"></i>
        <h4 class="mb-1">¡Revisión semanal completada!</h4>
        <?php if ($fechaFin): ?>
            <p class="text-muted small mb-0"><?= htmlspecialchars($fechaFin) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($ultimaRevision): ?>

        <!-- 4 stats -->
        <div class="row g-2 mb-3">
            <div class="col-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body py-3 px-2 text-center">
                        <div class="fw-bold fs-4"><?= (int) $ultimaRevision['items_procesados'] ?></div>
                        <div class="text-muted" style="font-size:.7rem">ítems<br>procesados</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body py-3 px-2 text-center">
                        <div class="fw-bold fs-4"><?= (int) $ultimaRevision['proyectos_revisados'] ?></div>
                        <div class="text-muted" style="font-size:.7rem">proyectos<br>revisados</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body py-3 px-2 text-center">
                        <div class="fw-bold fs-4"><?= (int) $ultimaRevision['delegaciones_cerradas'] ?></div>
                        <div class="text-muted" style="font-size:.7rem">delegaciones<br>cerradas</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body py-3 px-2 text-center">
                        <div class="fw-bold fs-4"><?= (int) $ultimaRevision['incubadas_activadas'] ?></div>
                        <div class="text-muted" style="font-size:.7rem">ítems<br>activados</div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mb-4">Duración: <strong><?= $dur ?></strong></p>

        <?php if (!empty($ultimaRevision['foco_semana'])): ?>
            <div class="card border-0 mb-4 text-start" style="background:#e8f4fd">
                <div class="card-body py-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-bullseye text-primary flex-shrink-0 mt-1" style="font-size:1.2rem"></i>
                        <div>
                            <div class="small fw-semibold text-primary mb-1">Foco de la semana</div>
                            <p class="mb-0"><?= htmlspecialchars($ultimaRevision['foco_semana']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Próxima revisión -->
    <p class="text-muted small mb-4">
        <i class="bi bi-calendar-event me-1"></i>
        Próxima revisión recomendada: <strong><?= $proximaStr ?></strong>
    </p>

    <!-- Botones -->
    <div class="d-flex gap-2 justify-content-center">
        <a href="/dashboard" class="btn btn-primary">
            <i class="bi bi-house me-1"></i>Ir al dashboard
        </a>
        <a href="/revision/historial" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>Ver historial
        </a>
    </div>

</div>
