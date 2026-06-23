<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-light bg-white border-bottom px-4">
        <span class="navbar-brand fw-bold"><?= htmlspecialchars(APP_NAME) ?></span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small"><?= htmlspecialchars($nombre) ?></span>
            <a href="/logout" class="btn btn-outline-secondary btn-sm">Cerrar sesión</a>
        </div>
    </nav>

    <div class="container py-5">
        <h4>Bienvenido al GTD App — Inbox en construcción</h4>
    </div>

</body>
</html>
