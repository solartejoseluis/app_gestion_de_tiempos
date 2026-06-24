(() => {
    const counter = document.getElementById('proyectos-counter');

    // ── Helpers ───────────────────────────────────────────────
    async function postProyecto(endpoint, id) {
        const res  = await fetch(endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams({ id }),
        });
        return res.json();
    }

    function actualizarCounter() {
        const activos = document.querySelectorAll('.proyecto-card:not(.proyecto-pausado)').length;
        if (counter) counter.textContent = activos;
    }

    // ── Completar proyecto ────────────────────────────────────
    async function completarProyecto(id, btn) {
        const orig    = btn.textContent.trim();
        btn.disabled  = true;
        btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>...';

        try {
            const data = await postProyecto('/proyectos/completar', id);
            if (data.ok) {
                const card = document.querySelector(`.proyecto-card[data-id="${id}"]`);
                card?.remove();
                actualizarCounter();
            } else {
                btn.disabled  = false;
                btn.innerHTML = orig;
            }
        } catch {
            btn.disabled  = false;
            btn.innerHTML = orig;
        }
    }

    // ── Pausar proyecto ───────────────────────────────────────
    async function pausarProyecto(id, btn) {
        btn.disabled = true;

        try {
            const data = await postProyecto('/proyectos/pausar', id);
            if (data.ok) {
                const card = document.querySelector(`.proyecto-card[data-id="${id}"]`);
                if (card) {
                    card.classList.add('proyecto-pausado');
                    card.dataset.estado = 'pausa';
                    card.querySelector('.proyecto-pausa-badge')?.classList.remove('d-none');
                    card.querySelector('.btn-pausar-proyecto')?.classList.add('d-none');
                    card.querySelector('.btn-reactivar-proyecto')?.classList.remove('d-none');
                }
                actualizarCounter();
            }
        } finally {
            btn.disabled = false;
        }
    }

    // ── Reactivar proyecto ────────────────────────────────────
    async function reactivarProyecto(id, btn) {
        btn.disabled = true;

        try {
            const data = await postProyecto('/proyectos/reactivar', id);
            if (data.ok) {
                const card = document.querySelector(`.proyecto-card[data-id="${id}"]`);
                if (card) {
                    card.classList.remove('proyecto-pausado');
                    card.dataset.estado = 'activo';
                    card.querySelector('.proyecto-pausa-badge')?.classList.add('d-none');
                    card.querySelector('.btn-reactivar-proyecto')?.classList.add('d-none');
                    card.querySelector('.btn-pausar-proyecto')?.classList.remove('d-none');
                }
                actualizarCounter();
            }
        } finally {
            btn.disabled = false;
        }
    }

    // ── Delegación de clicks ──────────────────────────────────
    document.querySelector('.proyectos-lista')?.addEventListener('click', (e) => {
        const btnCompletar  = e.target.closest('.btn-completar-proyecto');
        const btnPausar     = e.target.closest('.btn-pausar-proyecto');
        const btnReactivar  = e.target.closest('.btn-reactivar-proyecto');

        if (btnCompletar) {
            const id = btnCompletar.dataset.itemId;
            if (id) completarProyecto(id, btnCompletar);
        } else if (btnPausar) {
            const id = btnPausar.dataset.itemId;
            if (id) pausarProyecto(id, btnPausar);
        } else if (btnReactivar) {
            const id = btnReactivar.dataset.itemId;
            if (id) reactivarProyecto(id, btnReactivar);
        }
    });

    // ── Chevron collapse ──────────────────────────────────────
    document.querySelectorAll('.proyecto-area-header').forEach(header => {
        const targetId = header.dataset.bsTarget;
        const target   = document.querySelector(targetId);
        if (!target) return;

        target.addEventListener('hide.bs.collapse', () => {
            header.classList.add('collapsed');
        });
        target.addEventListener('show.bs.collapse', () => {
            header.classList.remove('collapsed');
        });
    });

})();
