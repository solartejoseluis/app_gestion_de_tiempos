<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<div class="app-shell">
    <button class="sidebar-toggle d-md-none"
            id="sidebar-toggle"
            aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-overlay d-none" id="sidebar-overlay"></div>
    <?php require BASE_PATH . '/app/Views/layouts/sidebar.php'; ?>
    <main class="main">
        <?php require $contentView; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require BASE_PATH . '/app/Views/components/modal_procesamiento.php'; ?>
<script src="/js/procesamiento.js"></script>
<?php require BASE_PATH . '/app/Views/components/modal_editar_accion.php'; ?>
<script src="/js/editar_accion.js"></script>
<?php require BASE_PATH . '/app/Views/components/modal_confirmar.php'; ?>
<script src="/js/confirmar.js"></script>
<script>
(function () {
    var toggle  = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!toggle || !sidebar || !overlay) return;

    function abrirSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }

    function cerrarSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.classList.add('d-none');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('sidebar-open')) {
            cerrarSidebar();
        } else {
            abrirSidebar();
        }
    });

    overlay.addEventListener('click', cerrarSidebar);

    sidebar.querySelectorAll('a.nav-item').forEach(function (a) {
        a.addEventListener('click', cerrarSidebar);
    });
}());
</script>
</body>
</html>
