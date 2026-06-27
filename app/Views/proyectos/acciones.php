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
            <div class="d-flex align-items-center gap-3 mb-2 text-muted small">
                <?php if ($proyecto['area_nombre']): ?>
                    <span class="badge fw-normal"
                          style="background:<?= htmlspecialchars($proyecto['area_color']) ?>20;
                                 color:<?= htmlspecialchars($proyecto['area_color']) ?>;
                                 border:1px solid <?= htmlspecialchars($proyecto['area_color']) ?>40">
                        <?= htmlspecialchars($proyecto['area_nombre']) ?>
                    </span>
                <?php endif; ?>
                <span>
                    <i class="bi bi-calendar3 me-1"></i>
                    Creado el <?php
                        $dtCreado = new DateTime($proyecto['created_at']);
                        echo $dtCreado->format('j') . ' ' .
                             $meses[$dtCreado->format('M')] . ' ' .
                             $dtCreado->format('Y');
                    ?>
                </span>
            </div>
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
                            <button class="btn btn-sm btn-outline-danger btn-eliminar-accion"
                                    data-id="<?= $item['id'] ?>"
                                    data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                                    title="Eliminar acción">
                                <i class="bi bi-trash"></i>
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
                        <div class="list-group-item px-2 py-2 border-0 border-bottom"
                             data-completada-id="<?= $c['id'] ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success flex-shrink-0"
                                   style="font-size:.85rem"></i>
                                <div class="flex-grow-1">
                                    <span class="small text-muted"
                                          style="text-decoration:line-through">
                                        <?= htmlspecialchars($c['titulo']) ?>
                                    </span>
                                </div>
                                <?php if ($fechaComp): ?>
                                    <span class="text-muted flex-shrink-0"
                                          style="font-size:.75rem;white-space:nowrap">
                                        <?= $fechaComp ?>
                                    </span>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary btn-reactivar-accion flex-shrink-0"
                                        data-id="<?= $c['id'] ?>"
                                        title="Mover de vuelta a próximas acciones">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar-accion flex-shrink-0"
                                        data-id="<?= $c['id'] ?>"
                                        data-titulo="<?= htmlspecialchars($c['titulo'], ENT_QUOTES) ?>"
                                        title="Eliminar acción">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="modal fade" id="modalConfirmarEliminarAccion" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">¿Eliminar acción?</h6>
                <button type="button" class="btn-close btn-sm"
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-muted small mb-0" id="eliminar-accion-titulo"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger"
                        id="btn-confirmar-eliminar-accion">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var lista         = document.getElementById('acciones-lista');
    var countEl       = document.getElementById('acciones-count');
    var alertaEl      = document.getElementById('alerta-sin-accion');
    var completadasEl = document.getElementById('completadas-collapse');

    // ── Helpers ───────────────────────────────────────────────
    var MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    var DIAS  = ['dom','lun','mar','mié','jue','vie','sáb'];

    function fmtHoy() {
        var d = new Date();
        return DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()];
    }

    function escHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Insertar fila en sección completadas ──────────────────
    function insertarCompletada(id, titulo) {
        if (!completadasEl) return;

        var vacio = completadasEl.querySelector('p.text-muted');
        if (vacio) vacio.remove();

        var listGroup = completadasEl.querySelector('.list-group');
        if (!listGroup) {
            listGroup = document.createElement('div');
            listGroup.className = 'list-group list-group-flush';
            completadasEl.appendChild(listGroup);
        }

        var fila = document.createElement('div');
        fila.className = 'list-group-item px-2 py-2 border-0 border-bottom';
        fila.dataset.completadaId = id;
        fila.innerHTML =
            '<div class="d-flex align-items-center gap-2">' +
                '<i class="bi bi-check-circle-fill text-success flex-shrink-0"' +
                   ' style="font-size:.85rem"></i>' +
                '<div class="flex-grow-1">' +
                    '<span class="small text-muted" style="text-decoration:line-through">' +
                        escHTML(titulo) +
                    '</span>' +
                '</div>' +
                '<span class="text-muted flex-shrink-0"' +
                      ' style="font-size:.75rem;white-space:nowrap">' +
                    fmtHoy() +
                '</span>' +
                '<button class="btn btn-sm btn-outline-secondary btn-reactivar-accion flex-shrink-0"' +
                        ' data-id="' + escHTML(id) + '"' +
                        ' title="Mover de vuelta a próximas acciones">' +
                    '<i class="bi bi-arrow-counterclockwise"></i>' +
                '</button>' +
                '<button class="btn btn-sm btn-outline-danger btn-eliminar-accion flex-shrink-0"' +
                        ' data-id="' + escHTML(id) + '"' +
                        ' data-titulo="' + escHTML(titulo) + '"' +
                        ' title="Eliminar acción">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</div>';
        listGroup.insertAdjacentElement('afterbegin', fila);

        var btnCollapse = document.querySelector('[data-bs-target="#completadas-collapse"]');
        if (btnCollapse) {
            var spanCount = btnCollapse.querySelector('span.fw-semibold');
            if (spanCount) {
                var m = spanCount.textContent.match(/\d+/);
                var n = m ? parseInt(m[0], 10) : 0;
                spanCount.textContent = 'Acciones completadas (' + (n + 1) + ')';
            }
        }
    }

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

            var fila   = lista ? lista.querySelector('.acciones-item[data-id="' + id + '"]') : null;
            var titulo = '';
            if (fila) {
                var textoEl = fila.querySelector('.item-text');
                titulo = textoEl ? textoEl.textContent.trim() : '';
                fila.style.transition = 'opacity .25s';
                fila.style.opacity    = '0';
                setTimeout(function () {
                    fila.remove();
                    insertarCompletada(id, titulo);
                    var restantes = lista ? lista.querySelectorAll('.acciones-item').length : 0;
                    if (countEl) countEl.textContent = restantes;
                    if (restantes === 0 && alertaEl) {
                        alertaEl.classList.remove('d-none');
                    }
                }, 260);
            } else {
                insertarCompletada(id, titulo);
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

    if (lista) {
        lista.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-done, .btn-check-circular, .btn-completar-accion');
            if (btn) {
                var id = btn.dataset.itemId;
                if (id) completarAccion(id, btn);
            }
        });
    }

    // ── Reactivar acción completada ───────────────────────────
    if (completadasEl) {
        completadasEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-reactivar-accion');
            if (!btn || btn.disabled) return;

            var id = btn.dataset.id;
            btn.disabled = true;

            fetch('/acciones/' + encodeURIComponent(id) + '/reactivar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    '',
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    btn.disabled = false;
                    alert(data.error || 'No se pudo reactivar la acción.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                btn.disabled = false;
                alert('Error de conexión. Inténtalo de nuevo.');
            });
        });
    }

    // ── Eliminar acción ──────────────────────────────────────
    var modalEliminarEl  = document.getElementById('modalConfirmarEliminarAccion');
    var btnConfirmarElim = document.getElementById('btn-confirmar-eliminar-accion');
    var tituloElimEl     = document.getElementById('eliminar-accion-titulo');
    var deleteAccionId   = null;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-eliminar-accion');
        if (!btn) return;
        deleteAccionId = btn.dataset.id;
        if (tituloElimEl) {
            tituloElimEl.textContent = '"' + (btn.dataset.titulo || 'esta acción') + '"';
        }
        bootstrap.Modal.getOrCreateInstance(modalEliminarEl).show();
    });

    if (btnConfirmarElim) {
        btnConfirmarElim.addEventListener('click', function () {
            if (!deleteAccionId) return;
            btnConfirmarElim.disabled = true;
            var id = deleteAccionId;
            fetch('/acciones/' + encodeURIComponent(id), {
                method: 'DELETE',
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                bootstrap.Modal.getInstance(modalEliminarEl).hide();
                if (data.ok) {
                    var itemActivo = lista
                        ? lista.querySelector('.acciones-item[data-id="' + id + '"]')
                        : null;
                    var itemCompletado = document.querySelector(
                        '.list-group-item[data-completada-id="' + id + '"]'
                    );
                    var item     = itemActivo || itemCompletado;
                    var esActivo = !!itemActivo;

                    if (item) {
                        item.style.transition = 'opacity .3s';
                        item.style.opacity    = '0';
                        setTimeout(function () {
                            item.remove();
                            if (esActivo) {
                                var restantes = lista
                                    ? lista.querySelectorAll('.acciones-item').length
                                    : 0;
                                if (countEl) countEl.textContent = restantes;
                                if (restantes === 0 && alertaEl) {
                                    alertaEl.classList.remove('d-none');
                                }
                            }
                        }, 300);
                    }
                } else {
                    alert(data.error || 'Error al eliminar.');
                }
                btnConfirmarElim.disabled = false;
                deleteAccionId = null;
            })
            .catch(function () {
                alert('Error de conexión.');
                btnConfirmarElim.disabled = false;
                deleteAccionId = null;
            });
        });

        modalEliminarEl.addEventListener('hidden.bs.modal', function () {
            deleteAccionId = null;
        });
    }

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
