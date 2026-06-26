<?php
$hayPersonas    = !empty($personas);
$coloresAvatar  = ['#4a90d9','#e67e22','#2ecc71','#9b59b6','#e74c3c','#1abc9c','#f39c12','#3498db'];
?>

<!-- Formulario de creación -->
<form id="form-crear-persona" method="POST" action="/config/personas" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-sm-5">
            <label for="persona-nombre-nuevo" class="form-label small fw-semibold mb-1">
                Nombre completo
            </label>
            <input id="persona-nombre-nuevo" name="nombre" type="text"
                   class="form-control form-control-sm"
                   placeholder="p. ej. Ana García"
                   maxlength="100" required>
        </div>
        <div class="col-sm-4">
            <label for="persona-rol-nuevo" class="form-label small fw-semibold mb-1">
                Rol o relación (opcional)
            </label>
            <input id="persona-rol-nuevo" name="rol" type="text"
                   class="form-control form-control-sm"
                   placeholder="p. ej. Cliente, Jefe, Colaborador"
                   maxlength="100">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Agregar persona
            </button>
        </div>
    </div>
    <div id="persona-crear-error" class="text-danger small d-none mt-1"></div>
</form>

<!-- Estado vacío -->
<div id="personas-empty"
     class="text-center text-muted py-5 <?= $hayPersonas ? 'd-none' : '' ?>">
    <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
    <p class="mb-0">
        Aún no tienes personas configuradas.<br>
        Agrégalas para hacer seguimiento a tareas delegadas.
    </p>
</div>

<!-- Tabla de personas (siempre en el DOM, d-none si vacía) -->
<div id="personas-table" class="table-responsive <?= $hayPersonas ? '' : 'd-none' ?>">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:52px">Avatar</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th class="text-center" style="width:145px">Tareas activas</th>
                <th class="text-center" style="width:90px">Estado</th>
                <th style="width:200px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($personas as $p):
            $tieneItems    = $p['tareas_activas'] > 0;
            $palabras      = array_filter(explode(' ', (string) $p['nombre']));
            $iniciales     = implode('', array_slice(
                                array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $palabras),
                                0, 2
                             )) ?: '?';
            $colorAvatar   = $coloresAvatar[(int) $p['id'] % count($coloresAvatar)];
        ?>
            <tr data-id="<?= $p['id'] ?>" data-estado="<?= htmlspecialchars($p['estado']) ?>">

                <td>
                    <div class="per-avatar d-flex align-items-center justify-content-center
                                rounded-circle fw-bold text-white"
                         data-id="<?= $p['id'] ?>"
                         style="width:36px;height:36px;background:<?= $colorAvatar ?>;
                                font-size:.75rem;flex-shrink:0;user-select:none;">
                        <?= htmlspecialchars($iniciales) ?>
                    </div>
                </td>

                <td>
                    <input type="text"
                           class="persona-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"
                           data-id="<?= $p['id'] ?>"
                           value="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                           maxlength="100">
                </td>

                <td>
                    <input type="text"
                           class="persona-rol form-control form-control-sm border-0 bg-transparent p-0 text-muted"
                           data-id="<?= $p['id'] ?>"
                           value="<?= htmlspecialchars($p['rol'] ?? '', ENT_QUOTES) ?>"
                           maxlength="100">
                </td>

                <td class="text-center">
                    <?php if ($p['tareas_vencidas'] > 0): ?>
                        <span class="badge bg-danger"><?= (int) $p['tareas_activas'] ?></span>
                        <div class="text-danger small mt-1"><?= (int) $p['tareas_vencidas'] ?> vencida(s)</div>
                    <?php elseif ($p['tareas_activas'] > 0): ?>
                        <span class="badge bg-primary"><?= (int) $p['tareas_activas'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>
                    <?php endif; ?>
                </td>

                <td class="text-center td-estado-per">
                    <?php if ($p['estado'] === 'activo'): ?>
                        <span class="badge bg-success">Activa</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Archivada</span>
                    <?php endif; ?>
                </td>

                <td class="td-acciones-per">
                    <?php if ($p['estado'] === 'activo'): ?>

                        <a href="/espera?persona_id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-info btn-ver-tareas-persona"
                           data-id="<?= $p['id'] ?>"
                           title="Ver tareas en espera">
                            <i class="bi bi-list-task"></i>
                        </a>

                        <button type="button"
                                class="btn btn-sm btn-outline-warning btn-archivar-persona"
                                data-id="<?= $p['id'] ?>"
                                title="Archivar">
                            <i class="bi bi-archive"></i>
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar-persona"
                                data-id="<?= $p['id'] ?>"
                                <?php if ($tieneItems): ?>
                                    disabled
                                    title="Tiene tareas activas — archívala primero"
                                <?php else: ?>
                                    title="Eliminar"
                                <?php endif; ?>>
                            <i class="bi bi-trash"></i>
                        </button>

                    <?php else: ?>

                        <button type="button"
                                class="btn btn-sm btn-outline-success btn-restaurar-persona"
                                data-id="<?= $p['id'] ?>"
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
