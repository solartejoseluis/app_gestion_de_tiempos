<?php
$hoy   = date('Y-m-d');
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$dias  = ['Sun'=>'dom','Mon'=>'lun','Tue'=>'mar','Wed'=>'mié',
          'Thu'=>'jue','Fri'=>'vie','Sat'=>'sáb'];

$fmtFecha = static function (?string $ts) use ($meses, $dias): string {
    if (!$ts) return '';
    $d = new DateTime($ts);
    return $dias[$d->format('D')] . ' ' . $d->format('j') . ' ' . $meses[$d->format('M')];
};

$totalActivas = count($acciones);
?>

<div class="p-4" style="max-width:760px">

    <!-- Breadcrumb -->
    <a href="/proyectos" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left"></i> Proyectos
    </a>

    <!-- Encabezado del proyecto -->
    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($proyecto['nombre']) ?></h4>
            <?php if ($proyecto['resultado_deseado']): ?>
                <p class="text-muted small mb-2">
                    <?= htmlspecialchars($proyecto['resultado_deseado']) ?>
                </p>
            <?php endif; ?>
            <?php if ($proyecto['estado'] === 'pausa'): ?>
                <span class="badge bg-warning text-dark">En pausa</span>
            <?php else: ?>
                <span class="badge bg-primary">Activo</span>
            <?php endif; ?>
        </div>
        <button class="btn btn-sm btn-outline-primary flex-shrink-0"
                data-bs-toggle="modal"
                data-bs-target="#modalProcesar"
                data-modo="agregar-accion"
                data-proyecto-id="<?= $proyecto['id'] ?>"
                data-proyecto-nombre="<?= htmlspecialchars($proyecto['nombre']) ?>"
                data-area-id="<?= $proyecto['area_id'] ?? '' ?>">
            <i class="bi bi-plus me-1"></i>Agregar acción
        </button>
    </div>

    <!-- ── Próximas acciones ── -->
    <div class="mb-5">
        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
            Próximas acciones
            <span id="acciones-count" class="badge bg-primary bg-opacity-75">
                <?= $totalActivas ?>
            </span>
        </h6>

        <?php if (empty($acciones)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" id="alerta-sin-accion">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                Sin próxima acción definida. Agrega una para mantener el proyecto avanzando.
            </div>
        <?php else: ?>
            <div id="acciones-lista">
                <?php foreach ($acciones as $item):
                    $vencida  = $item['fecha_accion'] !== null && $item['fecha_accion'] < $hoy;
                    $fechaStr = $fmtFecha($item['fecha_accion']);
                ?>
                    <div class="item acciones-item mb-2 <?= $vencida ? 'item-vencida' : '' ?>"
                         data-id="<?= $item['id'] ?>">

                        <button class="btn-check-circular flex-shrink-0"
                                data-item-id="<?= $item['id'] ?>"
                                title="Marcar como hecho"></button>

                        <div class="item-body">
                            <div class="item-text mb-1">
                                <?= htmlspecialchars($item['titulo']) ?>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if ($item['contexto_nombre']): ?>
                                    <span class="tag tag-ctx"
                                          <?= $item['contexto_color']
                                              ? 'style="--chip-color:' . htmlspecialchars($item['contexto_color']) . '"'
                                              : '' ?>>
                                        @<?= htmlspecialchars($item['contexto_nombre']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($fechaStr): ?>
                                    <span class="tag <?= $vencida ? 'tag-alert' : 'tag-date' ?>">
                                        <?= $fechaStr ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="item-actions">
                            <button class="btn btn-sm btn-done btn-completar-accion"
                                    data-item-id="<?= $item['id'] ?>">
                                <i class="bi bi-check me-1"></i>Hecho
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 d-none"
                 id="alerta-sin-accion">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                Sin próxima acción definida. Agrega una para mantener el proyecto avanzando.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Acciones completadas (colapsable) ── -->
    <div>
        <button class="btn btn-link text-muted text-decoration-none px-0 d-flex align-items-center gap-2"
                data-bs-toggle="collapse"
                data-bs-target="#completadas-collapse"
                aria-expanded="false">
            <i class="bi bi-chevron-down" style="font-size:.8rem"></i>
            <span class="small fw-semibold">
                Acciones completadas (<?= count($completadas) ?>)
            </span>
        </button>

        <div id="completadas-collapse" class="collapse mt-2">
            <?php if (empty($completadas)): ?>
                <p class="text-muted small ps-3">
                    Aún no hay acciones completadas en este proyecto.
                </p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($completadas as $c):
                        $fechaComp = $fmtFecha($c['fecha_completada'] ?? $c['updated_at']);
                    ?>
                        <div class="list-group-item px-2 py-2 border-0 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"
                                   style="font-size:.85rem"></i>
                                <div class="flex-grow-1">
                                    <span class="small text-muted"
                                          style="text-decoration:line-through">
                                        <?= htmlspecialchars($c['titulo']) ?>
                                    </span>
                                </div>
                                <?php if ($fechaComp): ?>
                                    <span class="text-muted" style="font-size:.75rem;white-space:nowrap">
                                        <?= $fechaComp ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    var lista     = document.getElementById('acciones-lista');
    var countEl   = document.getElementById('acciones-count');
    var alertaEl  = document.getElementById('alerta-sin-accion');

    if (!lista) return;

    function completarAccion(id, btnEl) {
        btnEl.disabled = true;

        fetch('/acciones/completar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'id=' + encodeURIComponent(id),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) {
                btnEl.disabled = false;
                return;
            }

            var fila = lista.querySelector('[data-id="' + id + '"]');
            if (fila) {
                fila.style.transition = 'opacity .25s';
                fila.style.opacity    = '0';
                setTimeout(function () {
                    fila.remove();
                    var restantes = lista.querySelectorAll('.acciones-item').length;
                    if (countEl) countEl.textContent = restantes;
                    if (restantes === 0 && alertaEl) {
                        alertaEl.classList.remove('d-none');
                    }
                }, 260);
            }

            // Actualizar badge sidebar
            var badge = document.getElementById('sidebar-acciones-badge');
            if (badge) {
                var n = parseInt(badge.textContent, 10) - 1;
                badge.textContent = Math.max(0, n);
                badge.classList.toggle('d-none', n <= 0);
            }
        })
        .catch(function () {
            btnEl.disabled = false;
        });
    }

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-done, .btn-check-circular, .btn-completar-accion');
        if (btn) {
            var id = btn.dataset.itemId;
            if (id) completarAccion(id, btn);
        }
    });

}());

// procesamiento.js llama window.recargarLista() tras éxito del modal.
// El timeout da margen a que procesamiento.js complete su ciclo
// (hide() + resetModal()) antes de que la navegación destruya la página,
// evitando que el botón quede en "Guardando..." si el modal se reabre
// antes de que el reload dispare.
window.recargarLista = function () {
    setTimeout(function () {
        window.location.reload();
    }, 300);
};
</script>
