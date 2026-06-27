(function () {
    'use strict';

    const lista       = document.getElementById('someday-lista');
    const counter     = document.getElementById('someday-counter');
    const warnBadge   = document.getElementById('someday-warn');
    const filtroArea  = document.getElementById('filtro-area');
    const btnLimpiar  = document.getElementById('btn-limpiar-filtros');

    // Modales — IDs de los elementos en el DOM
    const modalPospEl  = document.getElementById('modalSdPosponer');
    const modalElimEl  = document.getElementById('modalSdEliminar');
    const fechaInput   = document.getElementById('sd-posponer-fecha');
    const errPosp      = document.getElementById('sd-posponer-error');
    const errElim      = document.getElementById('sd-eliminar-error');
    const btnConfPosp  = document.getElementById('btn-sd-confirmar-posponer');
    const btnConfElim  = document.getElementById('btn-sd-confirmar-eliminar');

    let idActivo = null;

    // Estado de filtros
    let areaActiva = '';

    // ─── Filtrado ────────────────────────────────────────────────────────────

    function getItems() {
        return lista.querySelectorAll('.someday-item');
    }

    function actualizarContador() {
        const visibles = [...getItems()].filter(i => !i.classList.contains('d-none'));
        const revisarHoy = visibles.filter(i => i.classList.contains('item-revisar')).length;

        counter.textContent = visibles.length;

        if (warnBadge) {
            warnBadge.textContent = revisarHoy + ' para revisar';
            warnBadge.classList.toggle('d-none', revisarHoy === 0);
        }

        const empty = document.getElementById('someday-empty');
        if (empty) empty.classList.toggle('d-none', visibles.length > 0);
    }

    function aplicarFiltros() {
        getItems().forEach(item => {
            const passArea = !areaActiva || item.dataset.areaId === areaActiva;
            item.classList.toggle('d-none', !passArea);
        });
        actualizarContador();
    }

    filtroArea?.addEventListener('change', () => {
        areaActiva = filtroArea.value;
        aplicarFiltros();
    });

    btnLimpiar?.addEventListener('click', () => {
        areaActiva = '';
        if (filtroArea) filtroArea.value = '';
        aplicarFiltros();
    });

    // ─── Utilidades ──────────────────────────────────────────────────────────

    function setLoading(btn, on) {
        btn.disabled = on;
        if (on) btn.dataset.orig = btn.innerHTML;
        else    btn.innerHTML = btn.dataset.orig ?? btn.innerHTML;
    }

    function mostrarError(el, msg) {
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function ocultarError(el) {
        el.classList.add('d-none');
        el.textContent = '';
    }

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
        }).then(r => r.json());
    }

    function eliminarItemDOM(id) {
        const el = lista.querySelector(`.someday-item[data-id="${id}"]`);
        if (el) el.remove();
        actualizarContador();
    }

    // ─── Delegación de eventos sobre la lista ────────────────────────────────

    lista.addEventListener('click', (e) => {

        // Botón Activar
        const btnActivar = e.target.closest('.btn-activar');
        if (btnActivar) {
            const id = btnActivar.dataset.itemId;
            setLoading(btnActivar, true);
            post('/someday/activar', { id })
                .then(res => {
                    if (res.ok) {
                        eliminarItemDOM(id);
                    } else {
                        setLoading(btnActivar, false);
                        alert(res.error ?? 'Error al activar el ítem.');
                    }
                })
                .catch(() => {
                    setLoading(btnActivar, false);
                    alert('Error de red.');
                });
            return;
        }

        // Botón Posponer
        const btnPosp = e.target.closest('.btn-posponer');
        if (btnPosp) {
            idActivo = btnPosp.dataset.itemId;
            const fechaActual = btnPosp.dataset.fecha;
            if (fechaInput) fechaInput.value = fechaActual ?? '';
            ocultarError(errPosp);
            bootstrap.Modal.getOrCreateInstance(modalPospEl).show();
            return;
        }

        // Botón Eliminar
        const btnElim = e.target.closest('.btn-eliminar');
        if (btnElim) {
            idActivo = btnElim.dataset.itemId;
            ocultarError(errElim);
            bootstrap.Modal.getOrCreateInstance(modalElimEl).show();
            return;
        }
    });

    // ─── Confirmar posponer ──────────────────────────────────────────────────

    btnConfPosp?.addEventListener('click', () => {
        const fecha = fechaInput?.value ?? '';
        if (!fecha) {
            mostrarError(errPosp, 'Selecciona una fecha de revisión.');
            return;
        }
        setLoading(btnConfPosp, true);
        ocultarError(errPosp);

        post('/someday/posponer', { id: idActivo, fecha_revision: fecha })
            .then(res => {
                setLoading(btnConfPosp, false);
                if (res.ok) {
                    bootstrap.Modal.getInstance(modalPospEl)?.hide();
                    // Actualiza el data-fecha del botón en el DOM
                    const itemEl = lista.querySelector(`.someday-item[data-id="${idActivo}"]`);
                    if (itemEl) {
                        const btn = itemEl.querySelector('.btn-posponer');
                        if (btn) btn.dataset.fecha = fecha;

                        // Quita el badge de revisión si la nueva fecha es futura
                        const hoy = new Date().toISOString().slice(0, 10);
                        itemEl.classList.toggle('item-revisar', fecha <= hoy);

                        // Actualiza el tag de fecha visible
                        const tagFecha = itemEl.querySelector('.tag-date, .tag-alert');
                        if (tagFecha) {
                            const d = new Date(fecha + 'T00:00:00');
                            const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                            tagFecha.textContent = d.getDate() + ' ' + meses[d.getMonth()];
                            tagFecha.className = 'tag ' + (fecha <= hoy ? 'tag-alert' : 'tag-date');
                        }

                        // Elimina o agrega el badge "Revisar hoy"
                        const tagRevisar = itemEl.querySelector('.tag-revisar');
                        if (fecha <= hoy && !tagRevisar) {
                            const wrap = itemEl.querySelector('.d-flex.flex-wrap');
                            if (wrap) {
                                const span = document.createElement('span');
                                span.className = 'tag tag-revisar fw-bold';
                                span.textContent = 'Revisar hoy';
                                wrap.appendChild(span);
                            }
                        } else if (fecha > hoy && tagRevisar) {
                            tagRevisar.remove();
                        }

                        actualizarContador();
                    }
                } else {
                    mostrarError(errPosp, res.error ?? 'Error al posponer.');
                }
            })
            .catch(() => {
                setLoading(btnConfPosp, false);
                mostrarError(errPosp, 'Error de red.');
            });
    });

    // ─── Confirmar eliminar ──────────────────────────────────────────────────

    btnConfElim?.addEventListener('click', () => {
        setLoading(btnConfElim, true);
        ocultarError(errElim);

        post('/someday/eliminar', { id: idActivo })
            .then(res => {
                setLoading(btnConfElim, false);
                if (res.ok) {
                    bootstrap.Modal.getInstance(modalElimEl)?.hide();
                    eliminarItemDOM(idActivo);
                } else {
                    mostrarError(errElim, res.error ?? 'Error al eliminar.');
                }
            })
            .catch(() => {
                setLoading(btnConfElim, false);
                mostrarError(errElim, 'Error de red.');
            });
    });

    // ─── Limpiar modales al cerrar ────────────────────────────────────────────

    modalPospEl?.addEventListener('hidden.bs.modal', () => {
        ocultarError(errPosp);
        idActivo = null;
    });

    modalElimEl?.addEventListener('hidden.bs.modal', () => {
        ocultarError(errElim);
        idActivo = null;
    });

    // ─── Init ─────────────────────────────────────────────────────────────────

    actualizarContador();

})();

// ── Notas inline ─────────────────────────────────────────
(function () {
    'use strict';
    var lista = document.getElementById('someday-lista');
    if (!lista) return;
    var debNotas = {};

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-notas');
        if (!btn) return;
        var wrapper   = btn.closest('.notas-wrapper');
        var expandida = wrapper ? wrapper.querySelector('.notas-expandida') : null;
        if (!expandida) return;
        btn.classList.add('d-none');
        expandida.classList.remove('d-none');
        var ta = expandida.querySelector('.notas-inline');
        if (ta) ta.focus();
    });

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
                    setTimeout(function () { guardadoEl.classList.add('d-none'); }, 2000);
                }
            } catch (_) { /* silencioso */ }
        }, 800);
    });
}());
