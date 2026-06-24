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
        const activos = document.querySelectorAll(
            '.proyecto-card:not(.proyecto-pausado):not(.proyecto-card-completada)'
        ).length;
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

    // ── Modal: Nuevo proyecto ─────────────────────────────────
    const modalNP = document.getElementById('modalNuevoProyecto');

    if (modalNP) {
        modalNP.addEventListener('hidden.bs.modal', () => {
            document.getElementById('np-nombre').value = '';
            document.getElementById('np-area').selectedIndex = 0;
            document.getElementById('np-resultado').value = '';
            document.getElementById('np-error').classList.add('d-none');
        });
    }

    document.getElementById('btn-crear-proyecto')?.addEventListener('click', async function () {
        const nombre    = document.getElementById('np-nombre').value.trim();
        const area_id   = document.getElementById('np-area').value;
        const resultado = document.getElementById('np-resultado').value.trim();
        const errorEl   = document.getElementById('np-error');

        if (!nombre) {
            errorEl.textContent = 'El nombre es obligatorio.';
            errorEl.classList.remove('d-none');
            return;
        }

        errorEl.classList.add('d-none');
        this.disabled    = true;
        const orig       = this.innerHTML;
        this.textContent = 'Creando...';

        try {
            const res  = await fetch('/proyectos/crear', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({ nombre, area_id, resultado_deseado: resultado }),
            });
            const data = await res.json();

            if (data.ok) {
                bootstrap.Modal.getInstance(modalNP).hide();
                window.location.reload();
            } else {
                errorEl.textContent = data.error ?? 'Error al crear el proyecto.';
                errorEl.classList.remove('d-none');
                this.disabled  = false;
                this.innerHTML = orig;
            }
        } catch {
            errorEl.textContent = 'Error de conexión. Inténtalo de nuevo.';
            errorEl.classList.remove('d-none');
            this.disabled  = false;
            this.innerHTML = orig;
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
