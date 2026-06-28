<?php
$hoy     = date('Y-m-d');
$meses   = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
$diasNom = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

$lunesPrev = (clone $lunes)->modify('-7 days')->format('Y-m-d');
$lunesNext = (clone $lunes)->modify('+7 days')->format('Y-m-d');

$tituloSemana = $lunes->format('j') . ' ' .
    $meses[(int) $lunes->format('n') - 1] . ' – ' .
    $domingo->format('j') . ' ' .
    $meses[(int) $domingo->format('n') - 1] . ' ' .
    $domingo->format('Y');

$horaBase = 5;   // 05:00
$slots    = 32;  // 32 slots × 30 min = 16 h
$pxSlot   = 48;  // px por slot de 30 min

$horaToPx = static function (string $hora) use ($horaBase, $pxSlot): int {
    [$h, $m] = explode(':', $hora);
    return (int) ((((int) $h - $horaBase) * 60 + (int) $m) / 30 * $pxSlot);
};
?>
<link rel="stylesheet" href="/css/agenda.css">

<div class="agenda-wrapper">

  <!-- ── Navegación ──────────────────────────────── -->
  <div class="agenda-nav">
    <a href="/agenda?semana=<?= $lunesPrev ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i>
    </a>
    <a href="/agenda" class="btn btn-sm btn-outline-secondary">
        Hoy
    </a>
    <a href="/agenda?semana=<?= $lunesNext ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-right"></i>
    </a>
    <span class="agenda-nav-title"><?= $tituloSemana ?></span>
    <a href="/agenda/dia?fecha=<?= date('Y-m-d') ?>"
       class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-calendar-day me-1"></i>Día
    </a>
    <a href="/plantilla" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-layout-wtf me-1"></i>Plantilla
    </a>
  </div>

  <!-- ── Cabecera de días ────────────────────────── -->
  <div class="agenda-header">
    <div class="agenda-header-hora"></div>
    <?php for ($d = 0; $d < 7; $d++):
        $fechaDia = (clone $lunes)->modify("+{$d} days");
        $esHoy    = $fechaDia->format('Y-m-d') === $hoy;
    ?>
    <div class="agenda-header-dia <?= $esHoy ? 'es-hoy' : '' ?>">
        <div class="dia-nombre"><?= $diasNom[$d] ?></div>
        <a href="/agenda/dia?fecha=<?= $fechaDia->format('Y-m-d') ?>"
           class="dia-num-link"
           style="text-decoration:none;color:inherit">
            <div class="dia-num"><?= $fechaDia->format('j') ?></div>
        </a>
    </div>
    <?php endfor; ?>
  </div>

  <!-- ── Banda todo el día ───────────────────────── -->
  <div class="agenda-allday">
    <div class="agenda-allday-label">todo<br>el día</div>
    <?php for ($d = 0; $d < 7; $d++): ?>
    <div class="agenda-allday-col">
        <?php foreach ($itemsPorDia[$d]['sin_hora'] as $item): ?>
            <div class="agenda-chip-allday"
                 title="<?= htmlspecialchars($item['titulo']) ?>">
                <?= htmlspecialchars(mb_substr($item['titulo'], 0, 20)) ?>
            </div>
        <?php endforeach; ?>
        <?php foreach ($completadasPorDia[$d] as $item):
            if ($item['hora_inicio']) continue; ?>
            <div class="agenda-chip-allday completada"
                 title="<?= htmlspecialchars($item['titulo']) ?>">
                <?= htmlspecialchars(mb_substr($item['titulo'], 0, 20)) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endfor; ?>
  </div>

  <!-- ── Grid scrolleable ────────────────────────── -->
  <div class="agenda-scroll" id="agenda-scroll">
    <div class="agenda-grid">

      <!-- Columna de horas -->
      <div class="agenda-hora-col"
           style="height:<?= $slots * $pxSlot ?>px">
        <?php for ($s = 0; $s <= $slots; $s++):
            $horaActual = $horaBase + ($s / 2);
            if ($horaActual != floor($horaActual)) continue;
            $top = $s * $pxSlot;
        ?>
            <div class="agenda-hora-label"
                 style="top:<?= $top ?>px">
                <?= sprintf('%02d:00', (int) $horaActual) ?>
            </div>
        <?php endfor; ?>
      </div>

      <!-- 7 columnas de días -->
      <?php for ($d = 0; $d < 7; $d++):
          $fechaDia = (clone $lunes)->modify("+{$d} days");
          $esHoy    = $fechaDia->format('Y-m-d') === $hoy;
      ?>
      <div class="agenda-dia-col <?= $esHoy ? 'es-hoy' : '' ?>"
           data-dia="<?= $d ?>"
           data-fecha="<?= $fechaDia->format('Y-m-d') ?>">

        <!-- Líneas de slots -->
        <?php for ($s = 0; $s < $slots; $s++):
            $top = $s * $pxSlot;
        ?>
            <div class="agenda-slot-line <?= ($s % 2 === 0) ? 'hora-entera' : '' ?>"
                 style="top:<?= $top ?>px"></div>
        <?php endfor; ?>

        <!-- Bloques de tiempo -->
        <?php foreach ($bloquesPorDia[$d] as $bloque):
            $top    = $horaToPx($bloque['hora_inicio']);
            $bottom = $horaToPx($bloque['hora_fin']);
            $height = $bottom - $top;
            $color  = $bloque['color'];
        ?>
            <div class="agenda-bloque"
                 style="top:<?= $top ?>px;
                        height:<?= $height ?>px;
                        background:<?= $color ?>30;
                        border-color:<?= $color ?>;
                        color:<?= $color ?>;"
                 title="<?= htmlspecialchars($bloque['nombre']) ?>">
                <?= htmlspecialchars($bloque['nombre']) ?>
            </div>
        <?php endforeach; ?>

        <!-- Eventos con hora -->
        <?php foreach ($itemsPorDia[$d]['con_hora'] as $item):
            if ($item['tipo_tiempo'] === 'cita' && $item['fecha_cita']) {
                $dtCita    = new DateTime($item['fecha_cita']);
                $horaIni   = $dtCita->format('H:i');
                $durMin    = (int) ($item['duracion_minutos'] ?? 60);
                $horaFin   = (clone $dtCita)->modify("+{$durMin} minutes")->format('H:i');
                $tipoClase = 'tipo-cita';
            } else {
                $horaIni   = substr($item['hora_inicio'] ?? '08:00', 0, 5);
                $horaFin   = $item['hora_fin']
                    ? substr($item['hora_fin'], 0, 5)
                    : date('H:i', strtotime($horaIni) + 3600);
                $tipoClase = 'tipo-accion';
            }
            $top    = $horaToPx($horaIni);
            $bottom = $horaToPx($horaFin);
            $height = max($bottom - $top, $pxSlot);
        ?>
            <div class="agenda-evento <?= $tipoClase ?>"
                 data-id="<?= $item['id'] ?>"
                 style="top:<?= $top ?>px;height:<?= $height ?>px"
                 title="<?= htmlspecialchars($item['titulo']) ?>">
                <div class="agenda-evento-hora">
                    <?= $horaIni ?> – <?= $horaFin ?>
                </div>
                <div class="agenda-evento-titulo">
                    <?= htmlspecialchars($item['titulo']) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Completadas con hora -->
        <?php foreach ($completadasPorDia[$d] as $item):
            if (!$item['hora_inicio']) continue;
            $horaIni = substr($item['hora_inicio'], 0, 5);
            $horaFin = $item['hora_fin']
                ? substr($item['hora_fin'], 0, 5)
                : date('H:i', strtotime($horaIni) + 3600);
            $top    = $horaToPx($horaIni);
            $height = max($horaToPx($horaFin) - $top, $pxSlot);
        ?>
            <div class="agenda-evento tipo-completada"
                 style="top:<?= $top ?>px;height:<?= $height ?>px"
                 title="<?= htmlspecialchars($item['titulo']) ?>">
                <div class="agenda-evento-titulo">
                    <?= htmlspecialchars($item['titulo']) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Línea de hora actual (solo hoy) -->
        <?php if ($esHoy):
            $ahora  = new DateTime();
            $topNow = $horaToPx($ahora->format('H:i'));
        ?>
            <div class="agenda-now-line" style="top:<?= $topNow ?>px">
                <div class="agenda-now-dot"></div>
            </div>
        <?php endif; ?>

      </div><!-- /.agenda-dia-col -->
      <?php endfor; ?>

    </div><!-- /.agenda-grid -->
  </div><!-- /.agenda-scroll -->

</div><!-- /.agenda-wrapper -->

<!-- Mini-modal: detalle de evento ────────────────────── -->
<div id="modal-evento-detalle"
     style="display:none;position:fixed;inset:0;z-index:2000;
            background:rgba(0,0,0,.4)"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;padding:20px;
                width:340px;max-width:90vw;position:absolute;
                top:50%;left:50%;transform:translate(-50%,-50%);
                box-shadow:0 8px 32px rgba(0,0,0,.18)">
        <div style="display:flex;justify-content:space-between;
                    align-items:start;margin-bottom:12px">
            <h6 id="det-titulo"
                style="margin:0;font-size:.95rem;font-weight:600;
                       flex:1;padding-right:8px"></h6>
            <button onclick="document.getElementById('modal-evento-detalle').style.display='none'"
                    style="background:none;border:none;font-size:1.2rem;
                           cursor:pointer;color:#6b7280">×</button>
        </div>
        <p id="det-hora" style="font-size:.82rem;color:#6b7280;margin:0 0 8px"></p>
        <p id="det-ctx"  style="font-size:.82rem;color:#6b7280;margin:0 0 12px"></p>
        <div style="display:flex;gap:8px">
            <button id="det-btn-completar" class="btn btn-sm btn-success">
                ✓ Completar
            </button>
            <button onclick="document.getElementById('modal-evento-detalle').style.display='none'"
                    class="btn btn-sm btn-outline-secondary">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Scroll a hora actual ─────────────────────────
    var scroll = document.getElementById('agenda-scroll');
    if (scroll) {
        var ahora    = new Date();
        var horaBase = 5;
        var pxSlot   = 48;
        var top = ((ahora.getHours() - horaBase) * 60 + ahora.getMinutes()) / 30 * pxSlot;
        scroll.scrollTop = Math.max(0, top - scroll.clientHeight / 3);
    }

    // ── Clic en evento → modal detalle ──────────────
    var modalDet = document.getElementById('modal-evento-detalle');
    document.querySelectorAll('.agenda-evento[data-id]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('det-titulo').textContent =
                (el.querySelector('.agenda-evento-titulo') || {}).textContent || '';
            document.getElementById('det-hora').textContent =
                (el.querySelector('.agenda-evento-hora') || {}).textContent || '';
            document.getElementById('det-ctx').textContent = '';
            document.getElementById('det-btn-completar').dataset.itemId = el.dataset.id;
            if (modalDet) modalDet.style.display = 'block';
        });
    });

    // ── Completar desde modal detalle ────────────────
    var btnDetCompletar = document.getElementById('det-btn-completar');
    if (btnDetCompletar) {
        btnDetCompletar.addEventListener('click', function () {
            var btn = this;
            var id  = btn.dataset.itemId;
            if (!id) return;
            btn.disabled = true;
            fetch('/acciones/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    if (modalDet) modalDet.style.display = 'none';
                    var ev = document.querySelector('.agenda-evento[data-id="' + id + '"]');
                    if (ev) {
                        ev.className = 'agenda-evento tipo-completada';
                        var tituloEl = ev.querySelector('.agenda-evento-titulo');
                        if (tituloEl) tituloEl.style.textDecoration = 'line-through';
                    }
                } else {
                    btn.disabled = false;
                }
            })
            .catch(function () { btn.disabled = false; });
        });
    }
});
</script>
