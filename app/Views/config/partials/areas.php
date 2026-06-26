<?php
$areasActivas = array_filter($areas, fn($a) => $a['estado'] === 'activo');
$sobreLimite  = count($areasActivas) > 10;
$hayAreas     = !empty($areas);
?>

<!-- Formulario de creación -->
<form id="form-crear-area" method="POST" action="/config/areas" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-5">
            <label for="area-nombre-nueva" class="form-label small fw-semibold mb-1">
                Nombre del área
            </label>
            <input id="area-nombre-nueva" name="nombre" type="text"
                   class="form-control form-control-sm"
                   placeholder="p. ej. Trabajo, Salud, Familia…"
                   maxlength="100" required>
        </div>
        <div class="col-auto">
            <label for="area-color-nueva" class="form-label small fw-semibold mb-1">
                Color
            </label>
            <input id="area-color-nueva" name="color" type="color"
                   class="form-control form-control-color form-control-sm"
                   value="#4a90d9" title="Color del área">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Agregar área
            </button>
        </div>
    </div>
    <div id="area-crear-error" class="text-danger small d-none mt-1"></div>
</form>

<!-- Aviso de límite GTD -->
<div id="aviso-limite-areas"
     class="alert alert-warning alert-dismissible fade show py-2 small <?= $sobreLimite ? '' : 'd-none' ?>"
     role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i>
    GTD recomienda no superar 10 áreas activas. Considera archivar las que no estén activas en este período.
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<!-- Estado vacío -->
<div id="areas-empty"
     class="text-center text-muted py-5 <?= $hayAreas ? 'd-none' : '' ?>">
    <i class="bi bi-collection fs-1 d-block mb-2 opacity-50"></i>
    <p class="mb-0">
        Aún no tienes áreas configuradas.<br>
        Crea la primera para empezar a organizar tu sistema GTD.
    </p>
</div>

<!-- Tabla de áreas (siempre en el DOM, d-none si vacía) -->
<div id="areas-table" class="table-responsive <?= $hayAreas ? '' : 'd-none' ?>">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:52px">Color</th>
                <th>Nombre</th>
                <th class="text-center" style="width:130px">Proyectos activos</th>
                <th class="text-center" style="width:145px">Acciones pendientes</th>
                <th class="text-center" style="width:90px">Estado</th>
                <th style="width:155px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($areas as $area):
            $tieneItems = $area['proyectos_activos'] > 0 || $area['acciones_pendientes'] > 0;
        ?>
            <tr data-id="<?= $area['id'] ?>" data-estado="<?= htmlspecialchars($area['estado']) ?>">

                <td>
                    <input type="color"
                           class="area-color form-control form-control-color"
                           data-id="<?= $area['id'] ?>"
                           value="<?= htmlspecialchars($area['color'], ENT_QUOTES) ?>"
                           style="width:40px;height:28px;padding:2px;cursor:pointer;"
                           title="Cambiar color">
                </td>

                <td>
                    <input type="text"
                           class="area-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"
                           data-id="<?= $area['id'] ?>"
                           value="<?= htmlspecialchars($area['nombre'], ENT_QUOTES) ?>"
                           maxlength="100">
                </td>

                <td class="text-center">
                    <?php if ($area['proyectos_activos'] > 0): ?>
                        <span class="badge bg-primary"><?= (int) $area['proyectos_activos'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>
                    <?php endif; ?>
                </td>

                <td class="text-center">
                    <?php if ($area['acciones_pendientes'] > 0): ?>
                        <span class="badge bg-primary"><?= (int) $area['acciones_pendientes'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>
                    <?php endif; ?>
                </td>

                <td class="text-center td-estado">
                    <?php if ($area['estado'] === 'activo'): ?>
                        <span class="badge bg-success">Activa</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Archivada</span>
                    <?php endif; ?>
                </td>

                <td class="td-acciones">
                    <?php if ($area['estado'] === 'activo'): ?>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-editar-area"
                                data-id="<?= $area['id'] ?>"
                                title="Editar nombre">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-warning btn-archivar-area"
                                data-id="<?= $area['id'] ?>"
                                title="Archivar">
                            <i class="bi bi-archive"></i>
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar-area"
                                data-id="<?= $area['id'] ?>"
                                <?php if ($tieneItems): ?>
                                    disabled
                                    title="Tiene ítems activos — archívala primero"
                                <?php else: ?>
                                    title="Eliminar"
                                <?php endif; ?>>
                            <i class="bi bi-trash"></i>
                        </button>

                    <?php else: ?>

                        <button type="button"
                                class="btn btn-sm btn-outline-success btn-restaurar-area"
                                data-id="<?= $area['id'] ?>"
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
