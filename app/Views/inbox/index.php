<?php
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$formatFecha = static function(string $ts) use ($meses): string {
    $d = new DateTime($ts);
    return $d->format('j') . ' ' . $meses[$d->format('M')] . ' ' . $d->format('Y');
};
$total = count($items);
?>

<div class="inbox-wrapper">

    <div class="inbox-header">
        <h6 class="inbox-title">
            Inbox &mdash; <span id="inbox-counter"><?= $total ?></span>
            <?= $total === 1 ? 'pendiente' : 'pendientes' ?>
        </h6>
    </div>

    <div class="inbox-list" id="inbox-list">

        <?php if ($total === 0): ?>
            <div class="empty-state" id="empty-state">
                <i class="bi bi-check-circle"></i>
                <p>Inbox vacío. Sistema al día.</p>
            </div>
        <?php else: ?>
            <div class="empty-state d-none" id="empty-state">
                <i class="bi bi-check-circle"></i>
                <p>Inbox vacío. Sistema al día.</p>
            </div>
            <?php foreach ($items as $item): ?>
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
        <?php endif; ?>

    </div>

    <div class="capture">
        <form id="capture-form" class="d-flex gap-2 w-100">
            <input type="text"
                   id="capture-input"
                   class="form-control form-control-sm"
                   placeholder="Capturar ítem..."
                   maxlength="255"
                   autocomplete="off">
            <button type="submit" class="btn btn-sm btn-capture">Guardar</button>
        </form>
    </div>

</div>

<!-- Modal confirmación borrar -->
<div class="modal fade" id="modalBorrar" tabindex="-1" aria-labelledby="modalBorrarLabel" aria-hidden="true">
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
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger" id="btn-confirmar-borrar">Borrar</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/inbox.js"></script>
