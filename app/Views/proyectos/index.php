<div class="proyectos-wrapper">

    <!-- Encabezado sticky -->
    <div class="proyectos-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <h6 class="proyectos-title mb-0">Proyectos</h6>
            <span id="proyectos-counter" class="nav-badge badge-blue"><?= $totalActivos ?></span>
        </div>
        <button id="btn-nuevo-proyecto" class="btn btn-sm btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
            <i class="bi bi-plus me-1"></i>Nuevo proyecto
        </button>
    </div>

    <!-- Lista agrupada -->
    <div class="proyectos-lista">

        <?php if (empty($grouped)): ?>
            <div class="empty-state mt-4">
                <i class="bi bi-folder"></i>
                <p>No hay proyectos activos.<br>Crea uno con el botón "Nuevo proyecto".</p>
            </div>
        <?php else: ?>

            <?php foreach ($grouped as $areaId => $grupo): ?>

                <!-- Encabezado de área colapsable -->
                <div class="proyecto-area-header collapsed"
                     data-bs-toggle="collapse"
                     data-bs-target="#area-<?= $areaId ?>"
                     role="button"
                     aria-expanded="true">
                    <span>
                        <span class="proyecto-area-dot"
                              style="background:<?= htmlspecialchars($grupo['area_color']) ?>"></span>
                        <?= htmlspecialchars($grupo['area_nombre']) ?>
                        <span class="ms-2 fw-normal opacity-75">(<?= count($grupo['proyectos']) ?>)</span>
                    </span>
                    <i class="bi bi-chevron-down proyecto-area-chevron"></i>
                </div>

                <div id="area-<?= $areaId ?>" class="collapse show">

                    <?php foreach ($grupo['proyectos'] as $p):
                        $total  = (int) $p['total_items'];
                        $comp   = (int) $p['items_completados'];
                        $prox   = (int) $p['proximas_acciones'];
                        $pct    = $total > 0 ? round($comp / $total * 100) : 0;
                        $pausa  = $p['estado'] === 'pausa';
                        $sinAccion = $prox === 0 && !$pausa;
                    ?>
                        <div class="proyecto-card <?= $pausa ? 'proyecto-pausado' : '' ?>"
                             data-id="<?= $p['id'] ?>"
                             data-estado="<?= $p['estado'] ?>">

                            <!-- Nombre + badge estado -->
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="proyecto-nombre">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </span>
                                <span class="badge bg-secondary proyecto-pausa-badge <?= $pausa ? '' : 'd-none' ?>">
                                    En pausa
                                </span>
                            </div>

                            <!-- Resultado deseado -->
                            <?php if ($p['resultado_deseado']): ?>
                                <p class="proyecto-resultado mb-2">
                                    <?= htmlspecialchars($p['resultado_deseado']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Alerta sin próxima acción -->
                            <?php if ($sinAccion): ?>
                                <div class="alert alert-danger py-1 px-2 small d-flex align-items-center gap-1 mb-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Sin próxima acción definida
                                </div>
                            <?php endif; ?>

                            <!-- Barra de progreso -->
                            <div class="progress proyecto-progress mb-1">
                                <div class="progress-bar bg-success"
                                     role="progressbar"
                                     style="width:<?= $pct ?>%"
                                     aria-valuenow="<?= $pct ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100"></div>
                            </div>
                            <p class="proyecto-stats mb-3">
                                <?= $comp ?> de <?= $total ?> acciones completadas
                                &mdash;
                                <span class="proyecto-prox <?= $sinAccion ? 'text-danger fw-semibold' : '' ?>">
                                    <?= $prox ?> próxima<?= $prox !== 1 ? 's' : '' ?>
                                </span>
                            </p>

                            <!-- Acciones -->
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/acciones?proyecto_id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-list-check me-1"></i>Ver acciones
                                </a>
                                <button class="btn btn-sm btn-outline-primary btn-agregar-accion"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalProcesar"
                                        data-modo="agregar-accion"
                                        data-proyecto-id="<?= $p['id'] ?>"
                                        data-proyecto-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                        data-area-id="<?= $p['area_id'] ?? '' ?>">
                                    <i class="bi bi-plus me-1"></i>Agregar acción
                                </button>
                                <button class="btn btn-sm btn-pausar-proyecto <?= $pausa ? 'd-none' : '' ?>"
                                        data-item-id="<?= $p['id'] ?>">
                                    <i class="bi bi-pause me-1"></i>Pausar
                                </button>
                                <button class="btn btn-sm btn-reactivar-proyecto <?= $pausa ? '' : 'd-none' ?>"
                                        data-item-id="<?= $p['id'] ?>">
                                    <i class="bi bi-play me-1"></i>Reactivar
                                </button>
                                <button class="btn btn-sm btn-completar-proyecto"
                                        data-item-id="<?= $p['id'] ?>">
                                    <i class="bi bi-check-circle me-1"></i>Completar
                                </button>
                            </div>

                        </div><!-- /.proyecto-card -->
                    <?php endforeach; ?>

                </div><!-- /.collapse -->

            <?php endforeach; ?>

        <?php endif; ?>

        <!-- Proyectos completados (siempre al final, colapsado) -->
        <?php if (!empty($proyectosCompletados)): ?>
            <div class="mt-4">
                <div class="proyecto-area-header collapsed"
                     data-bs-toggle="collapse"
                     data-bs-target="#completados-collapse"
                     role="button">
                    <span>
                        <i class="bi bi-check-circle me-1"></i>
                        Proyectos completados
                        <span class="ms-2 fw-normal opacity-75">(<?= count($proyectosCompletados) ?>)</span>
                    </span>
                    <i class="bi bi-chevron-down proyecto-area-chevron"></i>
                </div>
                <div id="completados-collapse" class="collapse">
                    <?php foreach ($proyectosCompletados as $p): ?>
                        <div class="proyecto-card proyecto-card-completada">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="proyecto-nombre">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </span>
                                <span class="small text-success fw-medium">
                                    <i class="bi bi-check-circle me-1"></i>Completado
                                </span>
                            </div>
                            <?php if ($p['resultado_deseado']): ?>
                                <p class="proyecto-resultado mb-1">
                                    <?= htmlspecialchars($p['resultado_deseado']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($p['fecha_completada']): ?>
                                <p class="proyecto-stats mb-0">
                                    <?= (new DateTime($p['fecha_completada']))->format('d/m/Y') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /.proyectos-lista -->

</div><!-- /.proyectos-wrapper -->

<!-- Modal: Nuevo proyecto -->
<div class="modal fade" id="modalNuevoProyecto"
     data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-labelledby="modalNuevoProyectoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold" id="modalNuevoProyectoLabel">Nuevo proyecto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="np-nombre" class="form-label small fw-medium mb-1">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input id="np-nombre" type="text" class="form-control form-control-sm"
                           maxlength="200" placeholder="¿En qué proyecto estás trabajando?">
                </div>
                <div class="mb-3">
                    <label for="np-area" class="form-label small fw-medium mb-1">Área</label>
                    <select id="np-area" class="form-select form-select-sm">
                        <option value="">Sin área</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="np-resultado" class="form-label small fw-medium mb-1">
                        Resultado deseado <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <textarea id="np-resultado" class="form-control form-control-sm" rows="2"
                              placeholder="¿Cómo se verá cuando esté completo?"></textarea>
                </div>
                <div id="np-error" class="alert alert-danger d-none py-2 small mt-2" role="alert"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-crear-proyecto" class="btn btn-sm btn-primary">
                    <i class="bi bi-folder-plus me-1"></i>Crear proyecto
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/proyectos.js"></script>
