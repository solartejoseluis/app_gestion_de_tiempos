<?php
$hoy     = date('Y-m-d');
$meses   = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
            'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
            'Nov'=>'nov','Dec'=>'dic'];
$diasSem = ['Sun'=>'dom','Mon'=>'lun','Tue'=>'mar',
            'Wed'=>'mié','Thu'=>'jue','Fri'=>'vie','Sat'=>'sáb'];
$total    = count($items);
$vencidos = count(array_filter($items, fn($i) => $i['fecha_accion'] !== null && $i['fecha_accion'] < $hoy));
?>

<div class="espera-wrapper">

    <!-- Encabezado + filtros (sticky) -->
    <div class="espera-top">

        <div class="espera-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="espera-title mb-0">En espera de</h6>
                <span id="espera-counter" class="nav-badge badge-coral"><?= $total ?></span>
                <span id="espera-warn"
                      class="nav-badge badge-warn <?= $vencidos === 0 ? 'd-none' : '' ?>">
                    <?= $vencidos ?> vencido<?= $vencidos !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="espera-filtros">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="filtro-persona" class="form-select form-select-sm" style="max-width:180px">
                    <option value="">Todas las personas</option>
                    <?php foreach ($personas as $per): ?>
                        <option value="<?= $per['id'] ?>"><?= htmlspecialchars($per['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
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

    </div><!-- /.espera-top -->

    <!-- Lista -->
    <div id="espera-lista" class="espera-lista">

        <div id="espera-empty" class="empty-state <?= $total > 0 ? 'd-none' : '' ?>">
            <i class="bi bi-hourglass-split"></i>
            <p>No hay ítems en espera.<br>Delega acciones desde el inbox para hacer seguimiento.</p>
        </div>

        <?php foreach ($items as $item):
            $vencida  = $item['fecha_accion'] !== null && $item['fecha_accion'] < $hoy;
            $fechaStr = null;
            $horaStr  = null;
            $diasStr  = null;

            if ($item['fecha_accion']) {
                $dt       = new DateTime($item['fecha_accion']);
                $fechaStr = $diasSem[$dt->format('D')] . ' ' . $dt->format('j') . ' ' . $meses[$dt->format('M')];

                if (!empty($item['hora_inicio']) && $item['tipo_tiempo'] === 'cita') {
                    $horaStr = substr($item['hora_inicio'], 0, 5);
                }

                $hoyDt    = new DateTime($hoy);
                $diff     = (int) $hoyDt->diff($dt)->days;
                $esFuturo = $dt >= $hoyDt;
                if ($item['fecha_accion'] === $hoy) {
                    $diasStr = 'hoy';
                } elseif ($esFuturo && $diff === 1) {
                    $diasStr = 'mañana';
                } elseif ($esFuturo) {
                    $diasStr = 'en ' . $diff . ' días';
                } elseif ($diff === 1) {
                    $diasStr = 'ayer';
                } else {
                    $diasStr = $diff . ' días pasada';
                }
            }
        ?>
            <div class="item espera-item <?= $vencida ? 'item-vencida' : '' ?>"
                 data-id="<?= $item['id'] ?>"
                 data-persona-id="<?= $item['persona_id'] ?? '' ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>">

                <div class="item-body">
                    <div class="item-text mb-1"><?= htmlspecialchars($item['titulo']) ?></div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php if ($item['persona_nombre']): ?>
                            <span class="tag tag-persona">
                                <i class="bi bi-person"></i> <?= htmlspecialchars($item['persona_nombre']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($item['contexto_nombre']): ?>
                            <span class="tag tag-ctx">@<?= htmlspecialchars($item['contexto_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['area_nombre']): ?>
                            <span class="tag tag-area"><?= htmlspecialchars($item['area_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($fechaStr): ?>
                            <span class="tag <?= $vencida ? 'tag-alert' : 'tag-date' ?>">
                                <?= $fechaStr ?>
                                <?php if ($horaStr): ?>· <?= $horaStr ?><?php endif; ?>
                            </span>
                            <?php if ($diasStr): ?>
                                <span class="tag" style="font-size:.7rem;<?= $vencida
                                    ? 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;'
                                    : ($diasStr === 'hoy'
                                        ? 'background:#fef9c3;color:#854d0e;border:1px solid #fde047;'
                                        : 'background:#f0fdf4;color:#166534;border:1px solid #86efac;') ?>">
                                    <?= htmlspecialchars($diasStr) ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Notas inline -->
                    <div class="notas-wrapper mt-2">
                        <?php if (!empty($item['notas'])): ?>
                            <textarea class="notas-inline form-control form-control-sm"
                                      data-id="<?= $item['id'] ?>"
                                      rows="3"
                                      maxlength="5000"
                                      placeholder="Notas, pasos o contexto..."
                                      style="font-size:.82rem;resize:vertical;background:#fffef5;border-color:#e8e4c8;"
                            ><?= htmlspecialchars($item['notas']) ?></textarea>
                            <div class="notas-guardado text-muted d-none" style="font-size:.72rem">Guardado</div>
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
                                          placeholder="Notas, pasos o contexto..."
                                          style="font-size:.82rem;resize:vertical;background:#fffef5;border-color:#e8e4c8;"
                                ></textarea>
                                <div class="notas-guardado text-muted d-none" style="font-size:.72rem">Guardado</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="item-actions">
                    <button class="btn btn-sm btn-done btn-recibido"
                            data-item-id="<?= $item['id'] ?>">
                        <i class="bi bi-check me-1"></i>Recibido
                    </button>
                    <button class="btn btn-sm btn-edit btn-posponer"
                            data-item-id="<?= $item['id'] ?>"
                            data-fecha="<?= $item['fecha_accion'] ?? '' ?>">
                        Posponer
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

    </div><!-- /#espera-lista -->

</div><!-- /.espera-wrapper -->

<!-- Modal: Posponer -->
<div class="modal fade" id="modalPosponer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Posponer seguimiento</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="posponer-fecha" class="form-label small mb-1">
                    Nueva fecha de seguimiento
                </label>
                <input id="posponer-fecha" type="date" class="form-control form-control-sm">
                <div id="posponer-error" class="alert alert-danger d-none py-2 small mt-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-confirmar-posponer" class="btn btn-sm btn-primary">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/espera.js"></script>
