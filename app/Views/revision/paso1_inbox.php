<?php
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$formatFecha = static function(string $ts) use ($meses): string {
    $d = new DateTime($ts);
    return $d->format('j') . ' ' . $meses[$d->format('M')] . ' ' . $d->format('Y');
};
$total = count($itemsInbox);
?>

<div class="p-4" style="max-width:860px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado del paso -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <small class="text-muted text-uppercase fw-semibold">Paso 1 de 6</small>
            <h5 class="mb-1 mt-1">Inbox a cero</h5>
            <p class="text-muted small mb-0">
                Procesa o elimina todos los ítems antes de continuar.
                No es posible avanzar con ítems pendientes.
            </p>
        </div>
        <span id="revision-inbox-counter"
              class="badge <?= $total > 0 ? 'bg-warning text-dark' : 'bg-success' ?> fs-5 ms-3 flex-shrink-0">
            <?= $total ?>
        </span>
    </div>

    <!-- Lista del inbox -->
    <div id="revision-inbox-lista" class="<?= $total === 0 ? 'd-none' : '' ?>">
        <div class="inbox-list" id="inbox-list">
            <?php foreach ($itemsInbox as $item): ?>
                <div class="item" data-id="<?= $item['id'] ?>">
                    <div class="item-body">
                        <div class="item-text"><?= htmlspecialchars($item['titulo']) ?></div>
                        <div class="item-date"><?= $formatFecha($item['created_at']) ?></div>
                    </div>
                    <div class="item-actions">
                        <button class="btn btn-sm btn-process"
                                data-bs-toggle="modal"
                                data-bs-target="#modalProcesar"
                                data-item-id="<?= $item['id'] ?>"
                                data-item-texto="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>">Procesar</button>
                        <button class="btn btn-sm btn-del"
                                data-item-id="<?= $item['id'] ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#modalBorrar">Borrar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Estado vacío / éxito -->
    <div id="inbox-vacio-revision"
         class="text-center py-5 <?= $total > 0 ? 'd-none' : '' ?>">
        <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.5rem"></i>
        <p class="fw-semibold mb-1">¡Inbox vacío! El sistema está al día.</p>
        <p class="text-muted small mb-0">Puedes continuar con la revisión.</p>
    </div>

    <!-- Botón continuar -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
        <button id="btn-continuar-paso2"
                class="btn btn-primary"
                <?= $total > 0 ? 'disabled' : '' ?>>
            Continuar al Paso 2 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

</div>

<!-- Modal confirmación borrar -->
<div class="modal fade" id="modalBorrar" tabindex="-1"
     aria-labelledby="modalBorrarLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title" id="modalBorrarLabel">¿Borrar ítem?</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button"
                        class="btn btn-sm btn-danger"
                        id="btn-confirmar-borrar">Borrar</button>
            </div>
        </div>
    </div>
</div>

<div id="revision-paso-actual" data-paso="1" data-total-inicial="<?= $total ?>"></div>

<script src="/js/revision.js"></script>
