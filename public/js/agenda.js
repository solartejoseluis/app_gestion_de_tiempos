(() => {
    'use strict';

    // ── Scroll a hora actual ─────────────────────────────
    var scrollEl = document.getElementById('agenda-scroll');
    if (scrollEl) {
        var ahora  = new Date();
        var topNow = ((ahora.getHours() - 5) * 60 + ahora.getMinutes()) / 30 * 48;
        scrollEl.scrollTop = Math.max(0, topNow - scrollEl.clientHeight / 3);
    }

    // ── Modal crear acción: abrir (compartido por slot con hora
    //    y franja "todo el día") ─────────────────────────────
    var modalCrear    = document.getElementById('modal-crear-agenda');
    var horasWrapper  = document.getElementById('crear-agenda-horas-wrapper');
    var hintTodoDia   = document.getElementById('crear-agenda-hint-todo-dia');

    function abrirModalCrear(fecha, horaIni, horaFin, mostrarHoras) {
        document.getElementById('crear-agenda-hora-ini').value = horaIni || '';
        document.getElementById('crear-agenda-hora-fin').value = horaFin || '';
        document.getElementById('crear-agenda-titulo').value   = '';
        document.getElementById('crear-agenda-fecha').value    = fecha || '';
        document.getElementById('crear-agenda-error').classList.add('d-none');

        if (horasWrapper) horasWrapper.style.display = mostrarHoras ? 'grid' : 'none';
        if (hintTodoDia)  hintTodoDia.classList.toggle('d-none', mostrarHoras);

        if (modalCrear) modalCrear.style.display = 'block';
        setTimeout(function () {
            document.getElementById('crear-agenda-titulo').focus();
        }, 100);
    }

    // ── Clic en slot vacío (con hora) → modal crear ──────────
    document.querySelectorAll('.agenda-slot-line').forEach(function (line) {
        line.style.pointerEvents = 'auto';
        line.style.cursor        = 'pointer';
        line.addEventListener('click', function (e) {
            e.stopPropagation();
            var slot     = parseInt(this.dataset.slot, 10);
            var horaH    = 5 + Math.floor(slot / 2);
            var horaM    = (slot % 2) * 30;
            var horaIni  = String(horaH).padStart(2, '0') + ':' + String(horaM).padStart(2, '0');
            var horaFinH = horaH + (horaM === 30 ? 1 : 0);
            var horaFinM = horaM === 30 ? 0 : 30;
            var horaFin  = String(horaFinH).padStart(2, '0') + ':' + String(horaFinM).padStart(2, '0');
            var col      = this.closest('.agenda-dia-col');
            var fecha    = col ? (col.dataset.fecha || '') : '';
            abrirModalCrear(fecha, horaIni, horaFin, true);
        });
    });

    // ── Clic en área vacía de la franja "todo el día" → modal
    //    crear sin hora. Ignora clics sobre chips existentes
    //    (activos o ya completados) para no interferir con sus
    //    propios handlers (detalle / ninguno). ───────────────
    document.querySelectorAll('.agenda-allday-col').forEach(function (col) {
        col.addEventListener('click', function (e) {
            if (e.target.closest('.agenda-chip-allday')) return;
            var fecha = this.dataset.fecha || '';
            abrirModalCrear(fecha, '', '', false);
        });
    });

    // ── Guardar nueva acción ──────────────────────────────
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

            var tipoTiempo = horaIni ? 'cita' : 'dia';

            fetch('/acciones/crear', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    titulo:       titulo,
                    fecha_accion: fecha,
                    tipo_tiempo:  tipoTiempo,
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

    // ── Clic en evento con hora o chip de solo día → modal detalle ──
    // Mismo modal para ambos tipos: .agenda-evento[data-id] (con hora)
    // y .agenda-chip-allday[data-id] (tipo_tiempo = 'dia', sin hora).
    // Los ya completados no tienen data-id → no abren el modal.
    var modalDet = document.getElementById('modal-evento-detalle');

    function abrirDetalle(el) {
        document.getElementById('det-titulo').textContent = el.dataset.titulo || '';

        var horaIni = el.dataset.horaIni || '';
        var horaFin = el.dataset.horaFin || '';
        document.getElementById('det-hora').textContent = horaIni
            ? horaIni + (horaFin ? ' – ' + horaFin : '')
            : 'Todo el día';

        document.getElementById('det-btn-completar').dataset.itemId = el.dataset.id;
        if (modalDet) modalDet.style.display = 'block';
    }

    document.querySelectorAll('.agenda-evento[data-id], .agenda-chip-allday[data-id]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            abrirDetalle(el);
        });
    });

    // ── Completar desde modal detalle ─────────────────────
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
                    var ev = document.querySelector(
                        '.agenda-evento[data-id="' + id + '"], .agenda-chip-allday[data-id="' + id + '"]'
                    );
                    if (ev) {
                        if (ev.classList.contains('agenda-chip-allday')) {
                            ev.classList.add('completada');
                            ev.removeAttribute('data-id');
                        } else {
                            ev.className = 'agenda-evento tipo-completada';
                            var tituloEl = ev.querySelector('.agenda-evento-titulo');
                            if (tituloEl) tituloEl.style.textDecoration = 'line-through';
                        }
                    }
                } else {
                    btn.disabled = false;
                }
            })
            .catch(function () { btn.disabled = false; });
        });
    }

    // ── Botón Editar → modal global ───────────────────────
    var btnDetEditar = document.getElementById('det-btn-editar');
    if (btnDetEditar) {
        btnDetEditar.addEventListener('click', function () {
            var id = document.getElementById('det-btn-completar').dataset.itemId;
            var ev = document.querySelector(
                '.agenda-evento[data-id="' + id + '"], .agenda-chip-allday[data-id="' + id + '"]'
            );
            document.getElementById('modal-evento-detalle').style.display = 'none';
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

    // ── Actualizar DOM del grid tras edición ──────────────
    document.addEventListener('accion:editada', function (e) {
        var d  = e.detail;
        var ev = document.querySelector(
            '.agenda-evento[data-id="' + d.id + '"], .agenda-chip-allday[data-id="' + d.id + '"]'
        );
        if (!ev) return;

        ev.dataset.titulo     = d.titulo;
        ev.dataset.areaId     = d.areaId     || '';
        ev.dataset.contextoId = d.contextoId || '';
        ev.dataset.proyectoId = d.proyectoId || '';
        ev.dataset.fecha      = d.fecha      || '';
        ev.dataset.horaIni    = d.horaInicio || '';
        ev.dataset.horaFin    = d.horaFin    || '';

        if (ev.classList.contains('agenda-evento')) {
            var tEl = ev.querySelector('.agenda-evento-titulo');
            var hEl = ev.querySelector('.agenda-evento-hora');
            if (tEl) tEl.textContent = d.titulo;
            if (hEl && d.horaInicio) hEl.textContent = d.horaInicio + (d.horaFin ? ' – ' + d.horaFin : '');
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
        } else {
            // Chip de solo día: el CSS ya recorta con ellipsis, no hace
            // falta truncar el texto a mano.
            ev.textContent = d.titulo;
            ev.title       = d.titulo;
        }
    });

})();
