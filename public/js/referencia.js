(function () {
    'use strict';

    const lista          = document.getElementById('ref-lista');
    const counter        = document.getElementById('ref-counter');
    const filtroArea     = document.getElementById('filtro-area');
    const filtroProyecto = document.getElementById('filtro-proyecto');
    const filtroTexto    = document.getElementById('filtro-texto');
    const btnLimpiar     = document.getElementById('btn-limpiar-filtros');

    // Modales — sólo elementos del DOM, sin instanciar bootstrap.Modal aquí
    const modalEtiqEl   = document.getElementById('modalRefEtiquetas');
    const modalElimEl   = document.getElementById('modalRefEliminar');
    const etiqInput     = document.getElementById('ref-etiquetas-input');
    const errEtiq       = document.getElementById('ref-etiquetas-error');
    const errElim       = document.getElementById('ref-eliminar-error');
    const btnGuardarEtiq = document.getElementById('btn-ref-guardar-etiquetas');
    const btnConfElim    = document.getElementById('btn-ref-confirmar-eliminar');

    let idActivo = null;

    // Estado de filtros
    let areaActiva    = '';
    let proyActivo    = '';
    let textoActivo   = '';

    // ─── Filtrado ────────────────────────────────────────────────────────────

    function getItems() {
        return lista.querySelectorAll('.referencia-item');
    }

    function actualizarContador() {
        const visibles = [...getItems()].filter(i => !i.classList.contains('d-none'));
        counter.textContent = visibles.length;

        const empty = document.getElementById('ref-empty');
        if (empty) empty.classList.toggle('d-none', visibles.length > 0);
    }

    function aplicarFiltros() {
        getItems().forEach(item => {
            const passArea     = !areaActiva  || item.dataset.areaId     === areaActiva;
            const passProy     = !proyActivo  || item.dataset.proyectoId === proyActivo;
            const titulo       = item.querySelector('.item-text')?.textContent.toLowerCase() ?? '';
            const etiquetas    = item.dataset.etiquetas ?? '';
            const passTexto    = !textoActivo || titulo.includes(textoActivo) || etiquetas.includes(textoActivo);
            item.classList.toggle('d-none', !(passArea && passProy && passTexto));
        });
        actualizarContador();
    }

    filtroArea?.addEventListener('change', () => {
        areaActiva = filtroArea.value;
        aplicarFiltros();
    });

    filtroProyecto?.addEventListener('change', () => {
        proyActivo = filtroProyecto.value;
        aplicarFiltros();
    });

    filtroTexto?.addEventListener('input', () => {
        textoActivo = filtroTexto.value.toLowerCase().trim();
        aplicarFiltros();
    });

    btnLimpiar?.addEventListener('click', () => {
        areaActiva  = '';
        proyActivo  = '';
        textoActivo = '';
        if (filtroArea)     filtroArea.value     = '';
        if (filtroProyecto) filtroProyecto.value = '';
        if (filtroTexto)    filtroTexto.value    = '';
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
        const el = lista.querySelector(`.referencia-item[data-id="${id}"]`);
        if (el) el.remove();
        actualizarContador();
    }

    function renderizarChips(etiquetasStr) {
        if (!etiquetasStr) return '';
        return etiquetasStr.split(',')
            .map(e => e.trim())
            .filter(Boolean)
            .map(e => `<span class="tag tag-etiqueta">${escHtml(e)}</span>`)
            .join('');
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ─── Delegación de eventos sobre la lista ────────────────────────────────

    lista.addEventListener('click', (e) => {

        // Botón Editar etiquetas
        const btnEtiq = e.target.closest('.btn-etiquetas');
        if (btnEtiq) {
            idActivo = btnEtiq.dataset.itemId;
            if (etiqInput) etiqInput.value = btnEtiq.dataset.etiquetas ?? '';
            ocultarError(errEtiq);
            bootstrap.Modal.getOrCreateInstance(modalEtiqEl).show();
            return;
        }

        // Botón Activar
        const btnActivar = e.target.closest('.btn-activar');
        if (btnActivar) {
            const id = btnActivar.dataset.itemId;
            setLoading(btnActivar, true);
            post('/referencia/activar', { id })
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

        // Botón Eliminar
        const btnElim = e.target.closest('.btn-eliminar');
        if (btnElim) {
            idActivo = btnElim.dataset.itemId;
            ocultarError(errElim);
            bootstrap.Modal.getOrCreateInstance(modalElimEl).show();
            return;
        }
    });

    // ─── Guardar etiquetas ───────────────────────────────────────────────────

    btnGuardarEtiq?.addEventListener('click', () => {
        const etiquetas = etiqInput?.value ?? '';
        ocultarError(errEtiq);
        setLoading(btnGuardarEtiq, true);

        post('/referencia/editar-etiquetas', { id: idActivo, etiquetas })
            .then(res => {
                setLoading(btnGuardarEtiq, false);
                if (res.ok) {
                    bootstrap.Modal.getInstance(modalEtiqEl)?.hide();

                    const itemEl = lista.querySelector(`.referencia-item[data-id="${idActivo}"]`);
                    if (itemEl) {
                        const nuevas = res.data?.etiquetas ?? '';
                        // Actualiza data-etiquetas para el filtro de texto
                        itemEl.dataset.etiquetas = nuevas.toLowerCase();

                        // Actualiza data-etiquetas en el botón para el próximo modal
                        const btnEtiq = itemEl.querySelector('.btn-etiquetas');
                        if (btnEtiq) btnEtiq.dataset.etiquetas = nuevas;

                        // Reconstruye las chips de etiquetas conservando los otros tags
                        const wrap = itemEl.querySelector('.item-tags');
                        if (wrap) {
                            // Quita chips de etiqueta actuales
                            wrap.querySelectorAll('.tag-etiqueta').forEach(t => t.remove());
                            // Inserta las nuevas
                            if (nuevas) {
                                wrap.insertAdjacentHTML('beforeend', renderizarChips(nuevas));
                            }
                        }
                    }
                } else {
                    mostrarError(errEtiq, res.error ?? 'Error al guardar etiquetas.');
                }
            })
            .catch(() => {
                setLoading(btnGuardarEtiq, false);
                mostrarError(errEtiq, 'Error de red.');
            });
    });

    // ─── Confirmar eliminar ──────────────────────────────────────────────────

    btnConfElim?.addEventListener('click', () => {
        setLoading(btnConfElim, true);
        ocultarError(errElim);

        post('/referencia/eliminar', { id: idActivo })
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

    modalEtiqEl?.addEventListener('hidden.bs.modal', () => {
        ocultarError(errEtiq);
        idActivo = null;
    });

    modalElimEl?.addEventListener('hidden.bs.modal', () => {
        ocultarError(errElim);
        idActivo = null;
    });

    // ─── Init ─────────────────────────────────────────────────────────────────

    actualizarContador();

})();
