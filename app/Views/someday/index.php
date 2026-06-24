<?php
$hoy     = date('Y-m-d');
$meses   = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
            'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
            'Nov'=>'nov','Dec'=>'dic'];
$total    = count($items);
$revisar  = count(array_filter($items, fn($i) => $i['fecha_revision'] !== null && $i['fecha_revision'] <= $hoy));
?>

<div class="someday-wrapper">

    <!-- Encabezado + filtros (sticky) -->
    <div class="someday-top">

        <div class="someday-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="someday-title mb-0">Algún día / Quizás</h6>
                <span id="someday-counter" class="nav-badge badge-amber"><?= $total ?></span>
                <span id="someday-warn"
                      class="nav-badge badge-warn <?= $revisar === 0 ? 'd-none' : '' ?>">
                    <?= $revisar ?> para revisar
                </span>
            </div>
        </div>

        <div class="someday-filtros">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="filtro-area" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="btn-limpiar-filtros" class="btn btn-sm btn-outline-secondary">
                    Limpiar filtros
                </button>
            </div>
        </div>

    </div><!-- /.someday-top -->

    <!-- Lista -->
    <div id="someday-lista" class="someday-lista">

        <div id="someday-empty" class="empty-state <?= $total > 0 ? 'd-none' : '' ?>">
            <i class="bi bi-star"></i>
            <p>No hay ítems incubados.<br>Procesa ítems del inbox como 'Algún día' para guardarlos aquí.</p>
        </div>

        <?php foreach ($items as $item):
            $revisar  = $item['fecha_revision'] !== null && $item['fecha_revision'] <= $hoy;
            $fechaStr = null;
            if ($item['fecha_revision']) {
                $dt       = new DateTime($item['fecha_revision']);
                $fechaStr = $dt->format('j') . ' ' . $meses[$dt->format('M')];
            }
        ?>
            <div class="item someday-item <?= $revisar ? 'item-revisar' : '' ?>"
                 data-id="<?= $item['id'] ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>">

                <div class="item-body">
                    <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php if ($item['area_nombre']): ?>
                            <span class="tag tag-area"><?= htmlspecialchars($item['area_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($fechaStr): ?>
                            <span class="tag <?= $revisar ? 'tag-alert' : 'tag-date' ?>">
                                <?= $fechaStr ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($revisar): ?>
                            <span class="tag tag-revisar fw-bold">Revisar hoy</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="item-actions flex-wrap">
                    <button class="btn btn-sm btn-process btn-activar"
                            data-item-id="<?= $item['id'] ?>">
                        <i class="bi bi-arrow-up-circle me-1"></i>Activar
                    </button>
                    <button class="btn btn-sm btn-edit btn-posponer"
                            data-item-id="<?= $item['id'] ?>"
                            data-fecha="<?= $item['fecha_revision'] ?? '' ?>">
                        Posponer
                    </button>
                    <button class="btn btn-sm btn-del btn-eliminar"
                            data-item-id="<?= $item['id'] ?>">
                        Eliminar
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

    </div><!-- /#someday-lista -->

</div><!-- /.someday-wrapper -->

<!-- Modal: Posponer revisión -->
<div class="modal fade" id="modalSdPosponer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Posponer revisión</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="sd-posponer-fecha" class="form-label small mb-1">
                    Nueva fecha de revisión
                </label>
                <input id="sd-posponer-fecha" type="date" class="form-control form-control-sm">
                <div id="sd-posponer-error" class="alert alert-danger d-none py-2 small mt-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-sd-confirmar-posponer" class="btn btn-sm btn-primary">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirmar eliminación -->
<div class="modal fade" id="modalSdEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Eliminar ítem</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-0">
                    ¿Eliminar este ítem de algún día? Esta acción no se puede deshacer.
                </p>
                <div id="sd-eliminar-error" class="alert alert-danger d-none py-2 small mt-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-sd-confirmar-eliminar" class="btn btn-sm btn-danger">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/someday.js"></script>
