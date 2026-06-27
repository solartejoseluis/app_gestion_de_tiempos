(() => {
    const lista   = document.getElementById('espera-lista');
    const counter = document.getElementById('espera-counter');
    const warn    = document.getElementById('espera-warn');
    const empty   = document.getElementById('espera-empty');
    const hoy     = new Date().toISOString().slice(0, 10);

    // ── Estado de filtros ─────────────────────────────────────
    let personaActiva  = '';
    let areaActiva     = '';
    let posponerItemId = null;

    // ── Helpers ───────────────────────────────────────────────
    function getItems() {
        return lista.querySelectorAll('.espera-item');
    }

    function actualizarContador() {
        const visibles  = [...getItems()].filter(el => !el.classList.contains('d-none'));
        const n         = visibles.length;
        const vencidos  = visibles.filter(el => el.classList.contains('item-vencida')).length;

        counter.textContent = n;
        empty.classList.toggle('d-none', n > 0);

        if (warn) {
            warn.textContent = vencidos + ' vencido' + (vencidos !== 1 ? 's' : '');
            warn.classList.toggle('d-none', vencidos === 0);
        }

        const sidebarBadge = document.getElementById('sidebar-espera-badge');
        if (sidebarBadge) {
            sidebarBadge.textContent = n;
            sidebarBadge.classList.toggle('d-none', n === 0);
        }
    }

    function aplicarFiltros() {
        getItems().forEach(item => {
            const persona    = item.dataset.personaId;
            const area       = item.dataset.areaId;
            const passPersona = !personaActiva || persona === personaActiva;
            const passArea    = !areaActiva    || area    === areaActiva;
            item.classList.toggle('d-none', !(passPersona && passArea));
        });
        actualizarContador();
    }

    // ── Filtros ───────────────────────────────────────────────
    document.getElementById('filtro-persona')?.addEventListener('change', (e) => {
        personaActiva = e.target.value;
        aplicarFiltros();
    });

    document.getElementById('filtro-area')?.addEventListener('change', (e) => {
        areaActiva = e.target.value;
        aplicarFiltros();
    });

    document.getElementById('btn-limpiar-filtros')?.addEventListener('click', () => {
        personaActiva = '';
        areaActiva    = '';
        const selPersona = document.getElementById('filtro-persona');
        const selArea    = document.getElementById('filtro-area');
        if (selPersona) selPersona.value = '';
        if (selArea)    selArea.value    = '';
        aplicarFiltros();
    });

    // ── Recibir ───────────────────────────────────────────────
    async function recibirItem(id, btnEl) {
        const orig     = btnEl.innerHTML;
        btnEl.disabled = true;
        btnEl.textContent = '...';

        try {
            const res  = await fetch('/espera/recibido', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({ id }),
            });
            const data = await res.json();
            if (data.ok) {
                lista.querySelector(`.espera-item[data-id="${id}"]`)?.remove();
                actualizarContador();
            } else {
                btnEl.disabled = false;
                btnEl.innerHTML = orig;
            }
        } catch {
            btnEl.disabled = false;
            btnEl.innerHTML = orig;
        }
    }

    // ── Posponer — modal ──────────────────────────────────────
    const modalEl = document.getElementById('modalPosponer');

    const manana = (() => {
        const d = new Date();
        d.setDate(d.getDate() + 1);
        return d.toISOString().slice(0, 10);
    })();

    const inputFecha = document.getElementById('posponer-fecha');
    if (inputFecha) inputFecha.min = manana;

    // Limpiar error al cerrar modal
    modalEl?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('posponer-error')?.classList.add('d-none');
        posponerItemId = null;
    });

    // ── Delegación de clicks ──────────────────────────────────
    lista.addEventListener('click', (e) => {
        const btnRecibido = e.target.closest('.btn-recibido');
        const btnPosponer = e.target.closest('.btn-posponer');

        if (btnRecibido) {
            const id = btnRecibido.dataset.itemId;
            if (id) recibirItem(id, btnRecibido);
        } else if (btnPosponer) {
            posponerItemId = btnPosponer.dataset.itemId;
            const fecha    = btnPosponer.dataset.fecha;
            if (inputFecha) {
                inputFecha.value = fecha && fecha > hoy ? fecha : manana;
            }
            document.getElementById('posponer-error')?.classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    // ── Confirmar posponer ────────────────────────────────────
    document.getElementById('btn-confirmar-posponer')?.addEventListener('click', async function () {
        const fecha   = inputFecha?.value ?? '';
        const errorEl = document.getElementById('posponer-error');

        if (!fecha) {
            errorEl.textContent = 'La fecha es obligatoria.';
            errorEl.classList.remove('d-none');
            return;
        }

        errorEl.classList.add('d-none');
        this.disabled    = true;
        const orig       = this.textContent.trim();
        this.textContent = 'Guardando...';

        try {
            const res  = await fetch('/espera/posponer', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({ id: posponerItemId, fecha_accion: fecha }),
            });
            const data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                actualizarItemPospuesto(posponerItemId, fecha);
            } else {
                errorEl.textContent = data.error ?? 'Error al posponer.';
                errorEl.classList.remove('d-none');
            }
        } catch {
            errorEl.textContent = 'Error de conexión. Inténtalo de nuevo.';
            errorEl.classList.remove('d-none');
        } finally {
            this.disabled    = false;
            this.textContent = orig;
        }
    });

    // ── Actualizar ítem pospuesto en el DOM ───────────────────
    function actualizarItemPospuesto(id, fechaISO) {
        const card = lista.querySelector(`.espera-item[data-id="${id}"]`);
        if (!card) return;

        const ahoraVencida = fechaISO < hoy;

        // Actualizar data y clase
        card.classList.toggle('item-vencida', ahoraVencida);
        const btnPosponer = card.querySelector('.btn-posponer');
        if (btnPosponer) btnPosponer.dataset.fecha = fechaISO;

        // Reconstruir tags de fecha y vencido
        const tagsDiv = card.querySelector('.d-flex.flex-wrap.gap-1');
        if (tagsDiv) {
            tagsDiv.querySelectorAll('.tag-date, .tag-alert').forEach(t => t.remove());

            const meses    = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            const dt       = new Date(fechaISO + 'T12:00:00');
            const fechaStr = dt.getDate() + ' ' + meses[dt.getMonth()];

            const dateTag = document.createElement('span');
            dateTag.className = 'tag ' + (ahoraVencida ? 'tag-alert' : 'tag-date');
            dateTag.textContent = fechaStr;
            tagsDiv.appendChild(dateTag);

            if (ahoraVencida) {
                const alertTag = document.createElement('span');
                alertTag.className = 'tag tag-alert fw-bold';
                alertTag.textContent = 'Vencido';
                tagsDiv.appendChild(alertTag);
            }
        }

        actualizarContador();
    }

})();

// ── Notas inline ─────────────────────────────────────────
(function () {
    'use strict';
    var lista = document.getElementById('espera-lista');
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
