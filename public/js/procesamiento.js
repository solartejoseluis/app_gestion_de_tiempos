(() => {
    // ── Estado ────────────────────────────────────────────────
    let itemId         = null;
    let modo           = '';
    let modoProyectoId = null;
    let modoAreaId     = '';

    // ── Caché de datos de selects ─────────────────────────────
    var _cache = {
        areas:     null,
        personas:  null,
        contextos: null,
        proyectos: null,
    };

    // ── Helpers DOM ───────────────────────────────────────────
    const el = (id) => document.getElementById(id);

    function show(...ids) {
        ids.forEach(id => el(id)?.classList.remove('d-none'));
    }

    function hide(...ids) {
        ids.forEach(id => el(id)?.classList.add('d-none'));
    }

    // Muestra una sección principal junto con su separador (sep-{id})
    function showSection(...ids) {
        ids.forEach(id => {
            show(id);
            show('sep-' + id.replace('proc-', ''));
        });
    }

    // Oculta una sección principal junto con su separador (sep-{id})
    function hideSection(...ids) {
        ids.forEach(id => {
            hide(id);
            hide('sep-' + id.replace('proc-', ''));
        });
    }

    function setActive(active, ...group) {
        group.forEach(b => b?.classList.remove('active'));
        active?.classList.add('active');
    }

    // ── Fetch helper ──────────────────────────────────────────
    async function post(url, params = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params),
        });
        return res.json();
    }

    async function fetchCached(key, url) {
        if (_cache[key] !== null) return _cache[key];
        var data = await post(url);
        if (data.ok) _cache[key] = data;
        return data;
    }

    // ── Poblar selects ────────────────────────────────────────
    function poblarSelect(id, items, emptyLabel) {
        const sel = el(id);
        if (!sel) return;
        sel.innerHTML = `<option value="">${emptyLabel}</option>`;
        items.forEach(({ id: val, nombre }) => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = nombre;
            sel.appendChild(opt);
        });
    }

    async function cargarProyectos(areaId) {
        const data = await post('/procesar/proyectos', areaId ? { area_id: areaId } : {});
        if (!data.ok) return;

        ['proc-a2-proyecto', 'proc-a3-proyecto',
         'proc-del-proyecto', 'proc-prog-proyecto'].forEach(id => {
            poblarSelect(id, data.data, 'Ninguno');
        });
    }

    // ── Reset ─────────────────────────────────────────────────
    function resetModal() {
        // Limpiar estado de modo proyecto
        modo           = '';
        modoProyectoId = null;
        modoAreaId     = '';

        // Restaurar secciones que pueden ocultarse en modo proyecto
        show('proc-header', 'sep-b1', 'proc-b1', 'proc-prog-proyecto-wrap');
        hide('proc-modo-proyecto');
        el('modalProcesarLabel').textContent = 'Procesar ítem';

        // Ocultar todas las secciones opcionales con sus separadores
        hideSection(
            'proc-rama-a', 'proc-b2', 'proc-b3',
            'proc-b4', 'proc-delegar', 'proc-programar'
        );

        // Ocultar subforms y campos condicionales
        hide(
            'proc-a1-form', 'proc-a2-form', 'proc-a3-form',
            'proc-proyecto-form',
            'proc-a2-fecha',
            'proc-del-fecha', 'proc-prog-fecha',
            'btn-completar-ahora'
        );

        // Quitar .active de todos los botones de bifurcación
        document.querySelectorAll('.btn-proc-bifurcacion')
            .forEach(b => b.classList.remove('active'));

        // Resetear selects a primera opción
        ['proc-area', 'proc-quien',
         'proc-a2-fecha-tipo', 'proc-del-seguimiento', 'proc-prog-tiempo'].forEach(id => {
            const s = el(id);
            if (s) s.selectedIndex = 0;
        });

        // Limpiar inputs y textarea
        ['proc-titulo-input', 'proc-a3-etiquetas', 'proc-nueva-titulo',
         'proc-a2-fecha', 'proc-del-fecha', 'proc-prog-fecha'].forEach(id => {
            const e = el(id);
            if (e) e.value = '';
        });
        const ta = el('proc-resultado-deseado');
        if (ta) ta.value = '';

        // Restaurar visualización del título
        const tituloText  = el('proc-titulo-text');
        const tituloInput = el('proc-titulo-input');
        if (tituloText)  tituloText.textContent = '';
        if (tituloInput) tituloInput.classList.add('d-none');
        tituloText?.classList.remove('d-none');

        // Restaurar tipo de fecha a date (por si se cambió a datetime-local)
        ['proc-del-fecha', 'proc-prog-fecha'].forEach(id => {
            const e = el(id);
            if (e) e.type = 'date';
        });
    }

    // ── Modal: abrir ──────────────────────────────────────────
    const modalEl = el('modalProcesar');

    modalEl.addEventListener('show.bs.modal', async (e) => {
        const trigger = e.relatedTarget;
        itemId = trigger?.dataset.itemId ?? null;
        const texto = trigger?.dataset.itemTexto ?? '';

        resetModal();
        el('proc-titulo-text').textContent = texto;
        modo = trigger?.dataset.modo ?? '';

        if (modo === 'agregar-accion') {
            modoProyectoId = trigger.dataset.proyectoId ?? null;
            modoAreaId     = trigger.dataset.areaId ?? '';
            el('proc-nombre-proyecto-label').textContent = trigger.dataset.proyectoNombre ?? '';
            show('proc-modo-proyecto');
            hide('proc-header');
            hideSection('proc-b1');
            el('modalProcesarLabel').textContent = 'Agregar acción al proyecto';

            const [personasData, contextosData] = await Promise.all([
                fetchCached('personas',  '/procesar/personas'),
                fetchCached('contextos', '/procesar/contextos'),
            ]);

            if (personasData.ok) {
                const sel = el('proc-quien');
                sel.innerHTML = '<option value="yo">Yo mismo</option>';
                personasData.data.forEach(({ id: val, nombre }) => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = nombre;
                    sel.appendChild(opt);
                });
            }

            if (contextosData.ok) {
                ['proc-del-contexto', 'proc-prog-contexto']
                    .forEach(id => poblarSelect(id, contextosData.data, 'Selecciona un contexto'));
            }

            showSection('proc-b4');
            hide('proc-delegar', 'sep-delegar');
            showSection('proc-programar');
            hide('proc-prog-proyecto-wrap');

        } else {
            const [areasData, personasData, contextosData, proyectosData] = await Promise.all([
                fetchCached('areas',     '/procesar/areas'),
                fetchCached('personas',  '/procesar/personas'),
                fetchCached('contextos', '/procesar/contextos'),
                fetchCached('proyectos', '/procesar/proyectos'),
            ]);

            if (areasData.ok) {
                poblarSelect('proc-area', areasData.data, 'Selecciona un área');
            }

            if (personasData.ok) {
                const sel = el('proc-quien');
                sel.innerHTML = '<option value="yo">Yo mismo</option>';
                personasData.data.forEach(({ id: val, nombre }) => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = nombre;
                    sel.appendChild(opt);
                });
            }

            if (contextosData.ok) {
                ['proc-del-contexto', 'proc-prog-contexto']
                    .forEach(id => poblarSelect(id, contextosData.data, 'Selecciona un contexto'));
            }

            if (proyectosData.ok) {
                ['proc-a2-proyecto', 'proc-a3-proyecto',
                 'proc-del-proyecto', 'proc-prog-proyecto'].forEach(id => {
                    poblarSelect(id, proyectosData.data, 'Ninguno');
                });
            }
        }
    });

    // ── Modal: cerrar ─────────────────────────────────────────
    modalEl.addEventListener('hidden.bs.modal', () => {
        itemId = null;
        resetModal();
    });

    // ── Editar título ─────────────────────────────────────────
    el('btn-editar-titulo').addEventListener('click', () => {
        const span  = el('proc-titulo-text');
        const input = el('proc-titulo-input');
        const editing = !input.classList.contains('d-none');

        if (editing) {
            const val = input.value.trim();
            if (val) span.textContent = val;
            input.classList.add('d-none');
            span.classList.remove('d-none');
        } else {
            input.value = span.textContent;
            span.classList.add('d-none');
            input.classList.remove('d-none');
            input.focus();
            input.select();
        }
    });

    el('proc-titulo-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') el('btn-editar-titulo').click();
        if (e.key === 'Escape') {
            el('proc-titulo-input').classList.add('d-none');
            el('proc-titulo-text').classList.remove('d-none');
        }
    });

    // ── Área → cargar proyectos ───────────────────────────────
    el('proc-area').addEventListener('change', async (e) => {
        if (e.target.value) await cargarProyectos(e.target.value);
    });

    // ── B1 — ¿Requiere acción? ────────────────────────────────
    const btnSi = el('btn-si-accion');
    const btnNo = el('btn-no-accion');

    btnSi.addEventListener('click', () => {
        setActive(btnSi, btnSi, btnNo);
        hideSection('proc-rama-a');
        showSection('proc-b2');
    });

    btnNo.addEventListener('click', () => {
        setActive(btnNo, btnSi, btnNo);
        hideSection('proc-b2', 'proc-b3', 'proc-b4', 'proc-delegar', 'proc-programar');
        hide('proc-a1-form', 'proc-a2-form', 'proc-a3-form', 'btn-completar-ahora');
        showSection('proc-rama-a');
    });

    // ── Rama A — radio visual ─────────────────────────────────
    const btnA1 = el('btn-a1');
    const btnA2 = el('btn-a2');
    const btnA3 = el('btn-a3');

    function seleccionarSubformA(activeBtn, formId) {
        setActive(activeBtn, btnA1, btnA2, btnA3);
        hide('proc-a1-form', 'proc-a2-form', 'proc-a3-form');
        show(formId);
    }

    btnA1.addEventListener('click', () => seleccionarSubformA(btnA1, 'proc-a1-form'));
    btnA2.addEventListener('click', () => seleccionarSubformA(btnA2, 'proc-a2-form'));
    btnA3.addEventListener('click', () => seleccionarSubformA(btnA3, 'proc-a3-form'));

    // ── A2 — fecha revisión ───────────────────────────────────
    el('proc-a2-fecha-tipo').addEventListener('change', (e) => {
        const fechaEl = el('proc-a2-fecha');
        if (e.target.value === 'fecha') {
            show('proc-a2-fecha');
        } else {
            hide('proc-a2-fecha');
            fechaEl.value = '';
        }
    });

    // ── B2 — ¿Es un proyecto? ─────────────────────────────────
    const btnAccionUnica = el('btn-accion-unica');
    const btnEsProyecto  = el('btn-es-proyecto');

    btnAccionUnica.addEventListener('click', () => {
        setActive(btnAccionUnica, btnAccionUnica, btnEsProyecto);
        hide('proc-proyecto-form');
        hideSection('proc-b4', 'proc-delegar', 'proc-programar');
        hide('btn-completar-ahora');
        showSection('proc-b3');
    });

    btnEsProyecto.addEventListener('click', () => {
        setActive(btnEsProyecto, btnAccionUnica, btnEsProyecto);
        hideSection('proc-b3', 'proc-b4', 'proc-delegar', 'proc-programar');
        hide('btn-completar-ahora');
        show('proc-proyecto-form');
    });

    // ── B3 — ¿Menos de 2 minutos? ────────────────────────────
    const btnMenos2 = el('btn-menos-2min');
    const btnMas2   = el('btn-mas-2min');

    btnMenos2.addEventListener('click', () => {
        setActive(btnMenos2, btnMenos2, btnMas2);
        hideSection('proc-b4', 'proc-delegar', 'proc-programar');
        show('btn-completar-ahora');
    });

    btnMas2.addEventListener('click', () => {
        setActive(btnMas2, btnMenos2, btnMas2);
        hide('btn-completar-ahora');
        showSection('proc-b4');
        // proc-quien comienza en "yo mismo", mostrar programar por defecto
        hide('proc-delegar');
        hide('sep-delegar');
        showSection('proc-programar');
    });

    // ── B4 — ¿Quién ejecuta? ─────────────────────────────────
    el('proc-quien').addEventListener('change', (e) => {
        if (e.target.value === 'yo') {
            hideSection('proc-delegar');
            showSection('proc-programar');
        } else {
            hideSection('proc-programar');
            showSection('proc-delegar');
        }
    });

    // ── Seguimiento y programación → fecha dinámica ───────────
    function configurarFecha(selectId, inputId) {
        el(selectId).addEventListener('change', (e) => {
            const fechaEl = el(inputId);
            const val = e.target.value;
            if (val === 'ninguno') {
                hide(inputId);
                fechaEl.value = '';
            } else {
                fechaEl.type = val === 'cita' ? 'datetime-local' : 'date';
                show(inputId);
            }
        });
    }

    configurarFecha('proc-del-seguimiento', 'proc-del-fecha');
    configurarFecha('proc-prog-tiempo',     'proc-prog-fecha');

    // ── Error inline ──────────────────────────────────────────
    function mostrarError(msg) {
        const div = el('proc-error');
        div.textContent = msg;
        div.classList.remove('d-none');
    }

    function ocultarError() {
        el('proc-error').classList.add('d-none');
    }

    // ── Recarga stats de un proyecto en la vista /proyectos ───
    async function recargarProyecto(proyectoId) {
        if (!proyectoId) return;
        try {
            const res  = await fetch(`/proyectos/stats?id=${proyectoId}`);
            const data = await res.json();
            if (!data.ok) return;

            const card = document.querySelector(`.proyecto-card[data-id="${proyectoId}"]`);
            if (!card) return;

            const total = parseInt(data.data.total_items, 10);
            const comp  = parseInt(data.data.items_completados, 10);
            const prox  = parseInt(data.data.proximas_acciones, 10);
            const pct   = total > 0 ? Math.round(comp / total * 100) : 0;

            const bar = card.querySelector('.progress-bar');
            if (bar) {
                bar.style.width = pct + '%';
                bar.setAttribute('aria-valuenow', pct);
            }

            const statsEl = card.querySelector('.proyecto-stats');
            if (statsEl) {
                statsEl.innerHTML =
                    `${comp} de ${total} acciones completadas &mdash; ` +
                    `<span class="proyecto-prox${prox === 0 ? ' text-danger fw-semibold' : ''}">` +
                    `${prox} próxima${prox !== 1 ? 's' : ''}</span>`;
            }

            if (prox > 0) {
                card.querySelector('.alert-danger')?.classList.add('d-none');
            }
        } catch {
            // Ignorar errores de recarga de stats
        }
    }

    // ── Recarga inbox + badge sidebar ─────────────────────────
    async function recargarInbox() {
        try {
            const res  = await fetch('/inbox/lista');
            const data = await res.json();
            if (!data.ok) return;

            // Badge sidebar (siempre actualizar)
            const badge = document.getElementById('sidebar-inbox-badge');
            if (badge) {
                const n = data.data.length;
                badge.textContent = n;
                badge.classList.toggle('d-none', n === 0);
            }

            // Lista (solo si estamos en /inbox)
            if (typeof window.recargarLista === 'function') {
                window.recargarLista(data.data);
            }
        } catch {
            // Acción ya procesada — ignorar error de recarga
        }
    }

    // ── Patrón común para todos los submits ───────────────────
    async function postAccion(endpoint, datos, btnEl, onSuccess = null) {
        const textoOriginal = btnEl.textContent.trim();
        btnEl.disabled    = true;
        btnEl.textContent = 'Guardando...';
        ocultarError();

        try {
            const data = await post(endpoint, datos);
            if (data.ok) {
                bootstrap.Modal.getInstance(el('modalProcesar')).hide();
                btnEl.disabled    = false;
                btnEl.textContent = textoOriginal;
                await recargarInbox();
                if (onSuccess) await onSuccess();
            } else {
                mostrarError(data.error ?? 'Error al procesar. Inténtalo de nuevo.');
                btnEl.disabled    = false;
                btnEl.textContent = textoOriginal;
            }
        } catch {
            mostrarError('Error de conexión. Inténtalo de nuevo.');
            btnEl.disabled    = false;
            btnEl.textContent = textoOriginal;
        }
    }

    // ── Submit: Eliminar ──────────────────────────────────────
    el('btn-confirmar-eliminar').addEventListener('click', function () {
        postAccion('/procesar/eliminar', { id: itemId }, this);
    });

    // ── Submit: Completar ahora ───────────────────────────────
    el('btn-completar-ahora').addEventListener('click', function () {
        postAccion('/procesar/completar', { id: itemId }, this);
    });

    // ── Submit: Incubar ───────────────────────────────────────
    el('btn-guardar-incubar').addEventListener('click', function () {
        postAccion('/procesar/incubar', {
            id:             itemId,
            proyecto_id:    el('proc-a2-proyecto').value   || '',
            fecha_revision: el('proc-a2-fecha').value      || '',
        }, this);
    });

    // ── Submit: Referencia ────────────────────────────────────
    el('btn-guardar-referencia').addEventListener('click', function () {
        postAccion('/procesar/referencia', {
            id:          itemId,
            proyecto_id: el('proc-a3-proyecto').value  || '',
            etiquetas:   el('proc-a3-etiquetas').value || '',
        }, this);
    });

    // ── Submit: Proyecto ──────────────────────────────────────
    el('btn-guardar-proyecto').addEventListener('click', function () {
        const resultado = el('proc-resultado-deseado').value.trim();
        if (!resultado) { mostrarError('El resultado deseado es obligatorio.'); return; }

        postAccion('/procesar/proyecto', {
            id:                itemId,
            resultado_deseado: resultado,
            area_id:           el('proc-area').value || '',
        }, this);
    });

    // ── Submit: Delegar ───────────────────────────────────────
    el('btn-guardar-delegar').addEventListener('click', function () {
        const persona  = el('proc-quien').value;
        const contexto = el('proc-del-contexto').value;
        if (!persona || persona === 'yo') { mostrarError('Selecciona una persona a quien delegar.'); return; }
        if (!contexto)                    { mostrarError('El contexto es obligatorio.');             return; }

        postAccion('/procesar/delegar', {
            id:          itemId,
            persona_id:  persona,
            contexto_id: contexto,
            proyecto_id: el('proc-del-proyecto').value    || '',
            tipo_tiempo: el('proc-del-seguimiento').value,
            fecha_accion: el('proc-del-fecha').value      || '',
        }, this);
    });

    // ── Submit: Programar ─────────────────────────────────────
    el('btn-guardar-programar').addEventListener('click', function () {
        const contexto = el('proc-prog-contexto').value;
        if (!contexto) { mostrarError('El contexto es obligatorio.'); return; }

        if (modo === 'agregar-accion') {
            const titulo = el('proc-nueva-titulo')?.value.trim() ?? '';
            if (!titulo) { mostrarError('El título de la acción es obligatorio.'); return; }

            const proyId = modoProyectoId;
            postAccion('/procesar/nueva-accion', {
                titulo,
                contexto_id:  contexto,
                proyecto_id:  proyId ?? '',
                area_id:      modoAreaId,
                tipo_tiempo:  el('proc-prog-tiempo').value,
                fecha_accion: el('proc-prog-fecha').value || '',
            }, this, () => recargarProyecto(proyId));
        } else {
            postAccion('/procesar/programar', {
                id:           itemId,
                contexto_id:  contexto,
                proyecto_id:  el('proc-prog-proyecto').value || '',
                tipo_tiempo:  el('proc-prog-tiempo').value,
                fecha_accion: el('proc-prog-fecha').value   || '',
            }, this);
        }
    });

})();
