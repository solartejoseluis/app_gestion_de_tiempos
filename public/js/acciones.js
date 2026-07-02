(() => {
    const wrapper = document.querySelector('.acciones-wrapper');
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

    // ── Ordenamiento por columnas ───────────────────────────────
    // Mismo mecanismo que /config (data-* + Array.sort() + reordenar
    // con appendChild), adaptado a un contenedor flex en vez de <table>.
    const columnasHeader = document.querySelector('.acciones-columnas');
    let sortCol = 'creada';
    let sortDir = 'desc';

    function compararColumna(col, a, b) {
        var va, vb;

        if (col === 'accion') {
            va = (a.dataset.titulo || '').toLowerCase();
            vb = (b.dataset.titulo || '').toLowerCase();
            return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        }

        if (col === 'fecha') {
            va = a.dataset.fechaAccion || '';
            vb = b.dataset.fechaAccion || '';
            if (!va && !vb) return 0;
            if (!va) return 1;  // sin fecha siempre al final
            if (!vb) return -1;
            return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        }

        // 'creada' (por defecto)
        va = a.dataset.createdAt || '';
        vb = b.dataset.createdAt || '';
        return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
    }

    function ordenarPor(col, forzarDir) {
        if (forzarDir) {
            sortDir = forzarDir;
        } else if (sortCol === col) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortDir = 'asc';
        }
        sortCol = col;

        if (columnasHeader) {
            columnasHeader.querySelectorAll('.sort-icon').forEach(s => {
                s.textContent = '↕';
                s.classList.remove('text-primary');
                s.classList.add('text-muted');
            });
            const activo = columnasHeader.querySelector('[data-col="' + col + '"] .sort-icon');
            if (activo) {
                activo.textContent = sortDir === 'asc' ? '↑' : '↓';
                activo.classList.remove('text-muted');
                activo.classList.add('text-primary');
            }
        }

        const filas = [...getItems()].sort((a, b) => compararColumna(col, a, b));
        filas.forEach(fila => lista.appendChild(fila));
    }

    columnasHeader?.addEventListener('click', (e) => {
        const th = e.target.closest('.sortable');
        if (!th) return;
        ordenarPor(th.dataset.col);
    });

    // Orden inicial por defecto: Creada, descendente
    ordenarPor('creada', 'desc');

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
        const origHtml = btnEl.innerHTML;
        btnEl.disabled  = true;
        btnEl.innerHTML = '<i class="bi bi-hourglass"></i>';

        try {
            const res  = await fetch('/acciones/completar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id }),
            });
            const data = await res.json();
            if (data.ok) {
                lista.querySelector(`.acciones-item[data-id="${id}"]`)?.remove();
                document.querySelector(`#agenda-vista .agenda-item[data-id="${id}"]`)?.remove();
                actualizarContador();

                // Actualizar badge sidebar
                const badge = document.getElementById('sidebar-acciones-badge');
                if (badge) {
                    const n = parseInt(badge.textContent, 10) - 1;
                    badge.textContent = Math.max(0, n);
                    badge.classList.toggle('d-none', n <= 0);
                }
            } else {
                btnEl.disabled  = false;
                btnEl.innerHTML = origHtml;
            }
        } catch {
            btnEl.disabled  = false;
            btnEl.innerHTML = origHtml;
        }
    }

    // ── Completar (checkbox circular) ─────────────────────────
    // Sobre .acciones-wrapper (no solo #acciones-lista) para que también
    // funcione en los ítems clonados del modo agenda (#agenda-vista) —
    // ambos modos usan el mismo .btn-check-circular y el mismo flujo.
    wrapper.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-check-circular');
        if (btn) {
            const id = btn.dataset.itemId;
            if (id) completarAccion(id, btn);
        }
    });

    // ── Editar acción ─────────────────────────────────────────
    wrapper.addEventListener('click', function (e) {
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

    function sincronizarBtnEdit(btnEdit, d) {
        if (!btnEdit) return;
        btnEdit.dataset.titulo      = d.titulo;
        btnEdit.dataset.areaId      = d.areaId      || '';
        btnEdit.dataset.contextoId  = d.contextoId  || '';
        btnEdit.dataset.proyectoId  = d.proyectoId  || '';
        btnEdit.dataset.fecha       = d.fecha        || '';
        btnEdit.dataset.horaInicio  = d.horaInicio   || '';
        btnEdit.dataset.horaFin     = d.horaFin      || '';
    }

    document.addEventListener('accion:editada', function (e) {
        var d = e.detail;

        // DOM de lista
        var fila = lista.querySelector('.acciones-item[data-id="' + d.id + '"]');
        if (fila) {
            var textoEl = fila.querySelector('.item-text');
            if (textoEl) textoEl.textContent = d.titulo;

            // Mantener sincronizados los data-* usados por filtros/orden/modo agenda
            fila.dataset.titulo      = d.titulo;
            fila.dataset.areaId      = d.areaId      || '';
            fila.dataset.contextoId  = d.contextoId  || '';
            fila.dataset.proyectoId  = d.proyectoId  || '';
            fila.dataset.fechaAccion = d.fecha        || '';
            fila.dataset.horaInicio  = d.horaInicio   || '';

            sincronizarBtnEdit(fila.querySelector('.btn-edit'), d);
        }

        // DOM de agenda (si el ítem está actualmente renderizado ahí)
        var agendaItem = document.querySelector('#agenda-vista .agenda-item[data-id="' + d.id + '"]');
        if (agendaItem) {
            var agendaTexto = agendaItem.querySelector('.agenda-item-titulo');
            if (agendaTexto) agendaTexto.textContent = d.titulo;

            sincronizarBtnEdit(agendaItem.querySelector('.btn-edit'), d);
        }
    });

    document.addEventListener('accion:eliminada', function (e) {
        var fila = lista.querySelector('.acciones-item[data-id="' + e.detail.id + '"]');
        if (fila) fila.remove();

        var agendaItem = document.querySelector('#agenda-vista .agenda-item[data-id="' + e.detail.id + '"]');
        if (agendaItem) agendaItem.remove();

        actualizarContador();

        var badge = document.getElementById('sidebar-acciones-badge');
        if (badge) {
            var n = parseInt(badge.textContent, 10) - 1;
            badge.textContent = Math.max(0, n);
            badge.classList.toggle('d-none', n <= 0);
        }
    });

})();

// ── Notas inline ─────────────────────────────────────────────────
(function () {
    'use strict';

    // Sobre .acciones-wrapper (no solo #acciones-lista) para que también
    // funcione en las notas clonadas del modo agenda (#agenda-vista).
    var wrapperNotas = document.querySelector('.acciones-wrapper');
    if (!wrapperNotas) return;

    var debNotas = {};

    // Toggle "Agregar notas"
    wrapperNotas.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-notas');
        if (!btn) return;
        var notasWrapper = btn.closest('.notas-wrapper');
        var expandida = notasWrapper ? notasWrapper.querySelector('.notas-expandida') : null;
        var textarea  = expandida ? expandida.querySelector('.notas-inline') : null;
        if (!expandida) return;
        btn.classList.add('d-none');
        expandida.classList.remove('d-none');
        if (textarea) textarea.focus();
    });

    // Autoguardado con debounce 800 ms
    wrapperNotas.addEventListener('input', function (e) {
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

    function diffDias(fechaStr) {
        var hoy   = new Date();
        hoy.setHours(0, 0, 0, 0);
        var fecha = new Date(fechaStr + 'T00:00:00');
        return Math.round((fecha - hoy) / (1000 * 60 * 60 * 24));
    }

    function getPeriodo(fechaStr) {
        if (!fechaStr) return 'sin-fecha';
        var diff = diffDias(fechaStr);
        if (diff < 0)   return 'vencidas';
        if (diff === 0) return 'hoy';
        if (diff === 1) return 'manana';
        if (diff <= 7)  return 'semana';
        if (diff <= 14) return 'proxima-semana';
        return 'futuro';
    }

    // Mismo texto que $diasStr en app/Views/acciones/index.php
    function diasRelativosLabel(diff) {
        if (diff === 0)  return 'hoy';
        if (diff === 1)  return 'mañana';
        if (diff > 1)    return 'en ' + diff + ' días';
        if (diff === -1) return 'ayer';
        return (-diff) + ' días pasada';
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
                var id         = el.dataset.id;
                var titulo     = (el.querySelector('.item-text') || {}).textContent || '';
                var fecha      = el.dataset.fechaAccion || '';
                var tipoTiem   = el.dataset.tipoTiempo  || '';
                var horaInicio = el.dataset.horaInicio  || '';

                // Mismos datos y acciones que la fila de lista: el botón
                // Editar (con Borrar dentro del modal) y el panel de info
                // (breadcrumb + horas + notas) se clonan tal cual desde el
                // ítem correspondiente de #acciones-lista.
                var btnEditOrig  = el.querySelector('.btn-edit');
                var infoBodyOrig = el.querySelector('.acciones-item-info-body');

                var fechaLabel = '';
                if (fecha) {
                    fechaLabel = fmtFechaLarga(fecha);
                    if (tipoTiem === 'cita' && horaInicio) {
                        fechaLabel += ' · ⏰ ' + horaInicio.slice(0, 5);
                    }
                }

                html += '<div class="agenda-item mb-2 p-2 rounded" style="background:#f8f8fc" data-id="' + id + '">';
                html +=     '<div class="d-flex align-items-center gap-2">';
                html +=         '<div class="flex-grow-1">';
                html +=             '<div class="agenda-item-titulo fw-medium" style="font-size:.9rem">' +
                                    escHTML(titulo.trim()) + '</div>';
                html +=             '<div class="d-flex flex-wrap gap-1 mt-1">';

                // Frente de la tarjeta = mismo contenido que la fila de lista
                // (título + chip de fecha + chip de días relativos).
                // Contexto/área/proyecto viven solo en el panel Info.
                if (fechaLabel) {
                    html += '<span class="tag ' +
                            (key === 'vencidas' ? 'tag-alert' : 'tag-date') +
                            '">' + escHTML(fechaLabel) + '</span>';

                    var diasLabel = diasRelativosLabel(diffDias(fecha));
                    var diasEstilo = key === 'vencidas'
                        ? 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;'
                        : (key === 'hoy'
                            ? 'background:#fef9c3;color:#854d0e;border:1px solid #fde047;'
                            : 'background:#f0fdf4;color:#166534;border:1px solid #86efac;');
                    html += '<span class="tag" style="font-size:.66rem;' + diasEstilo + '">' +
                            escHTML(diasLabel) + '</span>';
                }

                html +=             '</div>';
                html +=         '</div>'; // /flex-grow-1

                html +=         '<div class="d-flex align-items-center gap-1 flex-shrink-0">';
                html +=             '<button class="btn-toggle-info" type="button"' +
                                    ' data-bs-toggle="collapse"' +
                                    ' data-bs-target="#agenda-info-' + id + '"' +
                                    ' aria-expanded="false"' +
                                    ' aria-controls="agenda-info-' + id + '"' +
                                    ' aria-label="Ver más información">' +
                                    '<i class="bi bi-chevron-down info-chevron"></i></button>';
                html +=             '<button class="btn-check-circular" data-item-id="' + id + '"' +
                                    ' aria-label="Marcar como hecho">' +
                                    '<i class="bi bi-check-lg"></i></button>';
                if (btnEditOrig) html += btnEditOrig.outerHTML;
                html +=         '</div>';
                html +=     '</div>'; // /d-flex fila

                html +=     '<div id="agenda-info-' + id + '" class="collapse agenda-item-info">' +
                            (infoBodyOrig ? infoBodyOrig.outerHTML : '') +
                            '</div>';
                html += '</div>'; // /.agenda-item
            });

            html += '</div>';
        });

        if (html === '') {
            html = '<p class="text-muted text-center py-4">No hay acciones que mostrar.</p>';
        }

        agendaEl.innerHTML = html;
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
