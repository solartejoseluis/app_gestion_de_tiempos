<?php
$paso  = 3;
$hoy   = date('Y-m-d');
$meses = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
          'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
          'Nov'=>'nov','Dec'=>'dic'];
$total = count($itemsEspera);
?>

<div class="p-4" style="max-width:860px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado del paso -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <small class="text-muted text-uppercase fw-semibold">Paso 3 de 6</small>
            <h5 class="mb-1 mt-1">Revisar en espera de</h5>
            <p class="text-muted small mb-0">
                Decide qué hacer con cada delegación: fue recibida, hacer seguimiento, posponer o asumir la tarea tú mismo.
            </p>
        </div>
        <span id="contador-espera"
              class="badge <?= $total > 0 ? 'bg-warning text-dark' : 'bg-success' ?> fs-5 ms-3 flex-shrink-0">
            <?= $total ?>
        </span>
    </div>

    <?php if (empty($itemsEspera)): ?>

        <div class="text-center py-5">
            <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.5rem"></i>
            <p class="fw-semibold mb-1">No hay ítems en espera. ¡Todo está al día!</p>
            <p class="text-muted small mb-0">Puedes continuar con la revisión.</p>
        </div>

    <?php else: ?>

        <div id="lista-espera-revision" data-total="<?= $total ?>">
            <?php foreach ($itemsEspera as $item):
                $vencida  = $item['fecha_accion'] !== null && $item['fecha_accion'] < $hoy;
                $fechaStr = null;
                if ($item['fecha_accion']) {
                    $dt       = new DateTime($item['fecha_accion']);
                    $fechaStr = $dt->format('j') . ' ' . $meses[$dt->format('M')];
                }
            ?>
                <div class="item espera-item <?= $vencida ? 'item-vencida' : '' ?>"
                     data-item-id="<?= $item['id'] ?>">

                    <div class="item-body">
                        <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if ($item['persona_nombre']): ?>
                                <span class="tag tag-persona">
                                    <i class="bi bi-person"></i> <?= htmlspecialchars($item['persona_nombre']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($fechaStr): ?>
                                <span class="tag <?= $vencida ? 'tag-alert' : 'tag-date' ?>">
                                    <?= $fechaStr ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($vencida): ?>
                                <span class="tag tag-alert fw-bold">Vencido</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-success small fw-semibold d-none decision-indicator mt-1 d-block"></span>
                    </div>

                    <div class="item-actions espera-actions flex-wrap gap-1">
                        <button class="btn btn-sm btn-done btn-recibido-espera"
                                data-id="<?= $item['id'] ?>">
                            <i class="bi bi-check me-1"></i>Recibido
                        </button>
                        <button class="btn btn-sm btn-outline-secondary btn-seguimiento-espera"
                                data-id="<?= $item['id'] ?>">
                            <i class="bi bi-bell me-1"></i>Seguimiento
                        </button>
                        <button class="btn btn-sm btn-edit btn-posponer-espera"
                                data-id="<?= $item['id'] ?>"
                                data-fecha="<?= $item['fecha_accion'] ?? '' ?>">
                            Posponer
                        </button>
                        <button class="btn btn-sm btn-outline-primary btn-convertir-espera"
                                data-id="<?= $item['id'] ?>">
                            <i class="bi bi-arrow-return-left me-1"></i>Asumir
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Navegación -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <a href="/revision/paso/2" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button id="btn-continuar-paso4"
                class="btn btn-primary"
                <?= $total > 0 ? 'disabled' : '' ?>>
            Continuar al Paso 4 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

</div>

<div id="revision-paso-actual" data-paso="3" data-total="<?= $total ?>"></div>
<script src="/js/revision.js"></script>
