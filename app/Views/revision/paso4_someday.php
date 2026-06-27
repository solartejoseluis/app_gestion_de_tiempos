<?php
$paso  = 4;
$hoy   = date('Y-m-d');
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$total = count($itemsSomeday);
?>

<div class="p-4" style="max-width:860px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado del paso -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <small class="text-muted text-uppercase fw-semibold">Paso 4 de 6</small>
            <h5 class="mb-1 mt-1">Revisar lista Algún día / tal vez</h5>
            <p class="text-muted small mb-0">
                Decide si ya es el momento de activar algún ítem incubado, mantenerlo para más adelante o eliminarlo.
            </p>
        </div>
        <span id="contador-someday"
              class="badge <?= $total > 0 ? 'bg-warning text-dark' : 'bg-success' ?> fs-5 ms-3 flex-shrink-0">
            <?= $total ?>
        </span>
    </div>

    <?php if (empty($itemsSomeday)): ?>

        <div class="text-center py-5">
            <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.5rem"></i>
            <p class="fw-semibold mb-1">No hay ítems incubados. ¡Lista vacía!</p>
            <p class="text-muted small mb-0">Puedes continuar con la revisión.</p>
        </div>

    <?php else: ?>

        <div id="lista-someday-revision" data-total="<?= $total ?>">
            <?php foreach ($itemsSomeday as $item):
                $revisar  = $item['fecha_revision'] !== null && $item['fecha_revision'] <= $hoy;
                $fechaStr = null;
                if ($item['fecha_revision']) {
                    $dt       = new DateTime($item['fecha_revision']);
                    $fechaStr = $dt->format('j') . ' ' . $meses[$dt->format('M')];
                }
            ?>
                <div class="item someday-item <?= $revisar ? 'item-revisar' : '' ?>"
                     data-item-id="<?= $item['id'] ?>">

                    <div class="item-body">
                        <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if ($item['area_nombre']): ?>
                                <span class="tag"
                                      style="background:<?= htmlspecialchars($item['area_color'] ?? '#6c757d') ?>20;
                                             color:<?= htmlspecialchars($item['area_color'] ?? '#6c757d') ?>;
                                             border:1px solid <?= htmlspecialchars($item['area_color'] ?? '#6c757d') ?>40">
                                    <?= htmlspecialchars($item['area_nombre']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($fechaStr): ?>
                                <span class="tag <?= $revisar ? 'tag-revisar' : 'tag-date' ?>">
                                    Revisar: <?= $fechaStr ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="item-actions flex-wrap gap-1">
                        <button class="btn btn-sm btn-done btn-activar-someday"
                                data-id="<?= $item['id'] ?>">
                            <i class="bi bi-play me-1"></i>Activar
                        </button>
                        <button class="btn btn-sm btn-edit btn-mantener-someday"
                                data-id="<?= $item['id'] ?>">
                            Mantener
                        </button>
                        <button class="btn btn-sm btn-del btn-eliminar-someday"
                                data-id="<?= $item['id'] ?>"
                                data-titulo="<?= htmlspecialchars($item['titulo']) ?>">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Navegación -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <a href="/revision/paso/3" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button id="btn-continuar-paso5"
                class="btn btn-primary"
                <?= $total > 0 ? 'disabled' : '' ?>>
            Continuar al Paso 5 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

</div>

<!-- Modal confirmación eliminar someday -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">¿Eliminar ítem?</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-muted small mb-0" id="someday-eliminar-titulo"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger" id="btn-confirmar-eliminar-someday">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<div id="revision-paso-actual" data-paso="4" data-total="<?= $total ?>"></div>
<script src="/js/revision.js"></script>
