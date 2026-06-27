<?php
$paso  = 2;
$total = count($proyectos);
$hoy   = date('Y-m-d');
?>

<div class="p-4" style="max-width:860px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado del paso -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <small class="text-muted text-uppercase fw-semibold">Paso 2 de 6</small>
            <h5 class="mb-1 mt-1">Revisar proyectos activos</h5>
            <p class="text-muted small mb-0">
                Confirma que cada proyecto activo tiene al menos una próxima acción definida.
            </p>
        </div>
        <span id="contador-proyectos"
              class="badge <?= $total > 0 ? 'bg-warning text-dark' : 'bg-success' ?> fs-5 ms-3 flex-shrink-0">
            <?= $total ?>
        </span>
    </div>

    <?php if (empty($proyectos)): ?>

        <div class="text-center py-5">
            <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.5rem"></i>
            <p class="fw-semibold mb-1">No hay proyectos activos. ¡Todo está al día!</p>
            <p class="text-muted small mb-0">Puedes continuar con la revisión.</p>
        </div>

    <?php else: ?>

        <div id="lista-proyectos-revision" data-total="<?= $total ?>">
            <?php foreach ($proyectos as $p):
                $sinAccion = (int) $p['proximas_acciones'] === 0;
            ?>
                <div class="card mb-2 proyecto-revision-card"
                     data-proyecto-id="<?= $p['id'] ?>">
                    <div class="card-body py-3">
                        <!-- Nombre + chips + check revisado -->
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></span>
                                    <?php if ($p['area_nombre']): ?>
                                        <span class="badge fw-normal"
                                              style="background:<?= htmlspecialchars($p['area_color'] ?? '#6c757d') ?>20;
                                                     color:<?= htmlspecialchars($p['area_color'] ?? '#6c757d') ?>;
                                                     border:1px solid <?= htmlspecialchars($p['area_color'] ?? '#6c757d') ?>40">
                                            <?= htmlspecialchars($p['area_nombre']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($sinAccion): ?>
                                        <span class="badge bg-danger sin-accion-badge">
                                            Sin próxima acción
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($p['resultado_deseado']): ?>
                                    <p class="text-muted small mb-0">
                                        <?= htmlspecialchars(mb_substr($p['resultado_deseado'], 0, 100)) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0 d-none revisado-check"></i>
                        </div>

                        <!-- Botones de decisión -->
                        <div class="d-flex flex-wrap gap-2 mt-2 botones-decision">
                            <?php if (!$sinAccion): ?>
                                <button class="btn btn-sm btn-success btn-confirmar-proyecto"
                                        data-id="<?= $p['id'] ?>">
                                    <i class="bi bi-check me-1"></i>Tiene próxima acción
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-primary btn-agregar-accion-proyecto"
                                    data-id="<?= $p['id'] ?>"
                                    data-modo="agregar-accion"
                                    data-proyecto-id="<?= $p['id'] ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalProcesar">
                                <i class="bi bi-plus me-1"></i>Agregar acción
                            </button>
                            <button class="btn btn-sm btn-outline-warning btn-pausar-revision"
                                    data-id="<?= $p['id'] ?>">
                                <i class="bi bi-pause me-1"></i>Pausar
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-completar-revision"
                                    data-id="<?= $p['id'] ?>">
                                <i class="bi bi-check-circle me-1"></i>Completar
                            </button>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Navegación -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <a href="/revision/paso/1" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button id="btn-continuar-paso3"
                class="btn btn-primary"
                <?= $total > 0 ? 'disabled' : '' ?>>
            Continuar al Paso 3 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

</div>

<div id="revision-paso-actual" data-paso="2" data-total="<?= $total ?>"></div>
<script src="/js/revision.js"></script>
