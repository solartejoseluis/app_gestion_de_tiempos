(function () {
    'use strict';

    var meta        = document.getElementById('revision-paso-actual');
    var listEl      = document.getElementById('inbox-list');
    var listaWrapper = document.getElementById('revision-inbox-lista');
    var vacioEl     = document.getElementById('inbox-vacio-revision');
    var counterEl   = document.getElementById('revision-inbox-counter');
    var btnCont     = document.getElementById('btn-continuar-paso2');
    var modalBorrar = document.getElementById('modalBorrar');
    var btnBorrar   = document.getElementById('btn-confirmar-borrar');

    // Solo aplica en el paso 1
    if (!listEl || !meta) return;

    var totalInicial = parseInt(meta.dataset.totalInicial, 10) || 0;
    var deleteId     = null;

    // ── Helpers ──────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    function formatFecha(ts) {
        var d = new Date(ts.replace(' ', 'T'));
        return d.getDate() + ' ' + MESES[d.getMonth()] + ' ' + d.getFullYear();
    }

    function crearItem(item) {
        var div = document.createElement('div');
        div.className  = 'item';
        div.dataset.id = item.id;
        div.innerHTML  =
            '<div class="item-body">' +
                '<div class="item-text">' + escHtml(item.titulo) + '</div>' +
                '<div class="item-date">' + formatFecha(item.created_at) + '</div>' +
            '</div>' +
            '<div class="item-actions">' +
                '<button class="btn btn-sm btn-process"' +
                        ' data-bs-toggle="modal" data-bs-target="#modalProcesar"' +
                        ' data-item-id="' + item.id + '"' +
                        ' data-item-texto="' + escHtml(item.titulo) + '">' +
                        'Procesar</button>' +
                '<button class="btn btn-sm btn-del"' +
                        ' data-item-id="' + item.id + '"' +
                        ' data-bs-toggle="modal" data-bs-target="#modalBorrar">' +
                        'Borrar</button>' +
            '</div>';
        return div;
    }

    function actualizarEstado(count) {
        if (counterEl) {
            counterEl.textContent = count;
            counterEl.className   = 'badge fs-5 ms-3 flex-shrink-0 ' +
                                    (count > 0 ? 'bg-warning text-dark' : 'bg-success');
        }
        if (count === 0) {
            if (listaWrapper) listaWrapper.classList.add('d-none');
            if (vacioEl)      vacioEl.classList.remove('d-none');
            if (btnCont)      btnCont.disabled = false;
        } else {
            if (listaWrapper) listaWrapper.classList.remove('d-none');
            if (vacioEl)      vacioEl.classList.add('d-none');
            if (btnCont)      btnCont.disabled = true;
        }
    }

    // ── window.recargarLista — invocado por procesamiento.js ─────
    window.recargarLista = function (items) {
        listEl.querySelectorAll('.item').forEach(function (el) { el.remove(); });
        items.forEach(function (item) { listEl.appendChild(crearItem(item)); });

        // Actualizar badge del sidebar
        var badge = document.getElementById('sidebar-inbox-badge');
        if (badge) {
            badge.textContent = items.length;
            badge.classList.toggle('d-none', items.length === 0);
        }

        actualizarEstado(items.length);
    };

    // ── Modal borrar ──────────────────────────────────────────────
    if (modalBorrar) {
        modalBorrar.addEventListener('show.bs.modal', function (e) {
            deleteId = e.relatedTarget ? e.relatedTarget.dataset.itemId : null;
        });
        modalBorrar.addEventListener('hidden.bs.modal', function () {
            deleteId = null;
        });
    }

    if (btnBorrar) {
        btnBorrar.addEventListener('click', function () {
            if (!deleteId) return;
            btnBorrar.disabled = true;

            fetch('/inbox/delete', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'id=' + encodeURIComponent(deleteId),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    var el = listEl.querySelector('.item[data-id="' + deleteId + '"]');
                    if (el) el.remove();
                    var n = listEl.querySelectorAll('.item').length;

                    var badge = document.getElementById('sidebar-inbox-badge');
                    if (badge) {
                        badge.textContent = n;
                        badge.classList.toggle('d-none', n === 0);
                    }

                    actualizarEstado(n);
                    bootstrap.Modal.getInstance(modalBorrar).hide();
                }
            })
            .catch(function () {})
            .finally(function () { btnBorrar.disabled = false; });
        });
    }

    // ── Botón "Continuar al Paso 2" ───────────────────────────────
    if (btnCont) {
        btnCont.addEventListener('click', function () {
            var actuales = listEl.querySelectorAll('.item').length;
            if (actuales > 0) return;

            btnCont.disabled = true;
            var procesados = totalInicial - actuales;

            fetch('/revision/paso/1/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'items_procesados=' + encodeURIComponent(procesados),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    window.location.href = '/revision/paso/' + data.data.siguiente;
                } else {
                    alert(data.error || 'No se puede continuar todavía.');
                    btnCont.disabled = false;
                }
            })
            .catch(function () {
                alert('Error de conexión. Inténtalo de nuevo.');
                btnCont.disabled = false;
            });
        });
    }

    // ── Estado inicial ───────────────────────────────────────────
    actualizarEstado(totalInicial);

}());

// ── Paso 2 — Proyectos activos ───────────────────────────────────
(function () {
    'use strict';

    var meta  = document.getElementById('revision-paso-actual');
    if (!meta || meta.dataset.paso !== '2') return;

    var lista          = document.getElementById('lista-proyectos-revision');
    var totalProyectos = lista ? (parseInt(lista.dataset.total, 10) || 0) : 0;
    var revisadosCount = 0;
    var btnCont        = document.getElementById('btn-continuar-paso3');

    if (btnCont) {
        btnCont.addEventListener('click', function () {
            btnCont.disabled = true;
            fetch('/revision/paso/2/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'proyectos_revisados=' + encodeURIComponent(revisadosCount),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) window.location.href = '/revision/paso/' + data.data.siguiente;
                else { alert(data.error || 'Error.'); btnCont.disabled = false; }
            })
            .catch(function () { alert('Error de conexión.'); btnCont.disabled = false; });
        });
    }

    if (totalProyectos === 0) {
        if (btnCont) btnCont.disabled = false;
        return;
    }

    var pendingModalId = null;
    var counterEl      = document.getElementById('contador-proyectos');

    function actualizarContador() {
        var pendientes = totalProyectos - revisadosCount;
        if (counterEl) {
            counterEl.textContent = pendientes;
            counterEl.className   = 'badge fs-5 ms-3 flex-shrink-0 ' +
                                    (pendientes > 0 ? 'bg-warning text-dark' : 'bg-success');
        }
        if (revisadosCount >= totalProyectos && btnCont) {
            btnCont.disabled = false;
        }
    }

    function marcarRevisado(card) {
        if (!card) return;
        var check = card.querySelector('.revisado-check');
        if (check && !check.classList.contains('d-none')) return; // ya marcado
        card.querySelectorAll('.botones-decision button').forEach(function (b) {
            b.disabled = true;
        });
        if (check) check.classList.remove('d-none');
        revisadosCount++;
        actualizarContador();
    }

    function postProyecto(endpoint, id) {
        return fetch(endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'id=' + encodeURIComponent(id),
        }).then(function (r) { return r.json(); });
    }

    // window.recargarLista es la señal de éxito del modal de procesamiento
    window.recargarLista = function () {
        if (pendingModalId) {
            var card = lista.querySelector('[data-proyecto-id="' + pendingModalId + '"]');
            marcarRevisado(card);
            pendingModalId = null;
        }
    };

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn || btn.disabled) return;
        var id   = btn.dataset.id;
        var card = lista.querySelector('[data-proyecto-id="' + id + '"]');
        if (!card) return;

        if (btn.classList.contains('btn-confirmar-proyecto')) {
            marcarRevisado(card);
        } else if (btn.classList.contains('btn-pausar-revision')) {
            btn.disabled = true;
            postProyecto('/proyectos/pausar', id)
                .then(function (data) {
                    if (data.ok) marcarRevisado(card);
                    else { alert(data.error || 'Error al pausar.'); btn.disabled = false; }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
        } else if (btn.classList.contains('btn-completar-revision')) {
            btn.disabled = true;
            postProyecto('/proyectos/completar', id)
                .then(function (data) {
                    if (data.ok) marcarRevisado(card);
                    else { alert(data.error || 'Error al completar.'); btn.disabled = false; }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
        }
    });

    // Detectar apertura del modal desde un botón de agregar-accion
    var modalProcesar = document.getElementById('modalProcesar');
    if (modalProcesar) {
        modalProcesar.addEventListener('show.bs.modal', function (e) {
            var trigger = e.relatedTarget;
            if (trigger && trigger.classList.contains('btn-agregar-accion-proyecto')) {
                pendingModalId = trigger.dataset.id;
            } else {
                pendingModalId = null;
            }
        });
        modalProcesar.addEventListener('hidden.bs.modal', function () {
            // pendingModalId solo se limpia en window.recargarLista (éxito)
            // o en show.bs.modal (apertura nueva) — no aquí, para evitar
            // la race condition cuando hidden.bs.modal llega antes que el fetch.
        });
    }

}());

// ── Paso 3 — En espera de ────────────────────────────────────────
(function () {
    'use strict';

    var meta  = document.getElementById('revision-paso-actual');
    if (!meta || meta.dataset.paso !== '3') return;

    var lista            = document.getElementById('lista-espera-revision');
    var totalEspera      = lista ? (parseInt(lista.dataset.total, 10) || 0) : 0;
    var cerradasEspera   = 0;
    var btnCont          = document.getElementById('btn-continuar-paso4');

    if (btnCont) {
        btnCont.addEventListener('click', function () {
            btnCont.disabled = true;
            fetch('/revision/paso/3/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'delegaciones_cerradas=' + encodeURIComponent(cerradasEspera),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) window.location.href = '/revision/paso/' + data.data.siguiente;
                else { alert(data.error || 'Error.'); btnCont.disabled = false; }
            })
            .catch(function () { alert('Error de conexión.'); btnCont.disabled = false; });
        });
    }

    if (totalEspera === 0) {
        if (btnCont) btnCont.disabled = false;
        return;
    }

    var decisionesEspera = 0;
    var counterEl        = document.getElementById('contador-espera');

    function actualizarContador() {
        var pendientes = totalEspera - decisionesEspera;
        if (counterEl) {
            counterEl.textContent = pendientes;
            counterEl.className   = 'badge fs-5 ms-3 flex-shrink-0 ' +
                                    (pendientes > 0 ? 'bg-warning text-dark' : 'bg-success');
        }
        if (decisionesEspera >= totalEspera && btnCont) {
            btnCont.disabled = false;
        }
    }

    function marcarDecision(card, texto) {
        card.querySelectorAll('.item-actions button').forEach(function (b) {
            b.disabled = true;
        });
        var indicator = card.querySelector('.decision-indicator');
        if (indicator) {
            indicator.textContent = texto || '✓ Revisado';
            indicator.classList.remove('d-none');
        }
        decisionesEspera++;
        actualizarContador();
    }

    function postEspera(url, params) {
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(params).toString(),
        }).then(function (r) { return r.json(); });
    }

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn || btn.disabled) return;

        // El botón cancelar no lleva data-id
        if (btn.classList.contains('btn-cancelar-posponer')) {
            var parentCard = btn.closest('[data-item-id]');
            if (parentCard) { var il = parentCard.querySelector('.posponer-inline'); if (il) il.remove(); }
            return;
        }

        var id = btn.dataset.id;
        if (!id) return;
        var card = lista.querySelector('[data-item-id="' + id + '"]');
        if (!card) return;

        if (btn.classList.contains('btn-recibido-espera')) {
            btn.disabled = true;
            postEspera('/espera/recibido', { id: id })
                .then(function (data) {
                    if (data.ok) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity    = '0';
                        setTimeout(function () { card.remove(); }, 300);
                        cerradasEspera++;
                        decisionesEspera++;
                        actualizarContador();
                    } else {
                        alert(data.error || 'Error.'); btn.disabled = false;
                    }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });

        } else if (btn.classList.contains('btn-seguimiento-espera')) {
            marcarDecision(card, '✓ Pendiente seguimiento');

        } else if (btn.classList.contains('btn-posponer-espera')) {
            if (card.querySelector('.posponer-inline')) return;
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 7);
            var defDate = tomorrow.toISOString().split('T')[0];

            var inline = document.createElement('div');
            inline.className = 'posponer-inline d-flex gap-2 align-items-center flex-wrap mt-2';
            inline.innerHTML =
                '<input type="date" class="form-control form-control-sm" style="max-width:160px"' +
                ' value="' + defDate + '" id="posponer-fecha-' + id + '">' +
                '<button type="button" class="btn btn-sm btn-primary btn-confirmar-posponer" data-id="' + id + '">' +
                'Confirmar</button>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary btn-cancelar-posponer">Cancelar</button>';
            card.querySelector('.item-body').appendChild(inline);

        } else if (btn.classList.contains('btn-confirmar-posponer')) {
            var fechaInput = document.getElementById('posponer-fecha-' + id);
            if (!fechaInput || !fechaInput.value) { alert('Selecciona una fecha.'); return; }
            btn.disabled = true;
            postEspera('/espera/posponer', { id: id, fecha_accion: fechaInput.value })
                .then(function (data) {
                    if (data.ok) {
                        var inline = card.querySelector('.posponer-inline');
                        if (inline) inline.remove();
                        marcarDecision(card, '✓ Pospuesto a ' + fechaInput.value);
                    } else {
                        alert(data.error || 'Error.'); btn.disabled = false;
                    }
                })
                .catch(function () { alert('Error.'); btn.disabled = false; });

        } else if (btn.classList.contains('btn-convertir-espera')) {
            btn.disabled = true;
            postEspera('/espera/convertir', { id: id })
                .then(function (data) {
                    if (data.ok) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity    = '0';
                        setTimeout(function () { card.remove(); }, 300);
                        decisionesEspera++;
                        actualizarContador();
                    } else {
                        alert(data.error || 'Error.'); btn.disabled = false;
                    }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
        }
    });

}());

// ── Paso 4 — Algún día / tal vez ─────────────────────────────────
(function () {
    'use strict';

    var meta  = document.getElementById('revision-paso-actual');
    if (!meta || meta.dataset.paso !== '4') return;

    var lista             = document.getElementById('lista-someday-revision');
    var totalSomeday      = lista ? (parseInt(lista.dataset.total, 10) || 0) : 0;
    var activadasSomeday  = 0;
    var btnCont           = document.getElementById('btn-continuar-paso5');

    if (btnCont) {
        btnCont.addEventListener('click', function () {
            btnCont.disabled = true;
            fetch('/revision/paso/4/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'incubadas_activadas=' + encodeURIComponent(activadasSomeday),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) window.location.href = '/revision/paso/' + data.data.siguiente;
                else { alert(data.error || 'Error.'); btnCont.disabled = false; }
            })
            .catch(function () { alert('Error de conexión.'); btnCont.disabled = false; });
        });
    }

    if (totalSomeday === 0) {
        if (btnCont) btnCont.disabled = false;
        return;
    }

    var decisionesSomeday = 0;
    var deleteSomedayId   = null;
    var counterEl         = document.getElementById('contador-someday');
    var modalEl           = document.getElementById('modalConfirmarEliminar');
    var btnConfirmarDel   = document.getElementById('btn-confirmar-eliminar-someday');
    var tituloEl          = document.getElementById('someday-eliminar-titulo');

    function actualizarContador() {
        var pendientes = totalSomeday - decisionesSomeday;
        if (counterEl) {
            counterEl.textContent = pendientes;
            counterEl.className   = 'badge fs-5 ms-3 flex-shrink-0 ' +
                                    (pendientes > 0 ? 'bg-warning text-dark' : 'bg-success');
        }
        if (decisionesSomeday >= totalSomeday && btnCont) {
            btnCont.disabled = false;
        }
    }

    function desaparecer(card, callback) {
        card.style.transition = 'opacity 0.3s';
        card.style.opacity    = '0';
        setTimeout(function () { card.remove(); if (callback) callback(); }, 300);
    }

    function postSomeday(url, params) {
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(params).toString(),
        }).then(function (r) { return r.json(); });
    }

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-id]');
        if (!btn || btn.disabled) return;
        var id   = btn.dataset.id;
        var card = lista.querySelector('[data-item-id="' + id + '"]');
        if (!card) return;

        if (btn.classList.contains('btn-activar-someday')) {
            btn.disabled = true;
            postSomeday('/someday/activar', { id: id })
                .then(function (data) {
                    if (data.ok) {
                        desaparecer(card, function () {
                            activadasSomeday++;
                            decisionesSomeday++;
                            actualizarContador();
                        });
                    } else {
                        alert(data.error || 'Error.'); btn.disabled = false;
                    }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });

        } else if (btn.classList.contains('btn-mantener-someday')) {
            btn.disabled = true;
            var d = new Date();
            d.setDate(d.getDate() + 7);
            var nuevaFecha = d.toISOString().split('T')[0];
            postSomeday('/someday/posponer', { id: id, fecha_revision: nuevaFecha })
                .then(function (data) {
                    if (data.ok) {
                        card.querySelectorAll('.item-actions button').forEach(function (b) {
                            b.disabled = true;
                        });
                        decisionesSomeday++;
                        actualizarContador();
                    } else {
                        alert(data.error || 'Error.'); btn.disabled = false;
                    }
                })
                .catch(function () { alert('Error de conexión.'); btn.disabled = false; });

        } else if (btn.classList.contains('btn-eliminar-someday')) {
            deleteSomedayId = id;
            if (tituloEl) tituloEl.textContent = btn.dataset.titulo || '';
            if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    if (btnConfirmarDel && modalEl) {
        btnConfirmarDel.addEventListener('click', function () {
            if (!deleteSomedayId) return;
            btnConfirmarDel.disabled = true;
            var id = deleteSomedayId;
            postSomeday('/someday/eliminar', { id: id })
                .then(function (data) {
                    bootstrap.Modal.getInstance(modalEl).hide();
                    if (data.ok) {
                        var card = lista.querySelector('[data-item-id="' + id + '"]');
                        if (card) desaparecer(card, function () {
                            decisionesSomeday++;
                            actualizarContador();
                        });
                    } else {
                        alert(data.error || 'Error.');
                    }
                    btnConfirmarDel.disabled = false;
                    deleteSomedayId = null;
                })
                .catch(function () {
                    alert('Error de conexión.');
                    btnConfirmarDel.disabled = false;
                    deleteSomedayId = null;
                });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            deleteSomedayId = null;
        });
    }

}());

// ── Paso 5 — Calendario ──────────────────────────────────────────
(function () {
    'use strict';

    var meta = document.getElementById('revision-paso-actual');
    if (!meta || meta.dataset.paso !== '5') return;

    var btnCont = document.getElementById('btn-continuar-paso6');
    if (!btnCont) return;

    btnCont.addEventListener('click', function () {
        btnCont.disabled = true;
        fetch('/revision/paso/5/completar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    '',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) window.location.href = '/revision/paso/' + data.data.siguiente;
            else { alert(data.error || 'Error.'); btnCont.disabled = false; }
        })
        .catch(function () { alert('Error de conexión.'); btnCont.disabled = false; });
    });
}());

// ── Paso 6 — Foco de la semana ───────────────────────────────────
(function () {
    'use strict';

    var meta = document.getElementById('revision-paso-actual');
    if (!meta || meta.dataset.paso !== '6') return;

    var textarea = document.getElementById('foco-semana-input');
    var counter  = document.getElementById('foco-counter');
    var btnComp  = document.getElementById('btn-completar-revision');

    if (textarea) {
        textarea.addEventListener('input', function () {
            var len = textarea.value.trim().length;
            if (counter) counter.textContent = textarea.value.length + ' / 500';
            if (btnComp) btnComp.disabled = (len === 0);
        });
    }

    if (btnComp) {
        btnComp.addEventListener('click', function () {
            var foco = textarea ? textarea.value.trim() : '';
            if (!foco) return;
            btnComp.disabled = true;
            fetch('/revision/completar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'foco_semana=' + encodeURIComponent(foco),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) window.location.href = '/revision/cierre';
                else { alert(data.error || 'Error.'); btnComp.disabled = false; }
            })
            .catch(function () { alert('Error de conexión.'); btnComp.disabled = false; });
        });
    }
}());
