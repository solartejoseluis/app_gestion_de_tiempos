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
    let editItemId    = null;
    const modalEditEl = document.getElementById('modalEditarAccion');

    lista.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit');
        if (!btn) return;

        editItemId = btn.dataset.itemId;

        const elTitulo   = document.getElementById('edit-titulo');
        const elContexto = document.getElementById('edit-contexto');
        const elFecha    = document.getElementById('edit-fecha');
        const elError    = document.getElementById('edit-error');

        if (elTitulo)   elTitulo.value   = btn.dataset.titulo      ?? '';
        if (elContexto) elContexto.value = btn.dataset.contextoId  ?? '';
        if (elFecha)    elFecha.value    = btn.dataset.fechaAccion  ?? '';
        if (elError)    elError.classList.add('d-none');

        bootstrap.Modal.getOrCreateInstance(modalEditEl).show();
    });

    document.getElementById('btn-guardar-editar')?.addEventListener('click', async () => {
        const btnEl  = document.getElementById('btn-guardar-editar');
        const titulo = document.getElementById('edit-titulo')?.value.trim() ?? '';

        if (!titulo) {
            const elErr = document.getElementById('edit-error');
            if (elErr) { elErr.textContent = 'El título es obligatorio.'; elErr.classList.remove('d-none'); }
            return;
        }

        const contextoId = document.getElementById('edit-contexto')?.value ?? '';
        const fecha      = document.getElementById('edit-fecha')?.value     ?? '';
        const textoOrig  = btnEl.textContent.trim();
        btnEl.disabled    = true;
        btnEl.textContent = 'Guardando...';

        try {
            const res = await fetch(`/acciones/${editItemId}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({
                    titulo,
                    contexto_id:  contextoId,
                    fecha_accion: fecha,
                    _method:      'PATCH',
                }),
            });
            const data = await res.json();

            if (data.ok) {
                // Actualizar el DOM antes de cerrar el modal para evitar que
                // una excepción en hide() bloquee la actualización
                const fila = lista.querySelector(`.acciones-item[data-id="${editItemId}"]`);
                if (fila) {
                    const textoEl = fila.querySelector('.item-text');
                    if (textoEl) textoEl.textContent = titulo;

                    // Sincronizar data attributes del botón para la próxima edición
                    const btnEdit = fila.querySelector('.btn-edit');
                    if (btnEdit) {
                        btnEdit.dataset.titulo      = titulo;
                        btnEdit.dataset.contextoId  = contextoId;
                        btnEdit.dataset.fechaAccion = fecha;
                    }
                }

                btnEl.disabled    = false;
                btnEl.textContent = textoOrig;
                bootstrap.Modal.getOrCreateInstance(modalEditEl).hide();
            } else {
                const elErr = document.getElementById('edit-error');
                if (elErr) { elErr.textContent = data.error ?? 'Error al guardar.'; elErr.classList.remove('d-none'); }
                btnEl.disabled    = false;
                btnEl.textContent = textoOrig;
            }
        } catch {
            const elErr = document.getElementById('edit-error');
            if (elErr) { elErr.textContent = 'Error de conexión.'; elErr.classList.remove('d-none'); }
            btnEl.disabled    = false;
            btnEl.textContent = textoOrig;
        }
    });

})();
