<?php
$paso    = 6;
$focoVal = $revisionActiva['foco_semana'] ?? '';
$focoLen = mb_strlen($focoVal);
?>

<div class="p-4" style="max-width:720px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado -->
    <div class="mb-5">
        <small class="text-muted text-uppercase fw-semibold">Paso 6 de 6</small>
        <h5 class="mb-1 mt-1">Definir el foco de la semana</h5>
        <p class="text-muted small mb-0">
            Sal de la revisión con intención, no solo con listas ordenadas.
        </p>
    </div>

    <!-- Pregunta central -->
    <p class="fw-semibold fs-5 text-center mb-4">
        ¿Cuál es el resultado más importante que quieres lograr esta semana?
    </p>

    <!-- Textarea + contador -->
    <div class="mb-4">
        <textarea id="foco-semana-input"
                  class="form-control form-control-lg"
                  rows="4"
                  maxlength="500"
                  placeholder="Escribe tu intención para esta semana..."
        ><?= htmlspecialchars($focoVal, ENT_QUOTES) ?></textarea>
        <div class="form-text text-end mt-1">
            <span id="foco-counter"><?= $focoLen ?> / 500</span>
        </div>
    </div>

    <!-- Navegación -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <a href="/revision/paso/5" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button id="btn-completar-revision"
                class="btn btn-success btn-lg"
                <?= $focoLen === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-check-circle me-2"></i>Completar revisión
        </button>
    </div>

</div>

<div id="revision-paso-actual" data-paso="6"></div>
<script src="/js/revision.js"></script>
