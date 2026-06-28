<?php
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$dias  = ['Sun'=>'dom','Mon'=>'lun','Tue'=>'mar','Wed'=>'mié',
          'Thu'=>'jue','Fri'=>'vie','Sat'=>'sáb'];

$fmtFecha = static function (?string $ts) use ($meses, $dias): string {
    if (!$ts) return '—';
    $d = new DateTime($ts);
    return $dias[$d->format('D')] . ' ' . $d->format('j') . ' ' . $meses[$d->format('M')]
         . ' · ' . $d->format('H:i');
};

$fmtFechaCorta = static function (?string $ts) use ($meses, $dias): string {
    if (!$ts) return '—';
    $d = new DateTime($ts);
    return $dias[$d->format('D')] . ' ' . $d->format('j') . ' ' . $meses[$d->format('M')]
         . ' ' . $d->format('Y');
};

$totalItems     = count($items);
$totalProyectos = count($proyectos);

$hayFiltros = !empty($filtros['area_id']) || !empty($filtros['proyecto_id'])
           || !empty($filtros['contexto_id']) || !empty($filtros['fecha_desde'])
           || !empty($filtros['fecha_hasta']);

$qBase = static function (array $extra = []): string {
    $p = [];
    foreach ($extra as $k => $v) {
        $p[] = urlencode($k) . '=' . urlencode((string) $v);
    }
    return $p ? '?' . implode('&', $p) : '';
};

$filtrosActuales = array_filter([
    'area_id'     => $filtros['area_id']     ?? '',
    'proyecto_id' => $filtros['proyecto_id'] ?? '',
    'contexto_id' => $filtros['contexto_id'] ?? '',
    'fecha_desde' => $filtros['fecha_desde'] ?? '',
    'fecha_hasta' => $filtros['fecha_hasta'] ?? '',
], static fn($v) => $v !== '' && $v !== '0');
?>

<div class="p-4" style="max-width:960px">

    <!-- Encabezado -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <h5 class="mb-0">Completadas</h5>
        <span class="text-muted small">
            <?= $totalItems ?> ítem<?= $totalItems !== 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <?= $totalProyectos ?> proyecto<?= $totalProyectos !== 1 ? 's' : '' ?>
        </span>
        <?php if ($hayFiltros): ?>
            <span class="badge bg-warning text-dark">Filtros activos</span>
        <?php endif; ?>
    </div>

    <!-- Tabs de vista -->
    <ul class="nav nav-pills mb-4 gap-1">
        <?php
        $tabs = ['todo' => 'Todo', 'acciones' => 'Acciones', 'proyectos' => 'Proyectos'];
        foreach ($tabs as $tabKey => $tabLabel):
            $tabParams = array_merge($filtrosActuales, ['vista' => $tabKey]);
            $tabQs     = '?' . http_build_query($tabParams);
        ?>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 <?= $vista === $tabKey ? 'active' : '' ?>"
                   href="/completadas<?= $tabQs ?>">
                    <?= $tabLabel ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Filtros -->
    <form method="GET" action="/completadas" class="mb-4">
        <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
        <div class="d-flex flex-wrap gap-2 align-items-end">

            <div>
                <label class="form-label small mb-1 text-muted">Área</label>
                <select name="area_id" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($selectores['areas'] as $a): ?>
                        <option value="<?= $a['id'] ?>"
                            <?= (string)($filtros['area_id'] ?? '') === (string)$a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($vista !== 'proyectos'): ?>
            <div>
                <label class="form-label small mb-1 text-muted">Proyecto</label>
                <select name="proyecto_id" class="form-select form-select-sm" style="max-width:180px">
                    <option value="">Todos los proyectos</option>
                    <?php foreach ($selectores['proyectos'] as $pr): ?>
                        <option value="<?= $pr['id'] ?>"
                            <?= (string)($filtros['proyecto_id'] ?? '') === (string)$pr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label small mb-1 text-muted">Contexto</label>
                <select name="contexto_id" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todos los contextos</option>
                    <?php foreach ($selectores['contextos'] as $ctx): ?>
                        <option value="<?= $ctx['id'] ?>"
                            <?= (string)($filtros['contexto_id'] ?? '') === (string)$ctx['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ctx['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="form-label small mb-1 text-muted">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
            </div>

            <div>
                <label class="form-label small mb-1 text-muted">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
            </div>

            <div class="d-flex gap-2 align-self-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="/completadas<?= $vista !== 'todo' ? '?vista=' . urlencode($vista) : '' ?>"
                   class="btn btn-sm btn-outline-secondary">
                    Limpiar
                </a>
            </div>

        </div>
    </form>

    <?php if ($totalItems === 0 && $totalProyectos === 0): ?>

        <!-- Estado vacío -->
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check2-all d-block mb-3" style="font-size:3rem;opacity:.4"></i>
            <p class="mb-2">Aún no hay elementos completados.</p>
            <a href="/inbox" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-inbox me-1"></i>Empieza a procesar tu inbox
            </a>
        </div>

    <?php else: ?>

        <!-- Sección: Acciones completadas -->
        <?php if ($vista !== 'proyectos' && !empty($items)): ?>
            <div class="mb-5">
                <h6 class="fw-semibold text-secondary mb-3">
                    Acciones completadas
                    <span class="badge bg-secondary bg-opacity-50 text-secondary ms-1">
                        <?= $totalItems ?>
                    </span>
                </h6>

                <div class="list-group list-group-flush">
                    <?php foreach ($items as $it):
                        $fechaTs = $it['fecha_completada'] ?? $it['updated_at'];
                    ?>
                        <div class="list-group-item list-group-item-action px-3 py-2
                                    bg-success bg-opacity-10 border-0 border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <span class="text-muted small text-nowrap mt-1"
                                      style="min-width:110px">
                                    <?= $fmtFecha($fechaTs) ?>
                                </span>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold mb-1">
                                        <?= htmlspecialchars($it['titulo']) ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if ($it['contexto_nombre']): ?>
                                            <span class="tag tag-ctx">
                                                @<?= htmlspecialchars($it['contexto_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($it['area_nombre']): ?>
                                            <span class="tag tag-area"
                                                  <?= $it['area_color']
                                                      ? 'style="background:' . htmlspecialchars($it['area_color']) . '20;color:' . htmlspecialchars($it['area_color']) . ';border:1px solid ' . htmlspecialchars($it['area_color']) . '40"'
                                                      : '' ?>>
                                                <?= htmlspecialchars($it['area_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($it['proyecto_nombre']): ?>
                                            <span class="tag" style="background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fb">
                                                <i class="bi bi-folder me-1"></i><?= htmlspecialchars($it['proyecto_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($it['persona_nombre']): ?>
                                            <span class="tag tag-persona">
                                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($it['persona_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 ms-auto flex-shrink-0 align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary btn-recuperar-item"
                                            data-id="<?= $it['id'] ?>"
                                            title="Recuperar acción">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar-item"
                                            data-id="<?= $it['id'] ?>"
                                            title="Eliminar permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalItems >= 200): ?>
                    <p class="text-muted small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Mostrando los 200 más recientes. Usa los filtros para ver más.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Sección: Proyectos completados -->
        <?php if ($vista !== 'acciones' && !empty($proyectos)): ?>
            <div>
                <h6 class="fw-semibold text-secondary mb-3">
                    Proyectos completados
                    <span class="badge bg-secondary bg-opacity-50 text-secondary ms-1">
                        <?= $totalProyectos ?>
                    </span>
                </h6>

                <div class="d-flex flex-column gap-2">
                    <?php foreach ($proyectos as $pr): ?>
                        <div class="card border-0 bg-success bg-opacity-10">
                            <div class="card-body py-3 px-3">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">
                                            <?= htmlspecialchars($pr['nombre']) ?>
                                        </div>
                                        <?php if ($pr['resultado_deseado']): ?>
                                            <p class="text-muted small mb-1">
                                                <?= htmlspecialchars(mb_substr($pr['resultado_deseado'], 0, 100))
                                                    . (mb_strlen($pr['resultado_deseado']) > 100 ? '…' : '') ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <?php if ($pr['area_nombre']): ?>
                                                <span class="tag tag-area"
                                                      <?= $pr['area_color']
                                                          ? 'style="background:' . htmlspecialchars($pr['area_color']) . '20;color:' . htmlspecialchars($pr['area_color']) . ';border:1px solid ' . htmlspecialchars($pr['area_color']) . '40"'
                                                          : '' ?>>
                                                    <?= htmlspecialchars($pr['area_nombre']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge bg-secondary bg-opacity-50 text-secondary">
                                                <?= (int) $pr['total_acciones'] ?> acción<?= (int)$pr['total_acciones'] !== 1 ? 'es' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                        <span class="text-muted small text-nowrap">
                                            <?= $fmtFechaCorta($pr['updated_at']) ?>
                                        </span>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-secondary btn-recuperar-proyecto"
                                                    data-id="<?= $pr['id'] ?>"
                                                    title="Reabrir proyecto">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-eliminar-proyecto"
                                                    data-id="<?= $pr['id'] ?>"
                                                    title="Eliminar proyecto permanentemente">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalProyectos >= 100): ?>
                    <p class="text-muted small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Mostrando los 100 más recientes. Usa los filtros para ver más.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Estado vacío con filtros aplicados -->
        <?php if ($totalItems === 0 && $totalProyectos === 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-filter-circle d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                <p class="mb-0">Ningún resultado con los filtros actuales.</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- Modal de confirmación ──────────────────────────── -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">¿Eliminar permanentemente?</h6>
                <button type="button" class="btn-close btn-sm"
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-muted small mb-0" id="modal-eliminar-texto">
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger"
                        id="btn-confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var modalEl   = document.getElementById('modalConfirmarEliminar');
    var btnConf   = document.getElementById('btn-confirmar-eliminar');
    var textoEl   = document.getElementById('modal-eliminar-texto');
    var pendingFn = null;

    function confirmarEliminar(texto, fn) {
        if (textoEl) textoEl.textContent = texto;
        pendingFn = fn;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    if (btnConf) {
        btnConf.addEventListener('click', function () {
            bootstrap.Modal.getInstance(modalEl)?.hide();
            if (pendingFn) { pendingFn(); pendingFn = null; }
        });
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                pendingFn = null;
            });
        }
    }

    function fetchPost(url, method) {
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    method === 'DELETE' ? '_method=DELETE' : '',
        }).then(function (r) { return r.json(); });
    }

    function fadeRemove(el) {
        el.style.transition = 'opacity .3s';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 320);
    }

    // ── Recuperar ítem ───────────────────────────────
    document.querySelectorAll('.btn-recuperar-item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            btn.disabled = true;
            fetchPost('/completadas/items/' + id + '/recuperar')
            .then(function (data) {
                if (data.ok) {
                    var row = btn.closest('.list-group-item');
                    if (row) { fadeRemove(row); } else { window.location.reload(); }
                } else {
                    alert(data.error || 'Error.');
                    btn.disabled = false;
                }
            })
            .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
        });
    });

    // ── Eliminar ítem ────────────────────────────────
    document.querySelectorAll('.btn-eliminar-item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            confirmarEliminar(
                'Se eliminará esta acción permanentemente. ¿Continuar?',
                function () {
                    btn.disabled = true;
                    fetchPost('/completadas/items/' + id, 'DELETE')
                    .then(function (data) {
                        if (data.ok) {
                            var row = btn.closest('.list-group-item');
                            if (row) { fadeRemove(row); } else { window.location.reload(); }
                        } else {
                            alert(data.error || 'Error.');
                            btn.disabled = false;
                        }
                    })
                    .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
                }
            );
        });
    });

    // ── Recuperar proyecto ───────────────────────────
    document.querySelectorAll('.btn-recuperar-proyecto').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            btn.disabled = true;
            fetchPost('/completadas/proyectos/' + id + '/recuperar')
            .then(function (data) {
                if (data.ok) {
                    var card = btn.closest('.card');
                    if (card) { fadeRemove(card); } else { window.location.reload(); }
                } else {
                    alert(data.error || 'Error.');
                    btn.disabled = false;
                }
            })
            .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
        });
    });

    // ── Eliminar proyecto ────────────────────────────
    document.querySelectorAll('.btn-eliminar-proyecto').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            confirmarEliminar(
                'Se eliminará el proyecto y todas sus tareas permanentemente. ¿Continuar?',
                function () {
                    btn.disabled = true;
                    fetchPost('/completadas/proyectos/' + id, 'DELETE')
                    .then(function (data) {
                        if (data.ok) {
                            var card = btn.closest('.card');
                            if (card) { fadeRemove(card); } else { window.location.reload(); }
                        } else {
                            alert(data.error || 'Error.');
                            btn.disabled = false;
                        }
                    })
                    .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
                }
            );
        });
    });

}());
</script>
