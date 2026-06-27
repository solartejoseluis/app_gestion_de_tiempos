<?php
$labels = ['Inbox', 'Proyectos', 'Espera', 'Algún día', 'Calendario', 'Foco'];
?>
<nav aria-label="Progreso de revisión" class="mb-4">
    <ol class="list-unstyled d-flex align-items-center mb-0">
        <?php for ($i = 1; $i <= 6; $i++):
            $done    = $i < $paso;
            $current = $i === $paso;
        ?>
            <?php if ($i > 1): ?>
                <li class="flex-grow-1 px-1" aria-hidden="true">
                    <hr class="m-0 opacity-100"
                        style="border-color:<?= $done ? '#198754' : '#dee2e6' ?>;border-width:2px">
                </li>
            <?php endif; ?>
            <li class="d-flex flex-column align-items-center" style="flex-shrink:0">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold
                            <?= $done ? 'bg-success text-white' : ($current ? 'bg-primary text-white' : 'bg-light text-muted border') ?>"
                     style="width:30px;height:30px;font-size:.8rem">
                    <?php if ($done): ?>
                        <i class="bi bi-check-lg"></i>
                    <?php else: ?>
                        <?= $i ?>
                    <?php endif; ?>
                </div>
                <span class="d-none d-sm-block mt-1
                             <?= $current ? 'text-primary fw-semibold' : 'text-muted' ?>"
                      style="font-size:.7rem;white-space:nowrap">
                    <?= htmlspecialchars($labels[$i - 1]) ?>
                </span>
            </li>
        <?php endfor; ?>
    </ol>
</nav>
