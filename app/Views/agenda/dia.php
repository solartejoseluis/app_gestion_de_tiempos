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
  <?php if (!empty($sinHora)): ?>
  <div style="padding:6px 12px;border-bottom:1px solid #e5e7eb;
              display:flex;flex-wrap:wrap;gap:4px;align-items:center">
    <span style="font-size:.65rem;color:#9ca3af;margin-right:4px">
        todo el día
    </span>
    <?php foreach ($sinHora as $item): ?>
        <div class="agenda-chip-allday"
             title="<?= htmlspecialchars($item['titulo']) ?>">
            <?= htmlspecialchars(mb_substr($item['titulo'], 0, 30)) ?>
        </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

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
        <div style="display:grid;grid-template-columns:1fr 1fr;
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
<div id="modal-evento-detalle-dia"
     style="display:none;position:fixed;inset:0;z-index:2000;
            background:rgba(0,0,0,.4)"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;padding:20px;
                width:380px;max-width:92vw;position:absolute;
                top:50%;left:50%;transform:translate(-50%,-50%);
                box-shadow:0 8px 32px rgba(0,0,0,.18)">

        <!-- Vista detalle -->
        <div id="det-dia-vista">
            <div style="display:flex;justify-content:space-between;
                        align-items:start;margin-bottom:12px">
                <h6 id="det-dia-titulo"
                    style="margin:0;font-size:.95rem;font-weight:600;flex:1"></h6>
                <button onclick="document.getElementById('modal-evento-detalle-dia').style.display='none'"
                        style="background:none;border:none;font-size:1.2rem;cursor:pointer">×</button>
            </div>
            <p id="det-dia-hora"
               style="font-size:.82rem;color:#6b7280;margin:0 0 16px"></p>
            <div style="display:flex;gap:8px">
                <button id="det-dia-btn-completar" class="btn btn-sm btn-success">
                    ✓ Completar
                </button>
                <button id="det-dia-btn-editar" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button onclick="document.getElementById('modal-evento-detalle-dia').style.display='none'"
                        class="btn btn-sm btn-outline-secondary">
                    Cerrar
                </button>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Scroll a hora actual ─────────────────────────
    var scroll = document.getElementById('agenda-scroll');
    if (scroll) {
        var ahora  = new Date();
        var topNow = ((ahora.getHours() - 5) * 60 + ahora.getMinutes()) / 30 * 48;
        scroll.scrollTop = Math.max(0, topNow - scroll.clientHeight / 3);
    }

    // ── Clic en slot vacío → modal crear ────────────
    var modalCrear = document.getElementById('modal-crear-agenda');
    document.querySelectorAll('.agenda-slot-line').forEach(function (line) {
        line.style.pointerEvents = 'auto';
        line.style.cursor        = 'pointer';
        line.addEventListener('click', function (e) {
            e.stopPropagation();
            var slot   = parseInt(this.dataset.slot, 10);
            var horaH  = 5 + Math.floor(slot / 2);
            var horaM  = (slot % 2) * 30;
            var horaIni = String(horaH).padStart(2, '0') + ':' + String(horaM).padStart(2, '0');
            var horaFinH = horaH + (horaM === 30 ? 1 : 0);
            var horaFinM = horaM === 30 ? 0 : 30;
            if (horaFinM === 60) { horaFinH++; horaFinM = 0; }
            var horaFin = String(horaFinH).padStart(2, '0') + ':' + String(horaFinM).padStart(2, '0');
            document.getElementById('crear-agenda-hora-ini').value = horaIni;
            document.getElementById('crear-agenda-hora-fin').value = horaFin;
            document.getElementById('crear-agenda-titulo').value   = '';
            document.getElementById('crear-agenda-error').classList.add('d-none');
            if (modalCrear) modalCrear.style.display = 'block';
            setTimeout(function () {
                document.getElementById('crear-agenda-titulo').focus();
            }, 100);
        });
    });

    // ── Guardar nueva acción ─────────────────────────
    var btnGuardar = document.getElementById('btn-crear-agenda-guardar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            var titulo   = document.getElementById('crear-agenda-titulo').value.trim();
            var horaIni  = document.getElementById('crear-agenda-hora-ini').value;
            var horaFin  = document.getElementById('crear-agenda-hora-fin').value;
            var area     = document.getElementById('crear-agenda-area')?.value || '';
            var contexto = document.getElementById('crear-agenda-contexto').value;
            var proyecto = document.getElementById('crear-agenda-proyecto').value;
            var fecha    = document.getElementById('crear-agenda-fecha').value;
            var errEl    = document.getElementById('crear-agenda-error');

            if (!titulo) {
                errEl.textContent = 'El título es obligatorio.';
                errEl.classList.remove('d-none');
                return;
            }
            errEl.classList.add('d-none');
            btnGuardar.disabled = true;

            fetch('/acciones/crear', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    titulo:       titulo,
                    fecha_accion: fecha,
                    hora_inicio:  horaIni,
                    hora_fin:     horaFin,
                    area_id:      area,
                    contexto_id:  contexto,
                    proyecto_id:  proyecto,
                    tipo:         'accion',
                }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    modalCrear.style.display = 'none';
                    window.location.reload();
                } else {
                    errEl.textContent = data.error || 'Error al guardar.';
                    errEl.classList.remove('d-none');
                    btnGuardar.disabled = false;
                }
            })
            .catch(function () {
                errEl.textContent = 'Error de conexión.';
                errEl.classList.remove('d-none');
                btnGuardar.disabled = false;
            });
        });
    }

    // ── Clic en evento → modal detalle ──────────────
    var modalDet = document.getElementById('modal-evento-detalle-dia');
    document.querySelectorAll('.agenda-evento[data-id]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('det-dia-titulo').textContent =
                (el.querySelector('.agenda-evento-titulo') || {}).textContent?.trim() || '';
            document.getElementById('det-dia-hora').textContent =
                (el.querySelector('.agenda-evento-hora') || {}).textContent?.trim() || '';
            document.getElementById('det-dia-btn-completar').dataset.itemId = el.dataset.id;
            if (modalDet) modalDet.style.display = 'block';
        });
    });

    // ── Completar desde modal detalle ────────────────
    var btnDetComp = document.getElementById('det-dia-btn-completar');
    if (btnDetComp) {
        btnDetComp.addEventListener('click', function () {
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
                    if (ev) ev.className = 'agenda-evento tipo-completada';
                } else {
                    btn.disabled = false;
                }
            })
            .catch(function () { btn.disabled = false; });
        });
    }

    // ── Botón Editar → modal global ──────────────────────────
    var diaBtnEditar = document.getElementById('det-dia-btn-editar');
    if (diaBtnEditar) {
        diaBtnEditar.addEventListener('click', function () {
            var id = document.getElementById('det-dia-btn-completar').dataset.itemId;
            var ev = document.querySelector('.agenda-evento[data-id="' + id + '"]');
            document.getElementById('modal-evento-detalle-dia').style.display = 'none';
            if (ev && window.abrirModalEditar) {
                window.abrirModalEditar({
                    id:         ev.dataset.id,
                    titulo:     ev.dataset.titulo     || '',
                    areaId:     ev.dataset.areaId     || '',
                    contextoId: ev.dataset.contextoId || '',
                    proyectoId: ev.dataset.proyectoId || '',
                    fecha:      ev.dataset.fecha      || '',
                    horaInicio: ev.dataset.horaIni    || '',
                    horaFin:    ev.dataset.horaFin    || '',
                });
            }
        });
    }

    // ── Actualizar DOM del grid tras edición ─────────────────
    document.addEventListener('accion:editada', function (e) {
        var d  = e.detail;
        var ev = document.querySelector('.agenda-evento[data-id="' + d.id + '"]');
        if (!ev) return;
        var tEl = ev.querySelector('.agenda-evento-titulo');
        var hEl = ev.querySelector('.agenda-evento-hora');
        if (tEl) tEl.textContent = d.titulo;
        if (hEl && d.horaInicio) hEl.textContent = d.horaInicio + (d.horaFin ? ' – ' + d.horaFin : '');
        ev.dataset.titulo     = d.titulo;
        ev.dataset.areaId     = d.areaId     || '';
        ev.dataset.contextoId = d.contextoId || '';
        ev.dataset.proyectoId = d.proyectoId || '';
        ev.dataset.fecha      = d.fecha      || '';
        ev.dataset.horaIni    = d.horaInicio || '';
        ev.dataset.horaFin    = d.horaFin    || '';
        if (d.horaInicio) {
            var horaPx = function (h) {
                var p = h.split(':');
                return ((parseInt(p[0], 10) - 5) * 60 + parseInt(p[1], 10)) / 30 * 48;
            };
            var newTop    = horaPx(d.horaInicio);
            var newBottom = d.horaFin ? horaPx(d.horaFin) : newTop + 48;
            ev.style.top    = newTop + 'px';
            ev.style.height = Math.max(newBottom - newTop, 48) + 'px';
        }
    });

}());
</script>
