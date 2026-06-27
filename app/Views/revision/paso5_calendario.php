<?php
$paso   = 5;
$semana ??= ['pasada' => [], 'actual' => [], 'proxima' => []];

$renderColumna = static function (array $items, string $tipo): string {
    if (empty($items)) {
        return '<p class="text-muted small mb-0">Sin compromisos registrados</p>';
    }
    $html = '<ul class="list-unstyled mb-0">';
    foreach ($items as $it) {
        $titulo = htmlspecialchars($it['titulo']);
        if ($tipo === 'pasada') {
            $html .= '<li class="d-flex align-items-start gap-2 mb-2">'
                   . '<span class="badge bg-warning text-dark flex-shrink-0 mt-1" style="font-size:.65rem">Pendiente</span>'
                   . '<span class="text-muted small">' . $titulo . '</span>'
                   . '</li>';
        } elseif ($tipo === 'proxima') {
            $html .= '<li class="d-flex align-items-start gap-2 mb-2">'
                   . '<span class="badge bg-info text-dark flex-shrink-0 mt-1" style="font-size:.65rem">Preparar</span>'
                   . '<span class="small">' . $titulo . '</span>'
                   . '</li>';
        } else {
            $html .= '<li class="mb-2 small">' . $titulo . '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
};
?>

<div class="p-4" style="max-width:1000px">

    <?php include __DIR__ . '/partials/stepper.php'; ?>

    <!-- Encabezado del paso -->
    <div class="mb-4">
        <small class="text-muted text-uppercase fw-semibold">Paso 5 de 6</small>
        <h5 class="mb-1 mt-1">Revisar el calendario</h5>
        <p class="text-muted small mb-0">
            Revisa compromisos pasados y futuros. ¿Quedó algo incompleto la semana pasada? ¿Hay algo que preparar para la próxima?
        </p>
    </div>

    <!-- Tres columnas de semanas -->
    <div class="row g-3 mb-4">

        <!-- Semana pasada -->
        <div class="col-md-4">
            <div class="card h-100 border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-secondary">
                        <i class="bi bi-calendar3 me-2"></i>Semana pasada
                    </h6>
                    <?= $renderColumna($semana['pasada'], 'pasada') ?>
                </div>
            </div>
        </div>

        <!-- Esta semana -->
        <div class="col-md-4">
            <div class="card h-100 border-0" style="background:#e8f4fd">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-primary">
                        <i class="bi bi-calendar-check me-2"></i>Esta semana
                    </h6>
                    <?= $renderColumna($semana['actual'], 'actual') ?>
                </div>
            </div>
        </div>

        <!-- Semana próxima -->
        <div class="col-md-4">
            <div class="card h-100 border">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-info">
                        <i class="bi bi-arrow-right-circle me-2"></i>Semana próxima
                    </h6>
                    <?= $renderColumna($semana['proxima'], 'proxima') ?>
                </div>
            </div>
        </div>

    </div>

    <p class="text-muted small mb-0">
        <i class="bi bi-info-circle me-1"></i>
        El calendario GTD solo incluye compromisos con fecha específica. Las acciones sin fecha aparecen en tus listas de contexto.
    </p>

    <!-- Navegación -->
    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <a href="/revision/paso/4" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button id="btn-continuar-paso6" class="btn btn-primary">
            Continuar al Paso 6 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

</div>

<div id="revision-paso-actual" data-paso="5"></div>
<script src="/js/revision.js"></script>
