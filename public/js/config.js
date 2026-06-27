(function () {
    'use strict';

    // ── Referencias ───────────────────────────────────────────
    const areasTable  = document.getElementById('areas-table');
    const areasEmpty  = document.getElementById('areas-empty');
    const formCrear   = document.getElementById('form-crear-area');
    const autosaveEl  = document.getElementById('autosave-indicator');
    const avisoLimite = document.getElementById('aviso-limite-areas');

    // ── Autosave indicator ────────────────────────────────────
    let autosaveTimer = null;

    function mostrarAutosave() {
        if (!autosaveEl) return;
        const d = new Date();
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        autosaveEl.textContent   = 'Guardado · ' + h + ':' + m;
        autosaveEl.style.opacity    = '1';
        autosaveEl.style.transition = 'none';
        autosaveEl.style.display    = '';

        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(function () {
            autosaveEl.style.transition = 'opacity .5s';
            autosaveEl.style.opacity    = '0';
            setTimeout(function () { autosaveEl.style.display = 'none'; }, 500);
        }, 3000);
    }

    // ── Error inline bajo un input ────────────────────────────
    function errorInline(campo, msg) {
        var err = campo.parentElement.querySelector('.campo-error');
        if (!err) {
            err = document.createElement('span');
            err.className = 'campo-error text-danger small d-block mt-1';
            campo.insertAdjacentElement('afterend', err);
        }
        err.textContent = msg;
    }

    function limpiarErrorInline(campo) {
        var err = campo.parentElement.querySelector('.campo-error');
        if (err) err.remove();
    }

    // ── Contador de áreas activas ─────────────────────────────
    function contarActivas() {
        if (!areasTable) return 0;
        return areasTable.querySelectorAll('tbody tr[data-estado="activo"]').length;
    }

    function verificarLimite() {
        if (!avisoLimite) return;
        avisoLimite.classList.toggle('d-none', contarActivas() <= 10);
    }

    // ── Toast de error ────────────────────────────────────────
    function mostrarToast(msg) {
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id        = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1100';
            document.body.appendChild(container);
        }
        var id = 'toast-' + Date.now();
        container.insertAdjacentHTML('beforeend',
            '<div id="' + id + '" class="toast align-items-center text-bg-danger border-0" role="alert">' +
                '<div class="d-flex">' +
                    '<div class="toast-body">' + escHTML(msg) + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                '</div>' +
            '</div>'
        );
        var toastEl = document.getElementById(id);
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }

    // ── Modal de confirmación genérico ────────────────────────
    function getOrCreateModalConfirm() {
        var el = document.getElementById('modal-confirmacion');
        if (el) return el;

        document.body.insertAdjacentHTML('beforeend',
            '<div class="modal fade" id="modal-confirmacion" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog modal-sm">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header border-0 pb-1">' +
                            '<h6 class="modal-title fw-semibold" id="modal-confirm-titulo"></h6>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                        '</div>' +
                        '<div class="modal-body small" id="modal-confirm-cuerpo"></div>' +
                        '<div id="modal-confirm-extra" class="px-3 pb-2 text-danger small d-none"></div>' +
                        '<div class="modal-footer border-0 pt-0">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary"' +
                                    ' data-bs-dismiss="modal">Cancelar</button>' +
                            '<button type="button" class="btn btn-sm btn-danger"' +
                                    ' id="modal-confirm-ok">Confirmar</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        return document.getElementById('modal-confirmacion');
    }

    function confirmar(titulo, msg, callback) {
        var modalEl = getOrCreateModalConfirm();
        document.getElementById('modal-confirm-titulo').textContent = titulo;
        document.getElementById('modal-confirm-cuerpo').textContent = msg;

        var extra = document.getElementById('modal-confirm-extra');
        extra.textContent = '';
        extra.classList.add('d-none');

        // Clonar para limpiar listeners anteriores
        var okBtn   = document.getElementById('modal-confirm-ok');
        var nuevoOk = okBtn.cloneNode(true);
        okBtn.replaceWith(nuevoOk);

        nuevoOk.addEventListener('click', function () {
            callback(modalEl);
        });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        return modalEl;
    }

    function mostrarExtraEnModal(msg) {
        var extra = document.getElementById('modal-confirm-extra');
        if (!extra) return;
        extra.textContent = msg;
        extra.classList.remove('d-none');
    }

    // ── HTML helpers ──────────────────────────────────────────
    function escHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function btnsActivo(id) {
        return '' +
            '<button type="button" class="btn btn-sm btn-outline-secondary btn-editar-area"' +
                    ' data-id="' + id + '" title="Editar nombre">' +
                '<i class="bi bi-pencil"></i>' +
            '</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-warning btn-archivar-area"' +
                    ' data-id="' + id + '" title="Archivar">' +
                '<i class="bi bi-archive"></i>' +
            '</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-area"' +
                    ' data-id="' + id + '" title="Eliminar">' +
                '<i class="bi bi-trash"></i>' +
            '</button>';
    }

    function btnsArchivado(id) {
        return '' +
            '<button type="button" class="btn btn-sm btn-outline-success btn-restaurar-area"' +
                    ' data-id="' + id + '" title="Restaurar">' +
                '<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar' +
            '</button>';
    }

    function buildFila(area) {
        var tr          = document.createElement('tr');
        tr.dataset.id     = area.id;
        tr.dataset.estado = 'activo';
        tr.innerHTML =
            '<td>' +
                '<input type="color"' +
                       ' class="area-color form-control form-control-color"' +
                       ' data-id="' + area.id + '"' +
                       ' value="' + escHTML(area.color) + '"' +
                       ' style="width:40px;height:28px;padding:2px;cursor:pointer;"' +
                       ' title="Cambiar color">' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                       ' class="area-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"' +
                       ' data-id="' + area.id + '"' +
                       ' value="' + escHTML(area.nombre) + '"' +
                       ' maxlength="100">' +
            '</td>' +
            '<td class="text-center">' +
                '<span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>' +
            '</td>' +
            '<td class="text-center">' +
                '<span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>' +
            '</td>' +
            '<td class="text-center td-estado">' +
                '<span class="badge bg-success">Activa</span>' +
            '</td>' +
            '<td class="td-acciones">' + btnsActivo(area.id) + '</td>';
        return tr;
    }

    // ── PATCH helper ──────────────────────────────────────────
    async function patchArea(id, campos) {
        var res = await fetch('/config/areas/' + id, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(campos),
        });
        return res.json();
    }

    // ── Autoguardado — nombre (focusout bubbles, blur no) ─────
    var debounceTimers = {};

    if (areasTable) {
        areasTable.addEventListener('focusout', function (e) {
            var input = e.target.closest('.area-nombre');
            if (!input) return;
            var id    = input.dataset.id;
            var valor = input.value.trim();

            clearTimeout(debounceTimers[id + '-nombre']);
            debounceTimers[id + '-nombre'] = setTimeout(async function () {
                limpiarErrorInline(input);
                try {
                    var data = await patchArea(id, { nombre: valor });
                    if (data.ok) {
                        mostrarAutosave();
                    } else {
                        errorInline(input, data.error || 'Error al guardar.');
                    }
                } catch (_) {
                    errorInline(input, 'Error de conexión.');
                }
            }, 300);
        });

        // ── Autoguardado — color ──────────────────────────────
        areasTable.addEventListener('change', function (e) {
            var input = e.target.closest('.area-color');
            if (!input) return;
            var id    = input.dataset.id;
            var valor = input.value;

            clearTimeout(debounceTimers[id + '-color']);
            debounceTimers[id + '-color'] = setTimeout(async function () {
                try {
                    var data = await patchArea(id, { color: valor });
                    if (data.ok) {
                        mostrarAutosave();
                    } else {
                        mostrarToast(data.error || 'Error al guardar color.');
                    }
                } catch (_) {
                    mostrarToast('Error de conexión.');
                }
            }, 300);
        });

        // ── Delegación de clicks sobre la tabla ───────────────
        areasTable.addEventListener('click', function (e) {
            var btnArchivar  = e.target.closest('.btn-archivar-area');
            var btnRestaurar = e.target.closest('.btn-restaurar-area');
            var btnEliminar  = e.target.closest('.btn-eliminar-area');
            var btnEditar    = e.target.closest('.btn-editar-area');

            if (btnArchivar) {
                var idA = btnArchivar.dataset.id;
                confirmar(
                    'Archivar área',
                    '¿Archivar esta área? Dejará de aparecer en los selects pero su historial se conserva.',
                    function (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                        archivarArea(idA, btnArchivar);
                    }
                );

            } else if (btnRestaurar) {
                restaurarArea(btnRestaurar.dataset.id, btnRestaurar);

            } else if (btnEliminar && !btnEliminar.disabled) {
                var idE = btnEliminar.dataset.id;
                confirmar(
                    'Eliminar área',
                    '¿Eliminar esta área? Esta acción no se puede deshacer.',
                    function (modalEl) { eliminarArea(idE, btnEliminar, modalEl); }
                );

            } else if (btnEditar) {
                var row = areasTable.querySelector('tr[data-id="' + btnEditar.dataset.id + '"]');
                if (row) row.querySelector('.area-nombre')?.select();
            }
        });
    }

    // ── Crear área ────────────────────────────────────────────
    if (formCrear) {
        var inputNombreNueva = document.getElementById('area-nombre-nueva');
        var inputColorNueva  = document.getElementById('area-color-nueva');
        var errorCrear       = document.getElementById('area-crear-error');

        formCrear.addEventListener('submit', async function (e) {
            e.preventDefault();
            var nombre = inputNombreNueva.value.trim();
            var color  = inputColorNueva.value;

            if (!nombre) {
                if (errorCrear) {
                    errorCrear.textContent = 'El nombre es obligatorio.';
                    errorCrear.classList.remove('d-none');
                }
                return;
            }
            if (errorCrear) errorCrear.classList.add('d-none');

            var btn    = formCrear.querySelector('[type=submit]');
            btn.disabled = true;

            try {
                var res  = await fetch('/config/areas', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    new URLSearchParams({ nombre: nombre, color: color }),
                });
                var data = await res.json();

                if (data.ok) {
                    if (areasTable) {
                        areasTable.querySelector('tbody').insertAdjacentElement(
                            'afterbegin',
                            buildFila(data.data)
                        );
                        areasTable.classList.remove('d-none');
                    }
                    if (areasEmpty) areasEmpty.classList.add('d-none');
                    formCrear.reset();
                    inputColorNueva.value = '#4a90d9'; // restaurar por defecto tras reset
                    inputNombreNueva.focus();
                    verificarLimite();
                } else {
                    if (errorCrear) {
                        errorCrear.textContent = data.error || 'Error al crear el área.';
                        errorCrear.classList.remove('d-none');
                    }
                }
            } catch (_) {
                if (errorCrear) {
                    errorCrear.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    errorCrear.classList.remove('d-none');
                }
            } finally {
                btn.disabled = false;
            }
        });
    }

    // ── Archivar área ─────────────────────────────────────────
    async function archivarArea(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/areas/' + id + '/archivar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = areasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'archivado';
                    row.querySelector('.td-estado').innerHTML =
                        '<span class="badge bg-secondary">Archivada</span>';
                    row.querySelector('.td-acciones').innerHTML = btnsArchivado(id);
                }
                verificarLimite();
                // btn fue eliminado del DOM — no re-habilitar
            } else {
                mostrarToast(data.error || 'Error al archivar el área.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Restaurar área ────────────────────────────────────────
    async function restaurarArea(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/areas/' + id + '/restaurar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = areasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'activo';
                    row.querySelector('.td-estado').innerHTML =
                        '<span class="badge bg-success">Activa</span>';
                    row.querySelector('.td-acciones').innerHTML = btnsActivo(id);
                }
                verificarLimite();
                // btn fue eliminado del DOM — no re-habilitar
            } else {
                mostrarToast(data.error || 'Error al restaurar el área.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Eliminar área ─────────────────────────────────────────
    async function eliminarArea(id, btn, modalEl) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/areas/' + id, { method: 'DELETE' });
            var data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                var row = areasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(function () { row.remove(); }, 300);
                }
                verificarLimite();
            } else {
                var msgDeps = (data.proyectos !== undefined)
                    ? 'No se puede eliminar: tiene ' + data.proyectos + ' proyecto(s) y ' +
                      data.items + ' ítem(s) activo(s) vinculados.'
                    : (data.error || 'Error al eliminar.');
                mostrarExtraEnModal(msgDeps);
                btn.disabled = false;
            }
        } catch (_) {
            mostrarExtraEnModal('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // CONTEXTOS
    // ═══════════════════════════════════════════════════════════════

    const contextosTable = document.getElementById('contextos-table');
    const contextosEmpty = document.getElementById('contextos-empty');
    const formCrearCtx   = document.getElementById('form-crear-contexto');

    // ── PATCH helper ──────────────────────────────────────────────
    async function patchContexto(id, campos) {
        var res = await fetch('/config/contextos/' + id, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(campos),
        });
        return res.json();
    }

    // ── Botones de acción ─────────────────────────────────────────
    function btnsActivoCtx(id) {
        return '' +
            '<button type="button" class="btn btn-sm btn-outline-warning btn-archivar-contexto"' +
                    ' data-id="' + id + '" title="Archivar">' +
                '<i class="bi bi-archive"></i>' +
            '</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-contexto"' +
                    ' data-id="' + id + '" title="Eliminar">' +
                '<i class="bi bi-trash"></i>' +
            '</button>';
    }

    function btnsArchivadoCtx(id) {
        return '' +
            '<button type="button" class="btn btn-sm btn-outline-success btn-restaurar-contexto"' +
                    ' data-id="' + id + '" title="Restaurar">' +
                '<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar' +
            '</button>';
    }

    // ── Construir fila nueva ──────────────────────────────────────
    function buildFilaContexto(ctx) {
        var tr = document.createElement('tr');
        tr.dataset.id     = ctx.id;
        tr.dataset.estado = 'activo';
        tr.innerHTML =
            '<td>' +
                '<span class="fw-bold ctx-at" style="color:' + escHTML(ctx.color) + '">@</span>' +
                '<input type="color"' +
                       ' class="contexto-color form-control form-control-color ms-1"' +
                       ' data-id="' + ctx.id + '"' +
                       ' value="' + escHTML(ctx.color) + '"' +
                       ' style="width:32px;height:24px;padding:2px 4px;vertical-align:middle;"' +
                       ' title="Cambiar color">' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                       ' class="contexto-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"' +
                       ' data-id="' + ctx.id + '"' +
                       ' value="' + escHTML(ctx.nombre) + '"' +
                       ' maxlength="50">' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                       ' class="contexto-descripcion form-control form-control-sm border-0 bg-transparent p-0 text-muted"' +
                       ' data-id="' + ctx.id + '"' +
                       ' value="' + escHTML(ctx.descripcion || '') + '"' +
                       ' maxlength="150">' +
            '</td>' +
            '<td class="text-center">' +
                '<span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>' +
            '</td>' +
            '<td class="text-center td-estado-ctx">' +
                '<span class="badge bg-success">Activo</span>' +
            '</td>' +
            '<td class="td-acciones-ctx">' + btnsActivoCtx(ctx.id) + '</td>';
        return tr;
    }

    // ── Debounce timers para contextos ────────────────────────────
    var debounceCtxTimers = {};

    if (contextosTable) {
        // Autoguardado nombre y descripción
        contextosTable.addEventListener('focusout', function (e) {
            var inputNombre = e.target.closest('.contexto-nombre');
            var inputDesc   = e.target.closest('.contexto-descripcion');

            if (inputNombre) {
                var id    = inputNombre.dataset.id;
                var valor = inputNombre.value.trim();
                clearTimeout(debounceCtxTimers[id + '-nombre']);
                debounceCtxTimers[id + '-nombre'] = setTimeout(async function () {
                    limpiarErrorInline(inputNombre);
                    try {
                        var data = await patchContexto(id, { nombre: valor });
                        if (data.ok) {
                            mostrarAutosave();
                        } else {
                            errorInline(inputNombre, data.error || 'Error al guardar.');
                        }
                    } catch (_) {
                        errorInline(inputNombre, 'Error de conexión.');
                    }
                }, 300);
            }

            if (inputDesc) {
                var idD    = inputDesc.dataset.id;
                var valorD = inputDesc.value.trim();
                clearTimeout(debounceCtxTimers[idD + '-desc']);
                debounceCtxTimers[idD + '-desc'] = setTimeout(async function () {
                    try {
                        var data = await patchContexto(idD, { descripcion: valorD });
                        if (data.ok) {
                            mostrarAutosave();
                        } else {
                            mostrarToast(data.error || 'Error al guardar descripción.');
                        }
                    } catch (_) {
                        mostrarToast('Error de conexión.');
                    }
                }, 300);
            }
        });

        // Autoguardado color
        contextosTable.addEventListener('change', function (e) {
            var input = e.target.closest('.contexto-color');
            if (!input) return;
            var id    = input.dataset.id;
            var valor = input.value;
            clearTimeout(debounceCtxTimers[id + '-color']);
            debounceCtxTimers[id + '-color'] = setTimeout(async function () {
                try {
                    var data = await patchContexto(id, { color: valor });
                    if (data.ok) {
                        mostrarAutosave();
                        var row = contextosTable.querySelector('tr[data-id="' + id + '"]');
                        if (row) {
                            var atSpan = row.querySelector('.ctx-at');
                            if (atSpan) atSpan.style.color = valor;
                        }
                    } else {
                        mostrarToast(data.error || 'Error al guardar color.');
                    }
                } catch (_) {
                    mostrarToast('Error de conexión.');
                }
            }, 300);
        });

        // Delegación de clicks
        contextosTable.addEventListener('click', function (e) {
            var btnArchivar  = e.target.closest('.btn-archivar-contexto');
            var btnRestaurar = e.target.closest('.btn-restaurar-contexto');
            var btnEliminar  = e.target.closest('.btn-eliminar-contexto');

            if (btnArchivar) {
                var idA = btnArchivar.dataset.id;
                confirmar(
                    'Archivar contexto',
                    '¿Archivar este contexto? Dejará de aparecer en los selects pero su historial se conserva.',
                    function (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                        archivarContexto(idA, btnArchivar);
                    }
                );
            } else if (btnRestaurar) {
                restaurarContexto(btnRestaurar.dataset.id, btnRestaurar);
            } else if (btnEliminar && !btnEliminar.disabled) {
                var idE = btnEliminar.dataset.id;
                confirmar(
                    'Eliminar contexto',
                    '¿Eliminar este contexto? Esta acción no se puede deshacer.',
                    function (modalEl) { eliminarContexto(idE, btnEliminar, modalEl); }
                );
            }
        });
    }

    // ── Crear contexto ────────────────────────────────────────────
    if (formCrearCtx) {
        var inputNombreCtx = document.getElementById('contexto-nombre-nuevo');
        var inputDescCtx   = document.getElementById('contexto-descripcion-nueva');
        var inputColorCtx  = document.getElementById('contexto-color-nuevo');
        var errorCrearCtx  = document.getElementById('contexto-crear-error');

        formCrearCtx.addEventListener('submit', async function (e) {
            e.preventDefault();
            var nombre = inputNombreCtx.value.trim();
            var desc   = inputDescCtx.value.trim();
            var color  = inputColorCtx.value;

            if (!nombre) {
                if (errorCrearCtx) {
                    errorCrearCtx.textContent = 'El nombre es obligatorio.';
                    errorCrearCtx.classList.remove('d-none');
                }
                return;
            }
            if (errorCrearCtx) errorCrearCtx.classList.add('d-none');

            var btn = formCrearCtx.querySelector('[type=submit]');
            btn.disabled = true;

            try {
                var res = await fetch('/config/contextos', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    new URLSearchParams({ nombre: nombre, descripcion: desc, color: color }),
                });
                var data = await res.json();

                if (data.ok) {
                    if (contextosTable) {
                        contextosTable.querySelector('tbody').insertAdjacentElement(
                            'afterbegin',
                            buildFilaContexto(data.data)
                        );
                        contextosTable.classList.remove('d-none');
                    }
                    if (contextosEmpty) contextosEmpty.classList.add('d-none');
                    formCrearCtx.reset();
                    inputColorCtx.value = '#6c757d';
                    inputNombreCtx.focus();
                } else {
                    if (errorCrearCtx) {
                        errorCrearCtx.textContent = data.error || 'Error al crear el contexto.';
                        errorCrearCtx.classList.remove('d-none');
                    }
                }
            } catch (_) {
                if (errorCrearCtx) {
                    errorCrearCtx.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    errorCrearCtx.classList.remove('d-none');
                }
            } finally {
                btn.disabled = false;
            }
        });
    }

    // ── Archivar contexto ─────────────────────────────────────────
    async function archivarContexto(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/contextos/' + id + '/archivar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = contextosTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'archivado';
                    row.querySelector('.td-estado-ctx').innerHTML =
                        '<span class="badge bg-secondary">Archivado</span>';
                    row.querySelector('.td-acciones-ctx').innerHTML = btnsArchivadoCtx(id);
                }
            } else {
                mostrarToast(data.error || 'Error al archivar el contexto.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Restaurar contexto ────────────────────────────────────────
    async function restaurarContexto(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/contextos/' + id + '/restaurar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = contextosTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'activo';
                    row.querySelector('.td-estado-ctx').innerHTML =
                        '<span class="badge bg-success">Activo</span>';
                    row.querySelector('.td-acciones-ctx').innerHTML = btnsActivoCtx(id);
                }
            } else {
                mostrarToast(data.error || 'Error al restaurar el contexto.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Eliminar contexto ─────────────────────────────────────────
    async function eliminarContexto(id, btn, modalEl) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/contextos/' + id, { method: 'DELETE' });
            var data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                var row = contextosTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(function () { row.remove(); }, 300);
                }
            } else {
                var msgDeps = (data.activos !== undefined)
                    ? 'No se puede eliminar: tiene ' + data.activos + ' acción(es) activa(s) vinculada(s).'
                    : (data.error || 'Error al eliminar.');
                mostrarExtraEnModal(msgDeps);
                btn.disabled = false;
            }
        } catch (_) {
            mostrarExtraEnModal('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Cargar sugeridos ──────────────────────────────────────────
    var btnSugeridos = document.querySelector('.btn-cargar-sugeridos');
    if (btnSugeridos) {
        btnSugeridos.addEventListener('click', async function () {
            btnSugeridos.disabled = true;
            try {
                var res  = await fetch('/config/contextos/sugeridos', { method: 'POST' });
                var data = await res.json();
                if (data.ok) {
                    window.location.reload();
                } else {
                    mostrarToast(data.error || 'Error al cargar contextos sugeridos.');
                    btnSugeridos.disabled = false;
                }
            } catch (_) {
                mostrarToast('Error de conexión. Inténtalo de nuevo.');
                btnSugeridos.disabled = false;
            }
        });
    }


    // ═══════════════════════════════════════════════════════════════
    // PERSONAS
    // ═══════════════════════════════════════════════════════════════

    const personasTable = document.getElementById('personas-table');
    const personasEmpty = document.getElementById('personas-empty');
    const formCrearPer  = document.getElementById('form-crear-persona');

    // ── Avatar helpers ────────────────────────────────────────────
    function getIniciales(nombre) {
        return (nombre || '').trim().split(/\s+/)
            .filter(function (p) { return p.length > 0; })
            .slice(0, 2)
            .map(function (p) { return p.charAt(0).toUpperCase(); })
            .join('') || '?';
    }

    function getColorAvatar(id) {
        var colores = ['#4a90d9','#e67e22','#2ecc71','#9b59b6',
                       '#e74c3c','#1abc9c','#f39c12','#3498db'];
        return colores[id % colores.length];
    }

    // ── PATCH helper ──────────────────────────────────────────────
    async function patchPersona(id, campos) {
        var res = await fetch('/config/personas/' + id, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(campos),
        });
        return res.json();
    }

    // ── Botones de acción ─────────────────────────────────────────
    function btnsActivoPer(id) {
        return '' +
            '<a href="/espera?persona_id=' + id + '"' +
               ' class="btn btn-sm btn-outline-info btn-ver-tareas-persona"' +
               ' data-id="' + id + '" title="Ver tareas en espera">' +
                '<i class="bi bi-list-task"></i>' +
            '</a> ' +
            '<button type="button" class="btn btn-sm btn-outline-warning btn-archivar-persona"' +
                    ' data-id="' + id + '" title="Archivar">' +
                '<i class="bi bi-archive"></i>' +
            '</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-persona"' +
                    ' data-id="' + id + '" title="Eliminar">' +
                '<i class="bi bi-trash"></i>' +
            '</button>';
    }

    function btnsArchivadoPer(id) {
        return '' +
            '<button type="button" class="btn btn-sm btn-outline-success btn-restaurar-persona"' +
                    ' data-id="' + id + '" title="Restaurar">' +
                '<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar' +
            '</button>';
    }

    // ── Construir fila nueva ──────────────────────────────────────
    function buildFilaPersona(per) {
        var tr = document.createElement('tr');
        tr.dataset.id     = per.id;
        tr.dataset.estado = 'activo';
        tr.innerHTML =
            '<td>' +
                '<div class="per-avatar d-flex align-items-center justify-content-center' +
                          ' rounded-circle fw-bold text-white"' +
                     ' data-id="' + per.id + '"' +
                     ' style="width:36px;height:36px;background:' + getColorAvatar(per.id) + ';' +
                            'font-size:.75rem;flex-shrink:0;user-select:none;">' +
                    escHTML(getIniciales(per.nombre)) +
                '</div>' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                       ' class="persona-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"' +
                       ' data-id="' + per.id + '"' +
                       ' value="' + escHTML(per.nombre) + '"' +
                       ' maxlength="100">' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                       ' class="persona-rol form-control form-control-sm border-0 bg-transparent p-0 text-muted"' +
                       ' data-id="' + per.id + '"' +
                       ' value="' + escHTML(per.rol || '') + '"' +
                       ' maxlength="100">' +
            '</td>' +
            '<td class="text-center">' +
                '<span class="badge bg-secondary bg-opacity-50 text-secondary">0</span>' +
            '</td>' +
            '<td class="text-center td-estado-per">' +
                '<span class="badge bg-success">Activa</span>' +
            '</td>' +
            '<td class="td-acciones-per">' + btnsActivoPer(per.id) + '</td>';
        return tr;
    }

    // ── Debounce timers para personas ─────────────────────────────
    var debouncePerTimers = {};

    if (personasTable) {
        // Autoguardado nombre y rol
        personasTable.addEventListener('focusout', function (e) {
            var inputNombre = e.target.closest('.persona-nombre');
            var inputRol    = e.target.closest('.persona-rol');

            if (inputNombre) {
                var id    = inputNombre.dataset.id;
                var valor = inputNombre.value.trim();
                clearTimeout(debouncePerTimers[id + '-nombre']);
                debouncePerTimers[id + '-nombre'] = setTimeout(async function () {
                    limpiarErrorInline(inputNombre);
                    try {
                        var data = await patchPersona(id, { nombre: valor });
                        if (data.ok) {
                            mostrarAutosave();
                            var row = personasTable.querySelector('tr[data-id="' + id + '"]');
                            if (row) {
                                var avatar = row.querySelector('.per-avatar');
                                if (avatar) avatar.textContent = getIniciales(valor);
                            }
                        } else {
                            errorInline(inputNombre, data.error || 'Error al guardar.');
                        }
                    } catch (_) {
                        errorInline(inputNombre, 'Error de conexión.');
                    }
                }, 300);
            }

            if (inputRol) {
                var idR    = inputRol.dataset.id;
                var valorR = inputRol.value.trim();
                clearTimeout(debouncePerTimers[idR + '-rol']);
                debouncePerTimers[idR + '-rol'] = setTimeout(async function () {
                    try {
                        var data = await patchPersona(idR, { rol: valorR });
                        if (data.ok) {
                            mostrarAutosave();
                        } else {
                            mostrarToast(data.error || 'Error al guardar rol.');
                        }
                    } catch (_) {
                        mostrarToast('Error de conexión.');
                    }
                }, 300);
            }
        });

        // Delegación de clicks
        personasTable.addEventListener('click', function (e) {
            var btnArchivar  = e.target.closest('.btn-archivar-persona');
            var btnRestaurar = e.target.closest('.btn-restaurar-persona');
            var btnEliminar  = e.target.closest('.btn-eliminar-persona');

            if (btnArchivar) {
                var idA = btnArchivar.dataset.id;
                confirmar(
                    'Archivar persona',
                    '¿Archivar esta persona? Dejará de aparecer en los selects pero su historial se conserva.',
                    function (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                        archivarPersona(idA, btnArchivar);
                    }
                );
            } else if (btnRestaurar) {
                restaurarPersona(btnRestaurar.dataset.id, btnRestaurar);
            } else if (btnEliminar && !btnEliminar.disabled) {
                var idE = btnEliminar.dataset.id;
                confirmar(
                    'Eliminar persona',
                    '¿Eliminar esta persona? Esta acción no se puede deshacer.',
                    function (modalEl) { eliminarPersona(idE, btnEliminar, modalEl); }
                );
            }
        });
    }

    // ── Crear persona ─────────────────────────────────────────────
    if (formCrearPer) {
        var inputNombrePer = document.getElementById('persona-nombre-nuevo');
        var inputRolPer    = document.getElementById('persona-rol-nuevo');
        var errorCrearPer  = document.getElementById('persona-crear-error');

        formCrearPer.addEventListener('submit', async function (e) {
            e.preventDefault();
            var nombre = inputNombrePer.value.trim();
            var rol    = inputRolPer.value.trim();

            if (!nombre) {
                if (errorCrearPer) {
                    errorCrearPer.textContent = 'El nombre es obligatorio.';
                    errorCrearPer.classList.remove('d-none');
                }
                return;
            }
            if (errorCrearPer) errorCrearPer.classList.add('d-none');

            var btn = formCrearPer.querySelector('[type=submit]');
            btn.disabled = true;

            try {
                var res = await fetch('/config/personas', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    new URLSearchParams({ nombre: nombre, rol: rol }),
                });
                var data = await res.json();

                if (data.ok) {
                    if (personasTable) {
                        personasTable.querySelector('tbody').insertAdjacentElement(
                            'afterbegin',
                            buildFilaPersona(data.data)
                        );
                        personasTable.classList.remove('d-none');
                    }
                    if (personasEmpty) personasEmpty.classList.add('d-none');
                    formCrearPer.reset();
                    inputNombrePer.focus();
                } else {
                    if (errorCrearPer) {
                        errorCrearPer.textContent = data.error || 'Error al crear la persona.';
                        errorCrearPer.classList.remove('d-none');
                    }
                }
            } catch (_) {
                if (errorCrearPer) {
                    errorCrearPer.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    errorCrearPer.classList.remove('d-none');
                }
            } finally {
                btn.disabled = false;
            }
        });
    }

    // ── Archivar persona ──────────────────────────────────────────
    async function archivarPersona(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/personas/' + id + '/archivar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = personasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'archivado';
                    row.querySelector('.td-estado-per').innerHTML =
                        '<span class="badge bg-secondary">Archivada</span>';
                    row.querySelector('.td-acciones-per').innerHTML = btnsArchivadoPer(id);
                }
            } else {
                mostrarToast(data.error || 'Error al archivar la persona.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Restaurar persona ─────────────────────────────────────────
    async function restaurarPersona(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/personas/' + id + '/restaurar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = personasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'activo';
                    row.querySelector('.td-estado-per').innerHTML =
                        '<span class="badge bg-success">Activa</span>';
                    row.querySelector('.td-acciones-per').innerHTML = btnsActivoPer(id);
                }
            } else {
                mostrarToast(data.error || 'Error al restaurar la persona.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }

    // ── Eliminar persona ──────────────────────────────────────────
    async function eliminarPersona(id, btn, modalEl) {
        btn.disabled = true;
        try {
            var res  = await fetch('/config/personas/' + id, { method: 'DELETE' });
            var data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                var row = personasTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(function () { row.remove(); }, 300);
                }
            } else {
                var msgDeps = (data.tareas !== undefined)
                    ? 'No se puede eliminar: tiene ' + data.tareas + ' tarea(s) activa(s) vinculada(s).'
                    : (data.error || 'Error al eliminar.');
                mostrarExtraEnModal(msgDeps);
                btn.disabled = false;
            }
        } catch (_) {
            mostrarExtraEnModal('Error de conexión. Inténtalo de nuevo.');
            btn.disabled = false;
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // PROYECTOS (Config)
    // ═══════════════════════════════════════════════════════════════

    var proyectosPaneEl  = document.getElementById('pane-proyectos');
    var debPrjTimers     = {};
    var pendingPrjValues = {}; // id → valor pendiente de guardar

    async function patchProyecto(id, nombre) {
        var res = await fetch('/proyectos/' + encodeURIComponent(id), {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ nombre: nombre }),
        });
        return res.json();
    }

    if (proyectosPaneEl) {
        // ── Autoguardado con debounce ────────────────────────────
        proyectosPaneEl.addEventListener('focusout', function (e) {
            var input = e.target.closest('.proyecto-cfg-nombre');
            if (!input) return;

            var id    = input.dataset.id;
            var valor = input.value.trim();

            // Registrar valor pendiente para que el handler de
            // navegación lo detecte si el click llega antes del debounce
            pendingPrjValues[id] = valor;

            clearTimeout(debPrjTimers[id + '-nombre']);
            debPrjTimers[id + '-nombre'] = setTimeout(async function () {
                limpiarErrorInline(input);
                try {
                    var data = await patchProyecto(id, valor);
                    if (data.ok) {
                        mostrarAutosave();
                        delete pendingPrjValues[id];
                    } else {
                        errorInline(input, data.error || 'Error al guardar.');
                    }
                } catch (_) {
                    errorInline(input, 'Error de conexión.');
                }
            }, 300);
        });

        // ── Navegación a "Ver acciones" con flush del pendiente ──
        // focusout siempre dispara ANTES que click, así que cuando
        // llega el click el debounce ya está programado pero aún
        // no ha ejecutado el PATCH. Lo cancelamos y hacemos el
        // PATCH de inmediato antes de navegar.
        proyectosPaneEl.addEventListener('click', async function (e) {
            var btn = e.target.closest('.btn-ver-acciones-cfg');
            if (!btn) return;

            var id   = btn.dataset.id;
            var href = btn.dataset.href;

            clearTimeout(debPrjTimers[id + '-nombre']);

            if (pendingPrjValues[id] !== undefined) {
                var valor = pendingPrjValues[id];
                var input = proyectosPaneEl.querySelector(
                    '.proyecto-cfg-nombre[data-id="' + id + '"]'
                );
                if (input) limpiarErrorInline(input);

                btn.disabled = true;
                try {
                    var data = await patchProyecto(id, valor);
                    if (data.ok) {
                        delete pendingPrjValues[id];
                    } else {
                        if (input) errorInline(input, data.error || 'Error al guardar.');
                        btn.disabled = false;
                        return; // No navegar si hubo error
                    }
                } catch (_) {
                    if (input) errorInline(input, 'Error de conexión.');
                    btn.disabled = false;
                    return;
                }
            }

            window.location.href = href;
        });
    }

}());
