<?php
$diasLabel = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
$hayBloques = !empty($bloques);
$fmtFecha = static function (?string $fecha): string {
    if (!$fecha) return '';
    $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $d = new DateTime($fecha);
    return $d->format('j') . ' ' . $meses[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
};
?>

<div class="p-4" style="max-width:960px">

    <!-- Encabezado -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h5 class="mb-1">Plantilla semanal</h5>
            <p class="text-muted small mb-0">
                Define tus bloques de tiempo recurrentes.
                Aparecerán como fondo en la vista de Agenda.
            </p>
        </div>
        <span id="autosave-indicator"
              class="text-success small mt-1"
              style="display:none;font-size:.78rem">
            Guardado · 00:00
        </span>
    </div>

    <!-- Formulario de creación -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title fw-semibold mb-3 small text-uppercase text-muted">
                Nuevo bloque
            </h6>
            <form id="form-crear-bloque">

                <div class="row g-2 mb-2">
                    <div class="col-sm-5">
                        <label for="bloque-nombre-nuevo" class="form-label small fw-semibold mb-1">
                            Nombre
                        </label>
                        <input id="bloque-nombre-nuevo" name="nombre" type="text"
                               class="form-control form-control-sm"
                               placeholder="Ej. Trabajo profundo"
                               maxlength="100" required>
                    </div>
                    <div class="col-auto">
                        <label for="bloque-color-nuevo" class="form-label small fw-semibold mb-1">
                            Color
                        </label>
                        <input id="bloque-color-nuevo" name="color" type="color"
                               class="form-control form-control-color form-control-sm"
                               value="#f0c040" title="Color del bloque">
                    </div>
                    <div class="col-sm-2">
                        <label for="bloque-hora-inicio" class="form-label small fw-semibold mb-1">
                            Inicio
                        </label>
                        <input id="bloque-hora-inicio" name="hora_inicio" type="time"
                               class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-2">
                        <label for="bloque-hora-fin" class="form-label small fw-semibold mb-1">
                            Fin
                        </label>
                        <input id="bloque-hora-fin" name="hora_fin" type="time"
                               class="form-control form-control-sm" required>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Días</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($diasLabel as $val => $lbl): ?>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="dias_semana[]"
                                           id="dia-<?= $val ?>"
                                           value="<?= $val ?>">
                                    <label class="form-check-label small fw-semibold"
                                           for="dia-<?= $val ?>"><?= $lbl ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <label for="bloque-fecha-desde" class="form-label small fw-semibold mb-1">
                            Vigente desde
                            <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input id="bloque-fecha-desde" name="fecha_inicio" type="date"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4">
                        <label for="bloque-fecha-hasta" class="form-label small fw-semibold mb-1">
                            Vigente hasta
                            <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input id="bloque-fecha-hasta" name="fecha_fin" type="date"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Agregar bloque
                        </button>
                    </div>
                </div>

                <div id="bloque-crear-error" class="text-danger small d-none"></div>

            </form>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="bloques-empty"
         class="text-center text-muted py-5 <?= $hayBloques ? 'd-none' : '' ?>">
        <i class="bi bi-layout-wtf fs-1 d-block mb-2 opacity-50"></i>
        <p class="mb-0">
            Aún no tienes bloques de tiempo.<br>
            Crea el primero para empezar a estructurar tu semana.
        </p>
    </div>

    <!-- Tabla de bloques -->
    <div id="bloques-table" class="table-responsive <?= $hayBloques ? '' : 'd-none' ?>">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:52px">Color</th>
                    <th>Nombre</th>
                    <th style="width:120px">Días</th>
                    <th style="width:135px">Horario</th>
                    <th>Vigencia</th>
                    <th class="text-center" style="width:90px">Estado</th>
                    <th style="width:155px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bloques as $bloque):
                $dias = array_filter(
                    explode(',', $bloque['dias_semana']),
                    fn($d) => isset($diasLabel[(int) $d])
                );
                $diasStr = implode(' ', array_map(fn($d) => $diasLabel[(int) $d], $dias));

                $vigencia = 'Indefinido';
                if ($bloque['fecha_inicio'] && $bloque['fecha_fin']) {
                    $vigencia = 'desde ' . $fmtFecha($bloque['fecha_inicio'])
                              . ' hasta ' . $fmtFecha($bloque['fecha_fin']);
                } elseif ($bloque['fecha_inicio']) {
                    $vigencia = 'desde ' . $fmtFecha($bloque['fecha_inicio']);
                } elseif ($bloque['fecha_fin']) {
                    $vigencia = 'hasta ' . $fmtFecha($bloque['fecha_fin']);
                }

                $horario = substr($bloque['hora_inicio'], 0, 5) . ' – ' . substr($bloque['hora_fin'], 0, 5);
            ?>
                <tr data-id="<?= $bloque['id'] ?>" data-estado="<?= $bloque['estado'] ?>">

                    <td>
                        <input type="color"
                               class="bloque-color form-control form-control-color"
                               data-id="<?= $bloque['id'] ?>"
                               value="<?= htmlspecialchars($bloque['color'], ENT_QUOTES) ?>"
                               style="width:40px;height:28px;padding:2px;cursor:pointer;"
                               title="Cambiar color">
                    </td>

                    <td>
                        <input type="text"
                               class="bloque-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"
                               data-id="<?= $bloque['id'] ?>"
                               value="<?= htmlspecialchars($bloque['nombre'], ENT_QUOTES) ?>"
                               maxlength="100">
                    </td>

                    <td>
                        <span class="text-muted small"><?= htmlspecialchars($diasStr) ?></span>
                    </td>

                    <td>
                        <span class="text-muted small"><?= htmlspecialchars($horario) ?></span>
                    </td>

                    <td>
                        <span class="text-muted small"><?= htmlspecialchars($vigencia) ?></span>
                    </td>

                    <td class="text-center td-estado-bloque">
                        <?php if ($bloque['estado'] === 'activo'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>

                    <td class="td-acciones-bloque">
                        <?php if ($bloque['estado'] === 'activo'): ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning btn-archivar-bloque"
                                    data-id="<?= $bloque['id'] ?>"
                                    title="Archivar">
                                <i class="bi bi-archive"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-eliminar-bloque"
                                    data-id="<?= $bloque['id'] ?>"
                                    title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php else: ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-success btn-restaurar-bloque"
                                    data-id="<?= $bloque['id'] ?>"
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

</div>

<script src="/js/plantilla.js"></script>
