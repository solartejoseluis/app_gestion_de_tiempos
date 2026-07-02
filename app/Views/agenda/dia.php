<?php
$hoy     = date('Y-m-d');
$meses   = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
$diasNom = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

$diaNom    = $diasNom[(int) $fecha->format('N') - 1];
$diaNum    = $fecha->format('j');
$mesNom    = $meses[(int) $fecha->format('n') - 1];
$anio      = $fecha->format('Y');
$esHoy     = $fechaStr === $hoy;

$fechaPrev = (clone $fecha)->modify('-1 day')->format('Y-m-d');
$fechaNext = (clone $fecha)->modify('+1 day')->format('Y-m-d');
$lunesSem  = (clone $fecha);
if ($lunesSem->format('N') !== '1') {
    $lunesSem->modify('last monday');
}
$semanaStr = $lunesSem->format('Y-m-d');

$horaBase = 5;
$slots    = 32;
$pxSlot   = 48;

$horaToPx = static function (string $hora) use ($horaBase, $pxSlot): int {
    [$h, $m] = explode(':', $hora);
    return (int) ((((int) $h - $horaBase) * 60 + (int) $m) / 30 * $pxSlot);
};
?>
<link rel="stylesheet" href="/css/agenda.css">

<div class="agenda-wrapper">

  <!-- ── Navegación ─────────────────────────────────── -->
  <div class="agenda-nav">
    <a href="/agenda/dia?fecha=<?= $fechaPrev ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i>
    </a>
    <a href="/agenda/dia?fecha=<?= $hoy ?>"
       class="btn btn-sm btn-outline-secondary">Hoy</a>
    <a href="/agenda/dia?fecha=<?= $fechaNext ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-right"></i>
    </a>
    <span class="agenda-nav-title">
        <?= $diaNom ?>, <?= $diaNum ?> <?= $mesNom ?> <?= $anio ?>
    </span>
    <a href="/agenda?semana=<?= $semanaStr ?>"
       class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-calendar-week me-1"></i>Semana
    </a>
  </div>

  <!-- ── Banda todo el día ──────────────────────────── -->
  <div class="agenda-allday-col"
       data-fecha="<?= $fechaStr ?>"
       style="padding:6px 12px;border-bottom:1px solid #e5e7eb;
              display:flex;flex-wrap:wrap;gap:4px;align-items:center;min-height:32px">
    <span style="font-size:.65rem;color:#9ca3af;margin-right:4px">
        todo el día
    </span>
    <?php foreach ($sinHora as $item): ?>
        <div class="agenda-chip-allday"
             data-id="<?= $item['id'] ?>"
             data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
             data-area-id="<?= $item['area_id'] ?? '' ?>"
             data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
             data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>"
             data-fecha="<?= $item['fecha_accion'] ?? '' ?>"
             title="<?= htmlspecialchars($item['titulo']) ?>">
            <?= htmlspecialchars(mb_substr($item['titulo'], 0, 30)) ?>
        </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Grid de un día ────────────────────────────── -->
  <div class="agenda-scroll" id="agenda-scroll">
    <div style="display:grid;grid-template-columns:52px 1fr;position:relative">

      <!-- Columna de horas -->
      <div class="agenda-hora-col"
           style="height:<?= $slots * $pxSlot ?>px">
        <?php for ($s = 0; $s <= $slots; $s++):
            $ha = $horaBase + ($s / 2);
            if ($ha != floor($ha)) continue;
        ?>
            <div class="agenda-hora-label"
                 style="top:<?= $s * $pxSlot ?>px">
                <?= sprintf('%02d:00', (int) $ha) ?>
            </div>
        <?php endfor; ?>
      </div>

      <!-- Columna del día -->
      <div class="agenda-dia-col <?= $esHoy ? 'es-hoy' : '' ?>"
           data-fecha="<?= $fechaStr ?>">

        <!-- Líneas de slots -->
        <?php for ($s = 0; $s < $slots; $s++): ?>
            <div class="agenda-slot-line <?= ($s % 2 === 0) ? 'hora-entera' : '' ?>"
                 style="top:<?= $s * $pxSlot ?>px"
                 data-slot="<?= $s ?>"></div>
        <?php endfor; ?>

        <!-- Bloques de tiempo -->
        <?php foreach ($bloques as $bloque):
            $top    = $horaToPx($bloque['hora_inicio']);
            $height = $horaToPx($bloque['hora_fin']) - $top;
            $color  = $bloque['color'];
        ?>
            <div class="agenda-bloque"
                 style="top:<?= $top ?>px;
                        height:<?= $height ?>px;
                        background:<?= $color ?>30;
                        border-color:<?= $color ?>;
                        color:<?= $color ?>">
                <?= htmlspecialchars($bloque['nombre']) ?>
            </div>
        <?php endforeach; ?>

        <!-- Eventos con hora -->
        <?php foreach ($conHora as $item):
            $horaIni = substr($item['hora_inicio'] ?? '08:00', 0, 5);
            $horaFin = $item['hora_fin']
                ? substr($item['hora_fin'], 0, 5)
                : date('H:i', strtotime($horaIni) + 3600);
            $clase   = $item['tipo_tiempo'] === 'cita' ? 'tipo-cita' : 'tipo-accion';
            $top    = $horaToPx($horaIni);
            $height = max($horaToPx($horaFin) - $top, $pxSlot);
        ?>
            <div class="agenda-evento <?= $clase ?>"
                 data-id="<?= $item['id'] ?>"
                 data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                 data-hora-ini="<?= $horaIni ?>"
                 data-hora-fin="<?= $horaFin ?>"
                 data-area-id="<?= $item['area_id'] ?? '' ?>"
                 data-contexto-id="<?= $item['contexto_id'] ?? '' ?>"
                 data-proyecto-id="<?= $item['proyecto_id'] ?? '' ?>"
                 data-fecha="<?= $item['fecha_accion'] ?? '' ?>"
                 style="top:<?= $top ?>px;height:<?= $height ?>px">
                <div class="agenda-evento-hora">
                    <?= $horaIni ?> – <?= $horaFin ?>
                </div>
                <div class="agenda-evento-titulo">
                    <?= htmlspecialchars($item['titulo']) ?>
                </div>
                <?php if ($item['contexto_nombre']): ?>
                <div class="agenda-evento-hora">
                    @<?= htmlspecialchars($item['contexto_nombre']) ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Completadas con hora -->
        <?php foreach ($completadas as $item):
            if (!$item['hora_inicio']) continue;
            $horaIni = substr($item['hora_inicio'], 0, 5);
            $horaFin = $item['hora_fin']
                ? substr($item['hora_fin'], 0, 5)
                : date('H:i', strtotime($horaIni) + 3600);
            $top    = $horaToPx($horaIni);
            $height = max($horaToPx($horaFin) - $top, $pxSlot);
        ?>
            <div class="agenda-evento tipo-completada"
                 data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                 data-hora-ini="<?= $horaIni ?>"
                 data-hora-fin="<?= $horaFin ?>"
                 style="top:<?= $top ?>px;height:<?= $height ?>px">
                <div class="agenda-evento-titulo">
                    <?= htmlspecialchars($item['titulo']) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Línea hora actual -->
        <?php if ($esHoy):
            $ahora  = new DateTime();
            $topNow = $horaToPx($ahora->format('H:i'));
        ?>
            <div class="agenda-now-line" style="top:<?= $topNow ?>px">
                <div class="agenda-now-dot"></div>
            </div>
        <?php endif; ?>

      </div><!-- /.agenda-dia-col -->
    </div>
  </div><!-- /.agenda-scroll -->

</div><!-- /.agenda-wrapper -->

<!-- Mini-modal: crear acción ──────────────────────────── -->
<div id="modal-crear-agenda"
     style="display:none;position:fixed;inset:0;z-index:2000;
            background:rgba(0,0,0,.4)"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;padding:20px;
                width:380px;max-width:92vw;position:absolute;
                top:50%;left:50%;transform:translate(-50%,-50%);
                box-shadow:0 8px 32px rgba(0,0,0,.18)">
        <div style="display:flex;justify-content:space-between;
                    margin-bottom:16px">
            <h6 style="margin:0;font-weight:600">Nueva acción</h6>
            <button onclick="document.getElementById('modal-crear-agenda').style.display='none'"
                    style="background:none;border:none;font-size:1.2rem;cursor:pointer">×</button>
        </div>
        <div style="margin-bottom:12px">
            <input id="crear-agenda-titulo" type="text"
                   class="form-control form-control-sm"
                   placeholder="Título de la acción *"
                   maxlength="255">
        </div>
        <p id="crear-agenda-hint-todo-dia" class="d-none"
           style="font-size:.75rem;color:#6b7280;margin:-6px 0 12px">
            <i class="bi bi-calendar-check me-1"></i>Acción de todo el día, sin hora específica.
        </p>
        <div id="crear-agenda-horas-wrapper"
             style="display:grid;grid-template-columns:1fr 1fr;
                    gap:8px;margin-bottom:12px">
            <div>
                <label style="font-size:.75rem;color:#6b7280">Hora inicio</label>
                <input id="crear-agenda-hora-ini" type="time"
                       class="form-control form-control-sm">
            </div>
            <div>
                <label style="font-size:.75rem;color:#6b7280">Hora fin</label>
                <input id="crear-agenda-hora-fin" type="time"
                       class="form-control form-control-sm">
            </div>
        </div>
        <div style="margin-bottom:12px">
            <select id="crear-agenda-area" class="form-select form-select-sm">
                <option value="">Sin área</option>
                <?php foreach ($areas_select as $a): ?>
                    <option value="<?= $a['id'] ?>">
                        <?= htmlspecialchars($a['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:12px">
            <select id="crear-agenda-contexto" class="form-select form-select-sm">
                <option value="">Sin contexto</option>
                <?php foreach ($contextos as $ctx): ?>
                    <option value="<?= $ctx['id'] ?>">
                        @<?= htmlspecialchars($ctx['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:16px">
            <select id="crear-agenda-proyecto" class="form-select form-select-sm">
                <option value="">Sin proyecto</option>
                <?php foreach ($proyectos as $prj): ?>
                    <option value="<?= $prj['id'] ?>">
                        <?= htmlspecialchars($prj['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" id="crear-agenda-fecha" value="<?= $fechaStr ?>">
        <div id="crear-agenda-error" class="text-danger small d-none mb-2"></div>
        <div style="display:flex;gap:8px">
            <button id="btn-crear-agenda-guardar" class="btn btn-sm btn-primary">
                Guardar
            </button>
            <button onclick="document.getElementById('modal-crear-agenda').style.display='none'"
                    class="btn btn-sm btn-outline-secondary">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Mini-modal: detalle evento ───────────────────────── -->
<div id="modal-evento-detalle"
     style="display:none;position:fixed;inset:0;z-index:2000;
            background:rgba(0,0,0,.4)"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;padding:20px;
                width:380px;max-width:92vw;position:absolute;
                top:50%;left:50%;transform:translate(-50%,-50%);
                box-shadow:0 8px 32px rgba(0,0,0,.18)">

        <!-- Vista detalle -->
        <div id="det-vista">
            <div style="display:flex;justify-content:space-between;
                        align-items:start;margin-bottom:12px">
                <h6 id="det-titulo"
                    style="margin:0;font-size:.95rem;font-weight:600;flex:1"></h6>
                <button onclick="document.getElementById('modal-evento-detalle').style.display='none'"
                        style="background:none;border:none;font-size:1.2rem;cursor:pointer">×</button>
            </div>
            <p id="det-hora"
               style="font-size:.82rem;color:#6b7280;margin:0 0 16px"></p>
            <div style="display:flex;gap:8px">
                <button id="det-btn-completar" class="btn btn-sm btn-success">
                    ✓ Completar
                </button>
                <button id="det-btn-editar" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button onclick="document.getElementById('modal-evento-detalle').style.display='none'"
                        class="btn btn-sm btn-outline-secondary">
                    Cerrar
                </button>
            </div>
        </div>

    </div>
</div>

<script src="/js/agenda.js"></script>
