(() => {
    const lista   = document.getElementById('espera-lista');
    const counter = document.getElementById('espera-counter');
    const warn    = document.getElementById('espera-warn');
    const empty   = document.getElementById('espera-empty');

    // ── Estado de filtros ─────────────────────────────────────
    let personaActiva  = '';
    let areaActiva     = '';

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

    // ── Delegación de clicks ──────────────────────────────────
    lista.addEventListener('click', (e) => {
        const btnRecibido = e.target.closest('.btn-recibido');
        if (btnRecibido) {
            const id = btnRecibido.dataset.itemId;
            if (id) recibirItem(id, btnRecibido);
        }
    });

    // ── Editar ítem (modal unificado) ──────────────────────────
    lista.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit');
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

    document.addEventListener('accion:editada', (e) => {
        const d    = e.detail;
        const fila = lista.querySelector(`.espera-item[data-id="${d.id}"]`);
        if (!fila) return;
        const textoEl = fila.querySelector('.item-text');
        if (textoEl) textoEl.textContent = d.titulo;
        const btnEdit = fila.querySelector('.btn-edit');
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

    document.addEventListener('accion:eliminada', (e) => {
        const fila = lista.querySelector(`.espera-item[data-id="${e.detail.id}"]`);
        if (fila) fila.remove();
        actualizarContador();
    });

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
