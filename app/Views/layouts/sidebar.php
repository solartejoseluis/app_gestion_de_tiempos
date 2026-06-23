<aside class="sidebar">

    <div class="sidebar-header">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></div>
        <div class="user-sub">Sistema GTD personal</div>
    </div>

    <nav class="sidebar-nav">

        <?php
        $nav = [
            ['href' => '/inbox',      'icon' => 'bi-inbox',          'label' => 'Inbox',
             'badge_class' => 'badge-purple', 'count' => $counters['inbox']],

            ['href' => '/acciones',   'icon' => 'bi-check2-square',  'label' => 'Próximas acciones',
             'badge_class' => 'badge-blue',   'count' => $counters['acciones']],

            ['href' => '/proyectos',  'icon' => 'bi-folder',         'label' => 'Proyectos',
             'badge_class' => 'badge-blue',   'count' => $counters['proyectos']],

            ['href' => '/espera',     'icon' => 'bi-hourglass-split','label' => 'En espera de',
             'badge_class' => $counters['espera_vencidas'] > 0 ? 'badge-warn' : 'badge-coral',
             'count' => $counters['espera']],

            ['href' => '/someday',    'icon' => 'bi-star',           'label' => 'Algún día',
             'badge_class' => 'badge-amber',  'count' => $counters['someday']],

            ['href' => '/referencia', 'icon' => 'bi-file-text',      'label' => 'Referencia',
             'badge_class' => null, 'count' => 0],

            ['href' => '/completadas','icon' => 'bi-check-all',      'label' => 'Completadas',
             'badge_class' => null, 'count' => 0],
        ];
        ?>

        <?php foreach ($nav as $item): ?>
            <a href="<?= $item['href'] ?>" class="nav-item <?= $currentRoute === $item['href'] ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span class="nav-label"><?= $item['label'] ?></span>
                <?php if ($item['badge_class'] !== null && $item['count'] > 0): ?>
                    <span class="nav-badge <?= $item['badge_class'] ?>"><?= $item['count'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="nav-separator"></div>

        <a href="/revision" class="nav-item <?= $currentRoute === '/revision' ? 'active' : '' ?>">
            <i class="bi bi-arrow-repeat"></i>
            <span class="nav-label">Revisión semanal</span>
            <?php if (!$counters['revision_ok']): ?>
                <span class="nav-badge badge-warn">!</span>
            <?php endif; ?>
        </a>

    </nav>

    <div class="sidebar-footer">
        <a href="/config" class="nav-item <?= $currentRoute === '/config' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i>
            <span class="nav-label">Configuración</span>
        </a>
        <a href="/logout" class="nav-item">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-label">Cerrar sesión</span>
        </a>
    </div>

</aside>
