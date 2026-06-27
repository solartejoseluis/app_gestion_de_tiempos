<?php
$hoy    = date('Y-m-d');
$meses  = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
           'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
           'Nov'=>'nov','Dec'=>'dic'];
$total  = count($items);
?>

<div class="acciones-wrapper">

    <!-- Encabezado + filtros (sticky juntos) -->
    <div class="acciones-top">

        <div class="acciones-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="acciones-title mb-0">Próximas acciones</h6>
                <span id="acciones-counter" class="nav-badge badge-blue"><?= $total ?></span>
            </div>
        </div>

        <div class="acciones-filtros">
            <?php if (!empty($contextos)): ?>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <?php foreach ($contextos as $ctx): ?>
                        <span class="filtro-ctx-chip"
                              data-ctx-id="<?= $ctx['id'] ?>"
                              style="--chip-color:<?= htmlspecialchars($ctx['color']) ?>">
                            @<?= htmlspecialchars($ctx['nombre']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="filtro-area" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filtro-proyecto" class="form-select form-select-sm" style="max-width:180px">
                    <option value="">Todos los proyectos</option>
                    <?php foreach ($proyectos as $proy): ?>
                        <option value="<?= $proy['id'] ?>"
                            <?= $proy['id'] == $filtroProyectoId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proy['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button id="btn-limpiar-filtros" class="btn btn-sm btn-outline-secondary">
                    Limpiar filtros
                </button>
            </div>
        </div>

    </div><!-- /.acciones-top -->

    <!-- Lista -->
    <div id="acciones-lista" class="acciones-lista">

        <div id="acciones-empty" class="empty-state <?= $total > 0 ? 'd-none' : '' ?>">
            <i class="bi bi-check2-all"></i>
            <p>No hay próximas acciones.<br>Procesa ítems del inbox para agregarlas.</p>
        </div>

        <?php foreach ($items as $item):
            $vencida   = $item['fecha_accion'] !== null && $item['fecha_accion'] < $hoy;
            $fechaStr  = null;
            if ($item['fecha_accion']) {
                $dt       = new DateTime($item['fecha_accion']);
                $fechaStr = $dt->format('j') . ' ' . $meses[$dt->format('M')];
            }
        ?>
            <div class="item acciones-item <?= $vencida ? 'item-vencida' : '' ?>"
                 data-id="<?= $item['id'] ?>"
                 data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>"
                 data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>">

                <button class="btn-check-circular flex-shrink-0"
                        data-item-id="<?= $item['id'] ?>"
                        title="Marcar como hecho"></button>

                <div class="item-body">
                    <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php if ($item['contexto_nombre']): ?>
                            <span class="tag tag-ctx">@<?= htmlspecialchars($item['contexto_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['area_nombre']): ?>
                            <span class="tag tag-area"><?= htmlspecialchars($item['area_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['proyecto_nombre']): ?>
                            <span class="tag tag-proj"><?= htmlspecialchars($item['proyecto_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($fechaStr): ?>
                            <span class="tag <?= $vencida ? 'tag-alert' : 'tag-date' ?>">
                                <?= $fechaStr ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Notas inline -->
                    <div class="notas-wrapper mt-2">
                        <?php if (!empty($item['notas'])): ?>
                            <textarea class="notas-inline form-control form-control-sm"
                                      data-id="<?= $item['id'] ?>"
                                      rows="3"
                                      maxlength="5000"
                                      placeholder="Notas, pasos o contexto de esta acción..."
                                      style="font-size:.82rem;resize:vertical;background:#fffef5;border-color:#e8e4c8;"
                            ><?= htmlspecialchars($item['notas']) ?></textarea>
                            <div class="notas-guardado text-muted d-none" style="font-size:.72rem">
                                Guardado
                            </div>
                        <?php else: ?>
                            <button class="btn-toggle-notas btn btn-link btn-sm text-muted p-0"
                                    data-id="<?= $item['id'] ?>"
                                    style="font-size:.78rem;text-decoration:none">
                                <i class="bi bi-journal-text me-1"></i>Agregar notas
                            </button>
                            <div class="notas-expandida d-none">
                                <textarea class="notas-inline form-control form-control-sm"
                                          data-id="<?= $item['id'] ?>"
                                          rows="3"
                                          maxlength="5000"
                                          placeholder="Notas, pasos o contexto de esta acción..."
                                          style="font-size:.82rem;resize:vertical;background:#fffef5;border-color:#e8e4c8;"
                                ></textarea>
                                <div class="notas-guardado text-muted d-none" style="font-size:.72rem">
                                    Guardado
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="item-actions">
                    <button class="btn btn-sm btn-done" data-item-id="<?= $item['id'] ?>">
                        <i class="bi bi-check me-1"></i>Hecho
                    </button>
                    <button class="btn btn-sm btn-edit"
                            data-item-id="<?= $item['id'] ?>"
                            data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                            data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
                            data-fecha-accion="<?= $item['fecha_accion'] ?? '' ?>">
                        Editar
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

    </div><!-- /#acciones-lista -->

</div><!-- /.acciones-wrapper -->

<!-- Modal: Editar acción -->
<div class="modal fade" id="modalEditarAccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Editar acción</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit-titulo" class="form-label small fw-medium mb-1">
                        Título <span class="text-danger">*</span>
                    </label>
                    <input id="edit-titulo" type="text" class="form-control form-control-sm" maxlength="255">
                </div>
                <div class="mb-3">
                    <label for="edit-contexto" class="form-label small fw-medium mb-1">Contexto</label>
                    <select id="edit-contexto" class="form-select form-select-sm">
                        <option value="">Sin contexto</option>
                        <?php foreach ($contextos as $ctx): ?>
                            <option value="<?= $ctx['id'] ?>">
                                <?= htmlspecialchars($ctx['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="edit-fecha" class="form-label small fw-medium mb-1">Fecha</label>
                    <input id="edit-fecha" type="date" class="form-control form-control-sm">
                </div>
                <div id="edit-error" class="alert alert-danger d-none py-2 small mt-2" role="alert"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-guardar-editar" class="btn btn-sm btn-primary">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/acciones.js"></script>
