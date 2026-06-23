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

    document.getElementById('filtro-proyecto')?.addEventListener('change', (e) => {
        projActivo = e.target.value;
        aplicarFiltros();
    });

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

})();
