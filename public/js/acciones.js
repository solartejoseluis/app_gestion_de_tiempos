(() => {
    const lista   = document.getElementById('acciones-lista');
    const counter = document.getElementById('acciones-counter');
    const empty   = document.getElementById('acciones-empty');

    // ── Estado de filtros ─────────────────────────────────────
    const ctxActivos = new Set();
    let areaActiva   = '';
    let projActivo   = '';

    // ── Helpers ───────────────────────────────────────────────
    function getItems() {
        return lista.querySelectorAll('.acciones-item');
    }

    function actualizarContador() {
        const n = [...getItems()].filter(el => !el.classList.contains('d-none')).length;
        counter.textContent = n;
        empty.classList.toggle('d-none', n > 0);
    }

    function aplicarFiltros() {
        getItems().forEach(item => {
            const ctx  = item.dataset.contextoId;
            const area = item.dataset.areaId;
            const proj = item.dataset.proyectoId;

            const passCtx  = ctxActivos.size === 0 || ctxActivos.has(ctx);
            const passArea = !areaActiva || area === areaActiva;
            const passProj = !projActivo || proj === projActivo;

            item.classList.toggle('d-none', !(passCtx && passArea && passProj));
        });
        actualizarContador();
    }

    // ── Chips de contexto ─────────────────────────────────────
    document.querySelectorAll('.filtro-ctx-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const id = chip.dataset.ctxId;
            if (ctxActivos.has(id)) {
                ctxActivos.delete(id);
                chip.classList.remove('active');
            } else {
                ctxActivos.add(id);
                chip.classList.add('active');
            }
            aplicarFiltros();
        });
    });

    // ── Selects de área y proyecto ────────────────────────────
    document.getElementById('filtro-area')?.addEventListener('change', (e) => {
        areaActiva = e.target.value;
        aplicarFiltros();
    });

    const selProyecto = document.getElementById('filtro-proyecto');
    selProyecto?.addEventListener('change', (e) => {
        projActivo = e.target.value;
        aplicarFiltros();
    });

    // Aplicar filtro de proyecto preseleccionado desde la URL
    if (selProyecto?.value) {
        projActivo = selProyecto.value;
        aplicarFiltros();
    }

    // ── Limpiar filtros ───────────────────────────────────────
    document.getElementById('btn-limpiar-filtros')?.addEventListener('click', () => {
        ctxActivos.clear();
        document.querySelectorAll('.filtro-ctx-chip').forEach(c => c.classList.remove('active'));
        const selArea = document.getElementById('filtro-area');
        const selProj = document.getElementById('filtro-proyecto');
        if (selArea) selArea.value = '';
        if (selProj) selProj.value = '';
        areaActiva = '';
        projActivo = '';
        aplicarFiltros();
    });

    // ── Completar acción ──────────────────────────────────────
    async function completarAccion(id, btnEl) {
        const textoOrig   = btnEl.textContent.trim();
        btnEl.disabled    = true;
        btnEl.textContent = '...';

        try {
            const res  = await fetch('/acciones/completar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id }),
            });
            const data = await res.json();
            if (data.ok) {
                lista.querySelector(`.acciones-item[data-id="${id}"]`)?.remove();
                actualizarContador();

                // Actualizar badge sidebar
                const badge = document.getElementById('sidebar-acciones-badge');
                if (badge) {
                    const n = parseInt(badge.textContent, 10) - 1;
                    badge.textContent = Math.max(0, n);
                    badge.classList.toggle('d-none', n <= 0);
                }
            } else {
                btnEl.disabled    = false;
                btnEl.textContent = textoOrig;
            }
        } catch {
            btnEl.disabled    = false;
            btnEl.textContent = textoOrig;
        }
    }

    // ── Delegación de clicks (btn-done y checkbox) ────────────
    lista.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-done, .btn-check-circular');
        if (btn) {
            const id = btn.dataset.itemId;
            if (id) completarAccion(id, btn);
        }
    });

    // ── Editar acción ─────────────────────────────────────────
    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-edit');
        if (!btn || !window.abrirModalEditar) return;
        window.abrirModalEditar({
            id:         btn.dataset.itemId,
            titulo:     btn.dataset.titulo      || '',
            areaId:     btn.dataset.areaId      || '',
            contextoId: btn.dataset.contextoId  || '',
            proyectoId: btn.dataset.proyectoId  || '',
            fecha:      btn.dataset.fecha        || '',
            horaInicio: btn.dataset.horaInicio   || '',
            horaFin:    btn.dataset.horaFin      || '',
        });
    });

    document.addEventListener('accion:editada', function (e) {
        var d    = e.detail;
        var fila = lista.querySelector('.acciones-item[data-id="' + d.id + '"]');
        if (!fila) return;
        var textoEl = fila.querySelector('.item-text');
        if (textoEl) textoEl.textContent = d.titulo;
        var btnEdit = fila.querySelector('.btn-edit');
        if (btnEdit) {
            btnEdit.dataset.titulo      = d.titulo;
            btnEdit.dataset.areaId      = d.areaId      || '';
            btnEdit.dataset.contextoId  = d.contextoId  || '';
            btnEdit.dataset.proyectoId  = d.proyectoId  || '';
            btnEdit.dataset.fecha       = d.fecha        || '';
            btnEdit.dataset.horaInicio  = d.horaInicio   || '';
            btnEdit.dataset.horaFin     = d.horaFin      || '';
        }
    });

})();

// ── Notas inline ─────────────────────────────────────────────────
(function () {
    'use strict';

    var lista = document.getElementById('acciones-lista');
    if (!lista) return;

    var debNotas = {};

    // Toggle "Agregar notas"
    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-notas');
        if (!btn) return;
        var wrapper   = btn.closest('.notas-wrapper');
        var expandida = wrapper ? wrapper.querySelector('.notas-expandida') : null;
        var textarea  = expandida ? expandida.querySelector('.notas-inline') : null;
        if (!expandida) return;
        btn.classList.add('d-none');
        expandida.classList.remove('d-none');
        if (textarea) textarea.focus();
    });

    // Autoguardado con debounce 800 ms
    lista.addEventListener('input', function (e) {
        var ta = e.target.closest('.notas-inline');
        if (!ta) return;
        var id         = ta.dataset.id;
        var valor      = ta.value;
        var wrapper    = ta.closest('.notas-wrapper');
        var guardadoEl = wrapper ? wrapper.querySelector('.notas-guardado') : null;

        clearTimeout(debNotas[id]);
        debNotas[id] = setTimeout(async function () {
            try {
                var res = await fetch('/acciones/' + encodeURIComponent(id), {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    new URLSearchParams({ notas: valor, _method: 'PATCH' }),
                });
                var data = await res.json();
                if (data.ok && guardadoEl) {
                    guardadoEl.classList.remove('d-none');
                    setTimeout(function () {
                        guardadoEl.classList.add('d-none');
                    }, 2000);
                }
            } catch (_) {
                // silencioso — no interrumpir la escritura
            }
        }, 800);
    });

}());

// ── Modo Agenda ──────────────────────────────────────────
(function () {
    'use strict';

    var btnModo  = document.getElementById('btn-modo-agenda');
    var listaEl  = document.getElementById('acciones-lista');
    var agendaEl = document.getElementById('agenda-vista');
    if (!btnModo || !listaEl || !agendaEl) return;

    var modoAgenda = false;

    // ── Helpers de fecha ─────────────────────────────────
    var MESES = ['ene','feb','mar','abr','may','jun',
                 'jul','ago','sep','oct','nov','dic'];
    var DIAS  = ['dom','lun','mar','mié','jue','vie','sáb'];

    function fmtFechaLarga(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');
        return DIAS[d.getDay()] + ' ' + d.getDate() +
               ' ' + MESES[d.getMonth()];
    }

    function fmtHora(datetimeStr) {
        if (!datetimeStr) return '';
        var d = new Date(datetimeStr);
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    function getPeriodo(fechaStr) {
        if (!fechaStr) return 'sin-fecha';
        var hoy   = new Date();
        hoy.setHours(0, 0, 0, 0);
        var fecha = new Date(fechaStr + 'T00:00:00');
        var diff  = Math.round((fecha - hoy) / (1000 * 60 * 60 * 24));
        if (diff < 0)   return 'vencidas';
        if (diff === 0) return 'hoy';
        if (diff === 1) return 'manana';
        if (diff <= 7)  return 'semana';
        if (diff <= 14) return 'proxima-semana';
        return 'futuro';
    }

    function escHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Construir vista agenda desde los ítems del DOM ───
    function construirAgenda() {
        var items = Array.from(
            listaEl.querySelectorAll('.acciones-item')
        ).filter(function (el) {
            return !el.classList.contains('d-none');
        });

        var grupos = {
            'vencidas':       { label: '⚠ Vencidas',        items: [], clase: 'text-danger' },
            'hoy':            { label: 'Hoy',                items: [], clase: 'text-primary fw-bold' },
            'manana':         { label: 'Mañana',             items: [], clase: '' },
            'semana':         { label: 'Esta semana',        items: [], clase: '' },
            'proxima-semana': { label: 'Próxima semana',     items: [], clase: '' },
            'futuro':         { label: 'Más adelante',       items: [], clase: '' },
            'sin-fecha':      { label: 'Sin fecha',          items: [], clase: 'text-muted' },
        };
        var orden = ['vencidas','hoy','manana','semana',
                     'proxima-semana','futuro','sin-fecha'];

        items.forEach(function (el) {
            var periodo = getPeriodo(el.dataset.fechaAccion || null);
            grupos[periodo].items.push(el);
        });

        var html = '';
        orden.forEach(function (key) {
            var g = grupos[key];
            if (g.items.length === 0) return;

            html += '<div class="agenda-grupo mb-4">';
            html += '<div class="agenda-grupo-header ' + g.clase + ' mb-2 pb-1"' +
                    ' style="border-bottom:2px solid currentColor;font-size:.82rem;' +
                    'font-weight:600;text-transform:uppercase;letter-spacing:.04em">' +
                    g.label +
                    ' <span class="fw-normal opacity-75">(' + g.items.length + ')</span></div>';

            g.items.forEach(function (el) {
                var titulo    = (el.querySelector('.item-text') || {}).textContent || '';
                var fecha     = el.dataset.fechaAccion || '';
                var tipoTiem  = el.dataset.tipoTiempo  || '';
                var fechaCita = el.dataset.fechaCita   || '';
                var ctx       = el.querySelector('.tag-ctx');
                var area      = el.querySelector('.tag-area');
                var proj      = el.querySelector('.tag-proj');

                var fechaLabel = '';
                if (fecha) {
                    fechaLabel = fmtFechaLarga(fecha);
                    if (tipoTiem === 'cita' && fechaCita) {
                        fechaLabel += ' · ⏰ ' + fmtHora(fechaCita);
                    }
                }

                html += '<div class="agenda-item d-flex align-items-start gap-2 mb-2 p-2 rounded"' +
                        ' style="background:#f8f8fc">' +
                    '<div class="flex-grow-1">' +
                        '<div class="fw-medium" style="font-size:.9rem">' +
                        escHTML(titulo.trim()) + '</div>' +
                        '<div class="d-flex flex-wrap gap-1 mt-1">';

                if (fechaLabel) {
                    html += '<span class="tag ' +
                            (key === 'vencidas' ? 'tag-alert' : 'tag-date') +
                            '">' + escHTML(fechaLabel) + '</span>';
                }
                if (ctx)  html += ctx.outerHTML;
                if (area) html += area.outerHTML;
                if (proj) html += proj.outerHTML;

                html += '</div></div>' +
                    '<button class="btn btn-sm btn-done agenda-btn-hecho flex-shrink-0"' +
                    ' data-item-id="' + el.dataset.id + '"' +
                    ' style="font-size:.75rem;padding:3px 8px">✓ Hecho</button>' +
                    '</div>';
            });

            html += '</div>';
        });

        if (html === '') {
            html = '<p class="text-muted text-center py-4">No hay acciones que mostrar.</p>';
        }

        agendaEl.innerHTML = html;

        // Botones "Hecho" dentro del modo agenda
        agendaEl.querySelectorAll('.agenda-btn-hecho').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.dataset.itemId;
                btn.disabled = true;
                fetch('/acciones/completar', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    'id=' + encodeURIComponent(id),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        var agendaItem = btn.closest('.agenda-item');
                        if (agendaItem) {
                            agendaItem.style.transition = 'opacity .25s';
                            agendaItem.style.opacity    = '0';
                            setTimeout(function () { agendaItem.remove(); }, 260);
                        }
                        // Quitar también del DOM original para que el contador sea correcto
                        var orig = listaEl.querySelector('[data-id="' + id + '"]');
                        if (orig) orig.remove();
                    } else {
                        btn.disabled = false;
                    }
                })
                .catch(function () { btn.disabled = false; });
            });
        });
    }

    // ── Toggle lista ↔ agenda ────────────────────────────
    btnModo.addEventListener('click', function () {
        modoAgenda = !modoAgenda;
        if (modoAgenda) {
            construirAgenda();
            listaEl.classList.add('d-none');
            agendaEl.classList.remove('d-none');
            btnModo.classList.remove('btn-outline-secondary');
            btnModo.classList.add('btn-secondary');
            btnModo.innerHTML = '<i class="bi bi-list-ul me-1"></i>Lista';
        } else {
            agendaEl.classList.add('d-none');
            listaEl.classList.remove('d-none');
            btnModo.classList.remove('btn-secondary');
            btnModo.classList.add('btn-outline-secondary');
            btnModo.innerHTML = '<i class="bi bi-calendar-week me-1"></i>Agenda';
        }
    });

}());
