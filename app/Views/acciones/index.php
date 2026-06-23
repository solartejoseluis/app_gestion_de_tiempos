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
                        <option value="<?= $proy['id'] ?>"><?= htmlspecialchars($proy['nombre']) ?></option>
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
                </div>

                <div class="item-actions">
                    <button class="btn btn-sm btn-done" data-item-id="<?= $item['id'] ?>">
                        <i class="bi bi-check me-1"></i>Hecho
                    </button>
                    <button class="btn btn-sm btn-edit">Editar</button>
                </div>

            </div>
        <?php endforeach; ?>

    </div><!-- /#acciones-lista -->

</div><!-- /.acciones-wrapper -->

<script src="/js/acciones.js"></script>
