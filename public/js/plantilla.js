(function () {
    'use strict';

    // ── Referencias ──────────────────────────────────────────────
    var bloquesTable = document.getElementById('bloques-table');
    var bloquesEmpty = document.getElementById('bloques-empty');
    var formCrear    = document.getElementById('form-crear-bloque');
    var autosaveEl   = document.getElementById('autosave-indicator');
    var errorCrear   = document.getElementById('bloque-crear-error');

    // ── Autosave indicator ───────────────────────────────────────
    var autosaveTimer = null;

    function mostrarAutosave() {
        if (!autosaveEl) return;
        var d = new Date();
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        autosaveEl.textContent      = 'Guardado · ' + h + ':' + m;
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

    // ── Toast de error ───────────────────────────────────────────
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
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto"' +
                    ' data-bs-dismiss="toast"></button>' +
                '</div></div>'
        );
        var toastEl = document.getElementById(id);
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }

    // ── Modal de confirmación ────────────────────────────────────
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
        var okBtn   = document.getElementById('modal-confirm-ok');
        var nuevoOk = okBtn.cloneNode(true);
        okBtn.replaceWith(nuevoOk);
        nuevoOk.addEventListener('click', function () { callback(modalEl); });
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // ── Helpers ──────────────────────────────────────────────────
    function escHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    var DIAS_LABEL = { 1: 'L', 2: 'M', 3: 'X', 4: 'J', 5: 'V', 6: 'S', 7: 'D' };

    function diasStr(diasSemana) {
        return (diasSemana || '').split(',')
            .map(function (d) { return DIAS_LABEL[parseInt(d, 10)] || ''; })
            .filter(Boolean).join(' ');
    }

    function fmtFecha(dateStr) {
        if (!dateStr) return '';
        var MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        var d = new Date(dateStr + 'T00:00:00');
        return d.getDate() + ' ' + MESES[d.getMonth()] + ' ' + d.getFullYear();
    }

    function vigenciaStr(fechaInicio, fechaFin) {
        if (!fechaInicio && !fechaFin) return 'Indefinido';
        if (fechaInicio && fechaFin)   return 'desde ' + fmtFecha(fechaInicio) + ' hasta ' + fmtFecha(fechaFin);
        if (fechaInicio)               return 'desde ' + fmtFecha(fechaInicio);
        return 'hasta ' + fmtFecha(fechaFin);
    }

    function btnsActivo(id) {
        return '<button type="button" class="btn btn-sm btn-outline-warning btn-archivar-bloque"' +
               ' data-id="' + id + '" title="Archivar"><i class="bi bi-archive"></i></button> ' +
               '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-bloque"' +
               ' data-id="' + id + '" title="Eliminar"><i class="bi bi-trash"></i></button>';
    }

    function btnsInactivo(id) {
        return '<button type="button" class="btn btn-sm btn-outline-success btn-restaurar-bloque"' +
               ' data-id="' + id + '" title="Restaurar">' +
               '<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar</button>';
    }

    function buildFila(b) {
        var tr = document.createElement('tr');
        tr.dataset.id     = b.id;
        tr.dataset.estado = 'activo';
        var horario  = b.hora_inicio.slice(0, 5) + ' – ' + b.hora_fin.slice(0, 5);
        var dias     = diasStr(b.dias_semana);
        var vigencia = vigenciaStr(b.fecha_inicio || '', b.fecha_fin || '');
        tr.innerHTML =
            '<td>' +
                '<input type="color"' +
                ' class="bloque-color form-control form-control-color"' +
                ' data-id="' + b.id + '" value="' + escHTML(b.color) + '"' +
                ' style="width:40px;height:28px;padding:2px;cursor:pointer;"' +
                ' title="Cambiar color">' +
            '</td>' +
            '<td>' +
                '<input type="text"' +
                ' class="bloque-nombre form-control form-control-sm border-0 bg-transparent p-0 fw-medium"' +
                ' data-id="' + b.id + '" value="' + escHTML(b.nombre) + '" maxlength="100">' +
            '</td>' +
            '<td><span class="text-muted small">' + escHTML(dias) + '</span></td>' +
            '<td><span class="text-muted small">' + escHTML(horario) + '</span></td>' +
            '<td><span class="text-muted small">' + escHTML(vigencia) + '</span></td>' +
            '<td class="text-center td-estado-bloque">' +
                '<span class="badge bg-success">Activo</span>' +
            '</td>' +
            '<td class="td-acciones-bloque">' + btnsActivo(b.id) + '</td>';
        return tr;
    }

    // ── PATCH helper ─────────────────────────────────────────────
    async function patchBloque(id, campos) {
        var res = await fetch('/plantilla/' + id, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(campos),
        });
        return res.json();
    }

    // ── Delegación sobre la tabla ─────────────────────────────────
    var debounceTimers = {};

    if (bloquesTable) {

        // Autoguardado nombre
        bloquesTable.addEventListener('focusout', function (e) {
            var input = e.target.closest('.bloque-nombre');
            if (!input) return;
            var id    = input.dataset.id;
            var valor = input.value.trim();
            clearTimeout(debounceTimers[id + '-nombre']);
            debounceTimers[id + '-nombre'] = setTimeout(async function () {
                try {
                    var data = await patchBloque(id, { nombre: valor });
                    if (data.ok) {
                        mostrarAutosave();
                    } else {
                        mostrarToast(data.error || 'Error al guardar.');
                    }
                } catch (_) {
                    mostrarToast('Error de conexión.');
                }
            }, 300);
        });

        // Autoguardado color
        bloquesTable.addEventListener('change', function (e) {
            var input = e.target.closest('.bloque-color');
            if (!input) return;
            var id    = input.dataset.id;
            var valor = input.value;
            clearTimeout(debounceTimers[id + '-color']);
            debounceTimers[id + '-color'] = setTimeout(async function () {
                try {
                    var data = await patchBloque(id, { color: valor });
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

        // Clicks delegados — archivar / restaurar / eliminar
        bloquesTable.addEventListener('click', function (e) {
            var btnArchivar  = e.target.closest('.btn-archivar-bloque');
            var btnRestaurar = e.target.closest('.btn-restaurar-bloque');
            var btnEliminar  = e.target.closest('.btn-eliminar-bloque');

            if (btnArchivar) {
                var idA = btnArchivar.dataset.id;
                confirmar(
                    'Archivar bloque',
                    '¿Archivar este bloque? Dejará de mostrarse en la agenda.',
                    function (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                        archivarBloque(idA, btnArchivar);
                    }
                );
            } else if (btnRestaurar) {
                restaurarBloque(btnRestaurar.dataset.id, btnRestaurar);
            } else if (btnEliminar) {
                var idE = btnEliminar.dataset.id;
                confirmar(
                    'Eliminar bloque',
                    '¿Eliminar este bloque? Esta acción no se puede deshacer.',
                    function (modalEl) { eliminarBloque(idE, btnEliminar, modalEl); }
                );
            }
        });
    }

    // ── Crear bloque ─────────────────────────────────────────────
    if (formCrear) {
        formCrear.addEventListener('submit', async function (e) {
            e.preventDefault();

            var nombre     = document.getElementById('bloque-nombre-nuevo').value.trim();
            var color      = document.getElementById('bloque-color-nuevo').value;
            var horaInicio = document.getElementById('bloque-hora-inicio').value;
            var horaFin    = document.getElementById('bloque-hora-fin').value;
            var fechaDesde = document.getElementById('bloque-fecha-desde').value;
            var fechaHasta = document.getElementById('bloque-fecha-hasta').value;

            var dias = [];
            formCrear.querySelectorAll('input[name="dias_semana[]"]:checked')
                .forEach(function (cb) { dias.push(cb.value); });

            if (errorCrear) errorCrear.classList.add('d-none');

            if (!nombre) {
                if (errorCrear) { errorCrear.textContent = 'El nombre es obligatorio.'; errorCrear.classList.remove('d-none'); }
                return;
            }
            if (dias.length === 0) {
                if (errorCrear) { errorCrear.textContent = 'Selecciona al menos un día.'; errorCrear.classList.remove('d-none'); }
                return;
            }
            if (!horaInicio || !horaFin) {
                if (errorCrear) { errorCrear.textContent = 'La hora de inicio y fin son obligatorias.'; errorCrear.classList.remove('d-none'); }
                return;
            }

            var btn = formCrear.querySelector('[type=submit]');
            btn.disabled = true;

            try {
                var res = await fetch('/plantilla', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        nombre:       nombre,
                        color:        color,
                        dias_semana:  dias.join(','),
                        hora_inicio:  horaInicio,
                        hora_fin:     horaFin,
                        fecha_inicio: fechaDesde,
                        fecha_fin:    fechaHasta,
                    }),
                });
                var data = await res.json();

                if (data.ok) {
                    if (bloquesTable) {
                        bloquesTable.querySelector('tbody').insertAdjacentElement(
                            'afterbegin',
                            buildFila(data.data)
                        );
                        bloquesTable.classList.remove('d-none');
                    }
                    if (bloquesEmpty) bloquesEmpty.classList.add('d-none');
                    formCrear.reset();
                    document.getElementById('bloque-color-nuevo').value = '#f0c040';
                } else {
                    if (errorCrear) {
                        errorCrear.textContent = data.error || 'Error al crear el bloque.';
                        errorCrear.classList.remove('d-none');
                    }
                }
            } catch (_) {
                if (errorCrear) {
                    errorCrear.textContent = 'Error de conexión.';
                    errorCrear.classList.remove('d-none');
                }
            } finally {
                btn.disabled = false;
            }
        });
    }

    // ── Archivar ─────────────────────────────────────────────────
    async function archivarBloque(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/plantilla/' + id + '/archivar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = bloquesTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'inactivo';
                    row.querySelector('.td-estado-bloque').innerHTML =
                        '<span class="badge bg-secondary">Inactivo</span>';
                    row.querySelector('.td-acciones-bloque').innerHTML = btnsInactivo(id);
                }
            } else {
                mostrarToast(data.error || 'Error al archivar.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión.');
            btn.disabled = false;
        }
    }

    // ── Restaurar ─────────────────────────────────────────────────
    async function restaurarBloque(id, btn) {
        btn.disabled = true;
        try {
            var res  = await fetch('/plantilla/' + id + '/restaurar', { method: 'POST' });
            var data = await res.json();
            if (data.ok) {
                var row = bloquesTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.dataset.estado = 'activo';
                    row.querySelector('.td-estado-bloque').innerHTML =
                        '<span class="badge bg-success">Activo</span>';
                    row.querySelector('.td-acciones-bloque').innerHTML = btnsActivo(id);
                }
            } else {
                mostrarToast(data.error || 'Error al restaurar.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión.');
            btn.disabled = false;
        }
    }

    // ── Eliminar ──────────────────────────────────────────────────
    async function eliminarBloque(id, btn, modalEl) {
        btn.disabled = true;
        try {
            var res  = await fetch('/plantilla/' + id, { method: 'DELETE' });
            var data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                var row = bloquesTable.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(function () { row.remove(); }, 300);
                }
            } else {
                mostrarToast(data.error || 'Error al eliminar.');
                btn.disabled = false;
            }
        } catch (_) {
            mostrarToast('Error de conexión.');
            btn.disabled = false;
        }
    }

}());
