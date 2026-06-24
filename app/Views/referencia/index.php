<?php
$total = count($items);
?>

<div class="referencia-wrapper">

    <!-- Encabezado + filtros (sticky) -->
    <div class="referencia-top">

        <div class="referencia-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="referencia-title mb-0">Referencia</h6>
                <span id="ref-counter" class="nav-badge badge-blue"><?= $total ?></span>
            </div>
        </div>

        <div class="referencia-filtros">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="filtro-area" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filtro-proyecto" class="form-select form-select-sm" style="max-width:160px">
                    <option value="">Todos los proyectos</option>
                    <?php foreach ($proyectos as $proy): ?>
                        <option value="<?= $proy['id'] ?>"><?= htmlspecialchars($proy['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input id="filtro-texto" type="text" class="form-control form-control-sm"
                       placeholder="Buscar por título o etiqueta…" style="max-width:220px">
                <button id="btn-limpiar-filtros" class="btn btn-sm btn-outline-secondary">
                    Limpiar filtros
                </button>
            </div>
        </div>

    </div><!-- /.referencia-top -->

    <!-- Lista -->
    <div id="ref-lista" class="referencia-lista">

        <div id="ref-empty" class="empty-state <?= $total > 0 ? 'd-none' : '' ?>">
            <i class="bi bi-file-text"></i>
            <p>No hay material de referencia.<br>Procesa ítems del inbox como 'Referencia' para guardarlos aquí.</p>
        </div>

        <?php foreach ($items as $item):
            $etiquetasArr = $item['etiquetas'] ? array_filter(array_map('trim', explode(',', $item['etiquetas']))) : [];
        ?>
            <div class="item referencia-item"
                 data-id="<?= $item['id'] ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>"
                 data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>"
                 data-etiquetas="<?= htmlspecialchars(strtolower($item['etiquetas'] ?? '')) ?>">

                <div class="item-body">
                    <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                    <div class="d-flex flex-wrap gap-1 item-tags">
                        <?php if ($item['area_nombre']): ?>
                            <span class="tag tag-area"><?= htmlspecialchars($item['area_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['proyecto_nombre']): ?>
                            <span class="tag tag-proj"><?= htmlspecialchars($item['proyecto_nombre']) ?></span>
                        <?php endif; ?>
                        <?php foreach ($etiquetasArr as $et): ?>
                            <span class="tag tag-etiqueta"><?= htmlspecialchars($et) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="item-actions flex-wrap">
                    <button class="btn btn-sm btn-edit btn-etiquetas"
                            data-item-id="<?= $item['id'] ?>"
                            data-etiquetas="<?= htmlspecialchars($item['etiquetas'] ?? '') ?>">
                        <i class="bi bi-tags me-1"></i>Etiquetas
                    </button>
                    <button class="btn btn-sm btn-process btn-activar"
                            data-item-id="<?= $item['id'] ?>">
                        <i class="bi bi-arrow-up-circle me-1"></i>Activar
                    </button>
                    <button class="btn btn-sm btn-del btn-eliminar"
                            data-item-id="<?= $item['id'] ?>">
                        Eliminar
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

    </div><!-- /#ref-lista -->

</div><!-- /.referencia-wrapper -->

<!-- Modal: Editar etiquetas -->
<div class="modal fade" id="modalRefEtiquetas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Editar etiquetas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="ref-etiquetas-input" class="form-label small mb-1">
                    Etiquetas (separadas por coma)
                </label>
                <input id="ref-etiquetas-input" type="text"
                       class="form-control form-control-sm"
                       placeholder="ej: trabajo, finanzas, salud">
                <div class="form-text small text-muted">Separa con comas. Deja vacío para quitar todas.</div>
                <div id="ref-etiquetas-error" class="alert alert-danger d-none py-2 small mt-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-ref-guardar-etiquetas" class="btn btn-sm btn-primary">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirmar eliminación -->
<div class="modal fade" id="modalRefEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Eliminar referencia</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-0">
                    ¿Eliminar este material de referencia? Esta acción no se puede deshacer.
                </p>
                <div id="ref-eliminar-error" class="alert alert-danger d-none py-2 small mt-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-ref-confirmar-eliminar" class="btn btn-sm btn-danger">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/referencia.js"></script>
