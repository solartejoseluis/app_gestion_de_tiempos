(() => {
    const list      = document.getElementById('inbox-list');
    const emptyEl   = document.getElementById('empty-state');
    const counterEl = document.getElementById('inbox-counter');
    const form      = document.getElementById('capture-form');
    const input     = document.getElementById('capture-input');
    const modalEl   = document.getElementById('modalBorrar');
    const btnBorrar = document.getElementById('btn-confirmar-borrar');

    let deleteId = null;

    // ── Contador ────────────────────────────────────────────
    function actualizarContador() {
        const n = list.querySelectorAll('.item').length;
        counterEl.textContent = n;
        counterEl.nextSibling.textContent = n === 1 ? ' pendiente' : ' pendientes';
        emptyEl.classList.toggle('d-none', n > 0);
    }

    // ── Crear elemento DOM a partir de un ítem ─────────────
    function crearElemento(item) {
        const div = document.createElement('div');
        div.className = 'item';
        div.dataset.id = item.id;
        div.innerHTML = `
            <div class="item-body">
                <div class="item-text">${escHtml(item.titulo)}</div>
                <div class="item-date">${formatFecha(item.created_at)}</div>
            </div>
            <div class="item-actions">
                <button class="btn btn-sm btn-process">Procesar</button>
                <button class="btn btn-sm btn-del"
                        data-item-id="${item.id}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalBorrar">Borrar</button>
            </div>`;
        return div;
    }

    // ── Captura ─────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const texto = input.value.trim();
        if (!texto) return;

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        try {
            const res  = await fetch('/inbox/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ texto }),
            });
            const data = await res.json();
            if (data.ok) {
                const el = crearElemento(data.data);
                emptyEl.insertAdjacentElement('afterend', el);
                input.value = '';
                actualizarContador();
            }
        } finally {
            btn.disabled = false;
            input.focus();
        }
    });

    // ── Borrar: capturar ID al abrir el modal ───────────────
    modalEl.addEventListener('show.bs.modal', (e) => {
        deleteId = e.relatedTarget?.dataset.itemId ?? null;
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        deleteId = null;
    });

    btnBorrar.addEventListener('click', async () => {
        if (!deleteId) return;
        btnBorrar.disabled = true;

        try {
            const res  = await fetch('/inbox/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: deleteId }),
            });
            const data = await res.json();
            if (data.ok) {
                list.querySelector(`.item[data-id="${deleteId}"]`)?.remove();
                actualizarContador();
                bootstrap.Modal.getInstance(modalEl).hide();
            }
        } finally {
            btnBorrar.disabled = false;
        }
    });

    // ── Utilidades ──────────────────────────────────────────
    function escHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    function formatFecha(ts) {
        const d = new Date(ts.replace(' ', 'T'));
        return `${d.getDate()} ${MESES[d.getMonth()]} ${d.getFullYear()}`;
    }
})();
