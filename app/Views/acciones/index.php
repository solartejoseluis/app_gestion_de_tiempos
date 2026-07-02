<?php
$hoy    = date('Y-m-d');
$meses  = ['Jan'=>'ene','Feb'=>'feb','Mar'=>'mar','Apr'=>'abr','May'=>'may',
           'Jun'=>'jun','Jul'=>'jul','Aug'=>'ago','Sep'=>'sep','Oct'=>'oct',
           'Nov'=>'nov','Dec'=>'dic'];
$diasSem = ['Sun'=>'dom','Mon'=>'lun','Tue'=>'mar',
            'Wed'=>'mié','Thu'=>'jue','Fri'=>'vie','Sat'=>'sáb'];
$total  = count($items);
?>

<div class="acciones-wrapper">

    <!-- Encabezado + filtros (sticky juntos) -->
    <div class="acciones-top">

        <div class="acciones-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="acciones-title mb-0">Próximas acciones</h6>
                <div class="d-flex align-items-center gap-2">
                    <span id="acciones-counter" class="nav-badge badge-blue"><?= $total ?></span>
                    <button id="btn-modo-agenda"
                            class="btn btn-sm btn-outline-secondary"
                            title="Vista agenda"
                            style="font-size:.78rem;padding:3px 10px;">
                        <i class="bi bi-calendar-week me-1"></i>Agenda
                    </button>
                </div>
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

        <!-- Encabezado de columnas (ordenable) -->
        <div class="acciones-columnas">
            <div class="col-creada sortable" data-col="creada">
                Creada <span class="sort-icon text-primary">↓</span>
            </div>
            <div class="col-accion sortable" data-col="accion">
                Acción <span class="sort-icon text-muted">↕</span>
            </div>
            <div class="col-fecha sortable" data-col="fecha">
                Fecha <span class="sort-icon text-muted">↕</span>
            </div>
            <div class="col-info"></div>
            <div class="col-hecho"></div>
            <div class="col-editar"></div>
        </div>

        <?php foreach ($items as $item):
            $vencida  = $item['fecha_accion'] !== null && $item['fecha_accion'] < $hoy;
            $fechaStr = null;
            $horaStr  = null;
            $diasStr  = null;

            if ($item['fecha_accion']) {
                $dt      = new DateTime($item['fecha_accion']);
                $diaSem  = $diasSem[$dt->format('D')];
                $fechaStr = $diaSem . ' ' . $dt->format('j') . ' ' . $meses[$dt->format('M')];

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

            $creadaDt  = new DateTime($item['created_at']);
            $creadaStr = $creadaDt->format('d/m/y');

            $tieneBreadcrumb = $item['area_nombre'] || $item['contexto_nombre'] || $item['proyecto_nombre'];
            $tieneHoras      = !empty($item['hora_inicio']) || !empty($item['hora_fin']);
        ?>
            <div class="acciones-item acciones-item-col <?= $vencida ? 'item-vencida' : '' ?>"
                 data-id="<?= $item['id'] ?>"
                 data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>"
                 data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>"
                 data-fecha-accion="<?= $item['fecha_accion'] ?? '' ?>"
                 data-tipo-tiempo="<?= $item['tipo_tiempo'] ?? '' ?>"
                 data-hora-inicio="<?= $item['hora_inicio'] ?? '' ?>"
                 data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                 data-created-at="<?= $item['created_at'] ?>">

              <div class="item acciones-item-fila">

                <div class="col-creada" title="Creada el <?= $creadaDt->format('d/m/Y H:i') ?>">
                    <?= $creadaStr ?>
                </div>

                <div class="col-accion item-body">
                    <div class="item-text"><?= htmlspecialchars($item['titulo']) ?></div>
                </div>

                <div class="col-fecha">
                    <?php if ($fechaStr): ?>
                        <span class="tag <?= $vencida ? 'tag-alert' : 'tag-date' ?>">
                            <?= $fechaStr ?>
                            <?php if ($horaStr): ?>
                                · <?= $horaStr ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($diasStr): ?>
                            <span class="tag" style="font-size:.66rem;<?= $vencida
                                ? 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;'
                                : ($diasStr === 'hoy'
                                    ? 'background:#fef9c3;color:#854d0e;border:1px solid #fde047;'
                                    : 'background:#f0fdf4;color:#166534;border:1px solid #86efac;') ?>">
                                <?= htmlspecialchars($diasStr) ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="col-info">
                    <button class="btn-toggle-info" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#info-<?= $item['id'] ?>"
                            aria-expanded="false"
                            aria-controls="info-<?= $item['id'] ?>"
                            aria-label="Ver más información">
                        <i class="bi bi-chevron-down info-chevron"></i>
                    </button>
                </div>

                <div class="col-hecho">
                    <button class="btn-check-circular" data-item-id="<?= $item['id'] ?>"
                            aria-label="Marcar como hecho">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </div>

                <div class="col-editar">
                    <button class="btn btn-sm btn-edit"
                            data-item-id="<?= $item['id'] ?>"
                            data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                            data-area-id="<?= $item['area_id'] ?? '' ?>"
                            data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
                            data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>"
                            data-fecha="<?= $item['fecha_accion'] ?? '' ?>"
                            data-hora-inicio="<?= $item['hora_inicio'] ?? '' ?>"
                            data-hora-fin="<?= $item['hora_fin'] ?? '' ?>"
                            aria-label="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>

              </div><!-- /.acciones-item-fila -->

              <!-- Fila expandible: breadcrumb + notas + horas -->
              <div id="info-<?= $item['id'] ?>" class="collapse acciones-item-info">
                <div class="acciones-item-info-body">

                    <?php if ($tieneBreadcrumb): ?>
                        <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                            <?php if ($item['area_nombre']): ?>
                                <span class="tag tag-area"
                                      <?= $item['area_color']
                                          ? 'style="background:' . htmlspecialchars($item['area_color']) . '20;color:' . htmlspecialchars($item['area_color']) . ';border:1px solid ' . htmlspecialchars($item['area_color']) . '40"'
                                          : '' ?>>
                                    <?= htmlspecialchars($item['area_nombre']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($item['contexto_nombre']): ?>
                                <span class="tag tag-ctx"
                                      <?= $item['contexto_color']
                                          ? 'style="background:' . htmlspecialchars($item['contexto_color']) . '20;color:' . htmlspecialchars($item['contexto_color']) . ';border:1px solid ' . htmlspecialchars($item['contexto_color']) . '40"'
                                          : '' ?>>
                                    @<?= htmlspecialchars($item['contexto_nombre']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($item['proyecto_nombre']): ?>
                                <span class="tag tag-proj">
                                    <i class="bi bi-folder me-1"></i><?= htmlspecialchars($item['proyecto_nombre']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tieneHoras): ?>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-clock me-1"></i>
                            <?= $item['hora_inicio'] ? substr($item['hora_inicio'], 0, 5) : '—' ?>
                            – <?= $item['hora_fin'] ? substr($item['hora_fin'], 0, 5) : '—' ?>
                        </p>
                    <?php endif; ?>

                    <!-- Notas inline -->
                    <div class="notas-wrapper">
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
              </div><!-- /.acciones-item-info -->

            </div><!-- /.acciones-item -->
        <?php endforeach; ?>

    </div><!-- /#acciones-lista -->

    <div id="agenda-vista" class="d-none px-3 pb-4"></div>

</div><!-- /.acciones-wrapper -->

<script src="/js/acciones.js"></script>
