<?php
$hayProyectos = !empty($proyectos) || !empty($proyectosCompletados);
?>

<p class="text-muted small mb-4">
    Los proyectos se crean durante el procesamiento GTD.
    Aquí puedes revisarlos, editarlos y gestionarlos.
</p>

<?php if (!$hayProyectos): ?>

    <div class="text-center text-muted py-5">
        <i class="bi bi-folder fs-1 d-block mb-2 opacity-50"></i>
        <p class="mb-0">
            Aún no tienes proyectos.<br>
            Se crean al procesar ítems del inbox.
        </p>
    </div>

<?php else: ?>

    <?php foreach ($proyectos as $areaId => $grupo): ?>

        <div class="proyecto-area-header collapsed"
             data-bs-toggle="collapse"
             data-bs-target="#cfg-area-<?= $areaId ?>"
             role="button"
             aria-expanded="true">
            <span>
                <span class="proyecto-area-dot"
                      style="background:<?= htmlspecialchars($grupo['area_color']) ?>"></span>
                <?= htmlspecialchars($grupo['area_nombre']) ?>
                <span class="ms-2 fw-normal opacity-75">(<?= count($grupo['proyectos']) ?>)</span>
            </span>
            <i class="bi bi-chevron-down proyecto-area-chevron"></i>
        </div>

        <div id="cfg-area-<?= $areaId ?>" class="collapse show">

            <?php foreach ($grupo['proyectos'] as $p):
                $prox      = (int) $p['proximas_acciones'];
                $pausa     = $p['estado'] === 'pausa';
                $sinAccion = $prox === 0 && !$pausa;
                $resultado = (string) ($p['resultado_deseado'] ?? '');
                $resCorto  = mb_strlen($resultado) > 80
                             ? mb_substr($resultado, 0, 80) . '…'
                             : $resultado;
            ?>
                <div class="proyecto-card <?= $pausa ? 'proyecto-pausado' : '' ?>"
                     data-id="<?= $p['id'] ?>"
                     data-estado="<?= htmlspecialchars($p['estado']) ?>">

                    <!-- Nombre + badge estado -->
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="proyecto-nombre">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </span>
                        <span class="ms-2">
                            <?php if ($p['estado'] === 'activo'): ?>
                                <span class="badge bg-primary">Activo</span>
                            <?php elseif ($pausa): ?>
                                <span class="badge bg-warning text-dark">En pausa</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Resultado deseado -->
                    <?php if ($resCorto !== ''): ?>
                        <p class="proyecto-resultado mb-2"
                           title="<?= htmlspecialchars($resultado, ENT_QUOTES) ?>">
                            <?= htmlspecialchars($resCorto) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Sin próxima acción -->
                    <?php if ($sinAccion): ?>
                        <span class="badge bg-danger mb-2">Sin próxima acción</span>
                    <?php endif; ?>

                    <!-- Acciones -->
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <a href="/proyectos/<?= $p['id'] ?>/acciones"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-list-check me-1"></i>Ver acciones
                        </a>

                        <?php if ($p['estado'] === 'activo'): ?>
                            <button class="btn btn-sm btn-outline-warning cfg-btn-proyecto"
                                    data-id="<?= $p['id'] ?>"
                                    data-accion="pausar">
                                <i class="bi bi-pause me-1"></i>Pausar
                            </button>
                            <button class="btn btn-sm btn-outline-success cfg-btn-proyecto"
                                    data-id="<?= $p['id'] ?>"
                                    data-accion="completar">
                                <i class="bi bi-check-circle me-1"></i>Completar
                            </button>
                        <?php elseif ($pausa): ?>
                            <button class="btn btn-sm btn-outline-primary cfg-btn-proyecto"
                                    data-id="<?= $p['id'] ?>"
                                    data-accion="reactivar">
                                <i class="bi bi-play me-1"></i>Reactivar
                            </button>
                        <?php endif; ?>
                    </div>

                </div><!-- /.proyecto-card -->

            <?php endforeach; ?>

        </div><!-- /.collapse -->

    <?php endforeach; ?>

    <!-- Proyectos completados -->
    <?php if (!empty($proyectosCompletados)): ?>
        <div class="mt-4">
            <div class="proyecto-area-header collapsed"
                 data-bs-toggle="collapse"
                 data-bs-target="#cfg-completados-collapse"
                 role="button">
                <span>
                    <i class="bi bi-check-circle me-1"></i>
                    Proyectos completados
                    <span class="ms-2 fw-normal opacity-75">(<?= count($proyectosCompletados) ?>)</span>
                </span>
                <i class="bi bi-chevron-down proyecto-area-chevron"></i>
            </div>
            <div id="cfg-completados-collapse" class="collapse">
                <?php foreach ($proyectosCompletados as $p): ?>
                    <div class="proyecto-card proyecto-card-completada" data-id="<?= $p['id'] ?>">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="proyecto-nombre">
                                <?= htmlspecialchars($p['nombre']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary">Completado</span>
                                <button class="btn btn-sm btn-outline-secondary cfg-btn-proyecto"
                                        data-id="<?= $p['id'] ?>"
                                        data-accion="reabrir">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir
                                </button>
                            </div>
                        </div>
                        <?php if ($p['resultado_deseado']): ?>
                            <p class="proyecto-resultado mb-0">
                                <?= htmlspecialchars($p['resultado_deseado']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<script>
(function () {

    // ── Restaurar pestaña Proyectos cuando se llega con ?tab=proyectos ──
    // DOMContentLoaded dispara DESPUÉS de que Bootstrap JS se ha ejecutado.
    document.addEventListener('DOMContentLoaded', function () {
        var params = new URLSearchParams(window.location.search);
        var tab    = params.get('tab');
        if (tab) {
            var tabEl = document.querySelector('[data-bs-target="#pane-' + tab + '"]');
            if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }
    });

    // ── Botones de acción ─────────────────────────────────────────
    var pane = document.getElementById('pane-proyectos');
    if (!pane) return;

    pane.addEventListener('click', function (e) {
        var btn = e.target.closest('.cfg-btn-proyecto');
        if (!btn || btn.disabled) return;

        var id     = btn.dataset.id;
        var accion = btn.dataset.accion;

        // 'reabrir' reutiliza el endpoint de reactivar
        var endpoint = (accion === 'reabrir') ? 'reactivar' : accion;

        btn.disabled = true;

        fetch('/proyectos/' + endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'id=' + encodeURIComponent(id),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) {
                alert(data.error || 'Error al realizar la acción.');
                btn.disabled = false;
                return;
            }
            if (accion === 'pausar') {
                mutarPausar(id);
            } else if (accion === 'reactivar') {
                mutarReactivar(id);
            } else {
                // completar / reabrir: recarga preservando la pestaña
                window.location.href = '/config?tab=proyectos';
            }
        })
        .catch(function () {
            alert('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        });
    });

    // ── Mutaciones DOM: pausar ────────────────────────────────────
    function mutarPausar(id) {
        var card = pane.querySelector('.proyecto-card[data-id="' + id + '"]');
        if (!card) return;

        card.dataset.estado = 'pausa';
        card.classList.add('proyecto-pausado');

        var badge = card.querySelector('.ms-2 .badge');
        if (badge) {
            badge.className   = 'badge bg-warning text-dark';
            badge.textContent = 'En pausa';
        }

        var actDiv = card.querySelector('.d-flex.flex-wrap');
        var btnP   = actDiv ? actDiv.querySelector('[data-accion="pausar"]')    : null;
        var btnC   = actDiv ? actDiv.querySelector('[data-accion="completar"]') : null;
        if (btnP) btnP.remove();
        if (btnC) btnC.remove();

        if (actDiv) {
            var newBtn = document.createElement('button');
            newBtn.className      = 'btn btn-sm btn-outline-primary cfg-btn-proyecto';
            newBtn.dataset.id     = id;
            newBtn.dataset.accion = 'reactivar';
            newBtn.innerHTML      = '<i class="bi bi-play me-1"></i>Reactivar';
            actDiv.appendChild(newBtn);
        }
    }

    // ── Mutaciones DOM: reactivar (desde pausa) ───────────────────
    function mutarReactivar(id) {
        var card = pane.querySelector('.proyecto-card[data-id="' + id + '"]');
        if (!card) return;

        card.dataset.estado = 'activo';
        card.classList.remove('proyecto-pausado');

        var badge = card.querySelector('.ms-2 .badge');
        if (badge) {
            badge.className   = 'badge bg-primary';
            badge.textContent = 'Activo';
        }

        var actDiv = card.querySelector('.d-flex.flex-wrap');
        var btnR   = actDiv ? actDiv.querySelector('[data-accion="reactivar"]') : null;
        if (btnR) btnR.remove();

        if (actDiv) {
            var btnP = document.createElement('button');
            btnP.className      = 'btn btn-sm btn-outline-warning cfg-btn-proyecto';
            btnP.dataset.id     = id;
            btnP.dataset.accion = 'pausar';
            btnP.innerHTML      = '<i class="bi bi-pause me-1"></i>Pausar';
            actDiv.appendChild(btnP);

            var btnC = document.createElement('button');
            btnC.className      = 'btn btn-sm btn-outline-success cfg-btn-proyecto';
            btnC.dataset.id     = id;
            btnC.dataset.accion = 'completar';
            btnC.innerHTML      = '<i class="bi bi-check-circle me-1"></i>Completar';
            actDiv.appendChild(btnC);
        }
    }

}());
</script>
