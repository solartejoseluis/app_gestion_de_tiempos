<div class="proyectos-wrapper">

    <!-- Encabezado sticky -->
    <div class="proyectos-header">
        <div class="d-flex align-items-center gap-2">
            <h6 class="proyectos-title mb-0">Proyectos</h6>
            <span id="proyectos-counter" class="nav-badge badge-blue"><?= $totalActivos ?></span>
        </div>
    </div>

    <!-- Lista agrupada -->
    <div class="proyectos-lista">

        <?php if (empty($grouped)): ?>
            <div class="empty-state mt-4">
                <i class="bi bi-folder"></i>
                <p>No hay proyectos activos.<br>Crea uno procesando un ítem del inbox.</p>
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
                                        data-proyecto-id="<?= $p['id'] ?>">
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

    </div><!-- /.proyectos-lista -->

</div><!-- /.proyectos-wrapper -->

<script src="/js/proyectos.js"></script>
