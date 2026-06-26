<?php
$hayContextos   = !empty($contextos);
$totalContextos = count($contextos);
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <p class="text-muted small mb-0">
        Los contextos GTD definen el lugar o situación en que puedes ejecutar una acción
        (@casa, @email, @llamar…).
    </p>
    <?php if ($totalContextos < 10): ?>
    <button type="button" class="btn btn-sm btn-outline-secondary btn-cargar-sugeridos ms-3 text-nowrap flex-shrink-0">
        <i class="bi bi-stars me-1"></i>Cargar contextos GTD sugeridos
    </button>
    <?php endif; ?>
</div>

<!-- Formulario de creación -->
<form id="form-crear-contexto" method="POST" action="/config/contextos" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-sm-5">
            <label for="contexto-nombre-nuevo" class="form-label small fw-semibold mb-1">
                Nombre (sin @)
            </label>
            <input id="contexto-nombre-nuevo" name="nombre" type="text"
                   class="form-control form-control-sm"
                   placeholder="p. ej. computador, llamar, casa…"
                   maxlength="50" required>
        </div>
        <div class="col-sm-4">
            <label for="contexto-descripcion-nueva" class="form-label small fw-semibold mb-1">
                Descripción breve (opcional)
            </label>
            <input id="contexto-descripcion-nueva" name="descripcion" type="text"
                   class="form-control form-control-sm"
                   placeholder="Para qué sirve este contexto"
                   maxlength="150">
        </div>
        <div class="col-auto">
            <label for="contexto-color-nuevo" class="form-label small fw-semibold mb-1">
                Color
            </label>
            <input id="contexto-color-nuevo" name="color" type="color"
                   class="form-control form-control-color form-control-sm"
                   value="#6c757d" title="Color del contexto">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Agregar contexto
            </button>
        </div>
    </div>
    <div id="contexto-crear-error" class="text-danger small d-none mt-1"></div>
</form>

<!-- Estado vacío -->
<div id="contextos-empty"
     class="text-center text-muted py-5 <?= $hayContextos ? 'd-none' : '' ?>">
    <i class="bi bi-at fs-1 d-block mb-2 opacity-50"></i>
    <p class="mb-0">
        Aún no tienes contextos configurados.<br>
        Crea el primero o carga los contextos GTD sugeridos.
    </p>
</div>

<!-- Tabla de contextos (siempre en el DOM, d-none si vacía) -->
<div id="contextos-table" class="table-responsive <?= $hayContextos ? '' : 'd-none' ?>">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:80px">@</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th class="text-center" style="width:145px">Acciones activas</th>
                <th class="text-center" style="width:90px">Estado</th>
                <th style="width:155px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contextos as $c):
            $tieneItems = $c['acciones_activas'] > 0;
        ?>
            <tr data-id="<?= $c['id'] ?>" data-estado="<?= htmlspecialchars($c['estado']) ?>">

                <td>
                    <span class="fw-bold ctx-at"
                          style="color:<?= htmlspecialchars($c['color'], ENT_QUOTES) ?>">@</span>
                    <input type="color"
                           class="contexto-color form-control form-control-color ms-1"
                           data-id="<?= $c['id'] ?>"
                           value="<?= htmlspecialchars($c['color'], ENT_QUOTES) ?>"
                           style="width:32px;height:24px;padding:2px 4px;vertical-align:middle;"
                           title="Cambiar color">
                </td>

                <td>
                    <input type="text"
                           class="contexto-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"
                           data-id="<?= $c['id'] ?>"
                           value="<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>"
                           maxlength="50">
                </td>

                <td>
                    <input type="text"
                           class="contexto-descripcion form-control form-control-sm border-0 bg-transparent p-0 text-muted"
                           data-id="<?= $c['id'] ?>"
                           value="<?= htmlspecialchars($c['descripcion'] ?? '', ENT_QUOTES) ?>"
                           maxlength="150">
                </td>

                <td class="text-center">
                    <?php if ($c['acciones_activas'] > 0): ?>
                        <span class="badge bg-primary"><?= (int) $c['acciones_activas'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>
                    <?php endif; ?>
                </td>

                <td class="text-center td-estado-ctx">
                    <?php if ($c['estado'] === 'activo'): ?>
                        <span class="badge bg-success">Activo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Archivado</span>
                    <?php endif; ?>
                </td>

                <td class="td-acciones-ctx">
                    <?php if ($c['estado'] === 'activo'): ?>

                        <button type="button"
                                class="btn btn-sm btn-outline-warning btn-archivar-contexto"
                                data-id="<?= $c['id'] ?>"
                                title="Archivar">
                            <i class="bi bi-archive"></i>
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar-contexto"
                                data-id="<?= $c['id'] ?>"
                                <?php if ($tieneItems): ?>
                                    disabled
                                    title="Tiene acciones activas — archívalo primero"
                                <?php else: ?>
                                    title="Eliminar"
                                <?php endif; ?>>
                            <i class="bi bi-trash"></i>
                        </button>

                    <?php else: ?>

                        <button type="button"
                                class="btn btn-sm btn-outline-success btn-restaurar-contexto"
                                data-id="<?= $c['id'] ?>"
                                title="Restaurar">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                        </button>

                    <?php endif; ?>
                </td>

            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
