<div class="config-wrapper p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Configuración</h4>
        <div class="d-flex align-items-center gap-2">
            <small id="autosave-indicator" class="text-muted" style="display:none">
                Guardado &middot; --:--
            </small>
            <a href="/config/exportar" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i>Exportar configuración
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-areas-btn"
                    data-bs-toggle="tab" data-bs-target="#pane-areas"
                    type="button" role="tab" aria-controls="pane-areas" aria-selected="true">
                Áreas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-proyectos-btn"
                    data-bs-toggle="tab" data-bs-target="#pane-proyectos"
                    type="button" role="tab" aria-controls="pane-proyectos" aria-selected="false">
                Proyectos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-personas-btn"
                    data-bs-toggle="tab" data-bs-target="#pane-personas"
                    type="button" role="tab" aria-controls="pane-personas" aria-selected="false">
                Personas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-contextos-btn"
                    data-bs-toggle="tab" data-bs-target="#pane-contextos"
                    type="button" role="tab" aria-controls="pane-contextos" aria-selected="false">
                Contextos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="configTabContent">

        <div class="tab-pane fade show active" id="pane-areas"
             role="tabpanel" aria-labelledby="tab-areas-btn">
            <?php include BASE_PATH . '/app/Views/config/partials/areas.php'; ?>
        </div>

        <div class="tab-pane fade" id="pane-proyectos"
             role="tabpanel" aria-labelledby="tab-proyectos-btn">
            <?php include __DIR__ . '/partials/proyectos_config.php'; ?>
        </div>

        <div class="tab-pane fade" id="pane-personas"
             role="tabpanel" aria-labelledby="tab-personas-btn">
            <?php include __DIR__ . '/partials/personas.php'; ?>
        </div>

        <div class="tab-pane fade" id="pane-contextos"
             role="tabpanel" aria-labelledby="tab-contextos-btn">
            <?php include __DIR__ . '/partials/contextos.php'; ?>
        </div>

    </div>

</div>

<script src="/js/config.js"></script>
