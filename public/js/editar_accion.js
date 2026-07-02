(function () {
    'use strict';

    var _cacheEdit = {
        areas:     null,
        contextos: null,
        proyectos: null,
    };

    function fetchCachedEdit(key, url) {
        if (_cacheEdit[key] !== null) return Promise.resolve(_cacheEdit[key]);
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    '',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) _cacheEdit[key] = data;
            return data;
        });
    }

    function poblarSelectEdit(selectEl, items, emptyLabel) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">' + emptyLabel + '</option>';
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value       = item.id;
            opt.textContent = item.nombre;
            selectEl.appendChild(opt);
        });
    }

    var modalEl   = null;
    var currentId = null;

    window.abrirModalEditar = function (config) {
        if (!modalEl) modalEl = document.getElementById('modalEditarAccion');
        if (!modalEl) return;

        currentId = config.id;

        var elTitulo  = document.getElementById('edit-titulo');
        var elFecha   = document.getElementById('edit-fecha');
        var elHoraIni = document.getElementById('edit-hora-inicio');
        var elHoraFin = document.getElementById('edit-hora-fin');
        var elErr     = document.getElementById('edit-error');

        if (elTitulo)  elTitulo.value  = config.titulo      || '';
        if (elFecha)   elFecha.value   = config.fecha        || '';
        if (elHoraIni) elHoraIni.value = config.horaInicio   || '';
        if (elHoraFin) elHoraFin.value = config.horaFin      || '';
        if (elErr)     elErr.classList.add('d-none');

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        Promise.all([
            fetchCachedEdit('areas',     '/procesar/areas'),
            fetchCachedEdit('contextos', '/procesar/contextos'),
            fetchCachedEdit('proyectos', '/procesar/proyectos'),
        ]).then(function (results) {
            var areasSel      = document.getElementById('edit-area');
            var contextosSel  = document.getElementById('edit-contexto');
            var proyectosSel  = document.getElementById('edit-proyecto');

            if (results[0].ok) poblarSelectEdit(areasSel,     results[0].data, 'Sin área');
            if (results[1].ok) poblarSelectEdit(contextosSel, results[1].data, 'Sin contexto');
            if (results[2].ok) poblarSelectEdit(proyectosSel, results[2].data, 'Sin proyecto');

            if (areasSel)     areasSel.value     = config.areaId     || '';
            if (contextosSel) contextosSel.value = config.contextoId || '';
            if (proyectosSel) proyectosSel.value = config.proyectoId || '';
        }).catch(function () {});
    };

    var btnGuardar = document.getElementById('btn-guardar-editar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            if (!modalEl) modalEl = document.getElementById('modalEditarAccion');

            var titulo    = (document.getElementById('edit-titulo')?.value     || '').trim();
            var areaId    =  document.getElementById('edit-area')?.value       || '';
            var contextoId = document.getElementById('edit-contexto')?.value   || '';
            var proyectoId = document.getElementById('edit-proyecto')?.value   || '';
            var fecha     =  document.getElementById('edit-fecha')?.value      || '';
            var horaIni   =  document.getElementById('edit-hora-inicio')?.value || '';
            var horaFin   =  document.getElementById('edit-hora-fin')?.value   || '';
            var elErr     =  document.getElementById('edit-error');

            if (!titulo) {
                if (elErr) {
                    elErr.textContent = 'El título es obligatorio.';
                    elErr.classList.remove('d-none');
                }
                return;
            }
            if (elErr) elErr.classList.add('d-none');

            var textoOrig      = btnGuardar.textContent.trim();
            btnGuardar.disabled    = true;
            btnGuardar.textContent = 'Guardando...';

            fetch('/acciones/' + encodeURIComponent(currentId), {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    _method:      'PATCH',
                    titulo:       titulo,
                    area_id:      areaId,
                    contexto_id:  contextoId,
                    proyecto_id:  proyectoId,
                    fecha_accion: fecha,
                    hora_inicio:  horaIni,
                    hora_fin:     horaFin,
                }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btnGuardar.disabled    = false;
                btnGuardar.textContent = textoOrig;
                if (data.ok) {
                    document.dispatchEvent(new CustomEvent('accion:editada', {
                        detail: {
                            id:         currentId,
                            titulo:     titulo,
                            areaId:     areaId,
                            contextoId: contextoId,
                            proyectoId: proyectoId,
                            fecha:      fecha,
                            horaInicio: horaIni,
                            horaFin:    horaFin,
                        },
                    }));
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                } else {
                    if (elErr) {
                        elErr.textContent = data.error || 'Error al guardar.';
                        elErr.classList.remove('d-none');
                    }
                }
            })
            .catch(function () {
                btnGuardar.disabled    = false;
                btnGuardar.textContent = textoOrig;
                if (elErr) {
                    elErr.textContent = 'Error de conexión.';
                    elErr.classList.remove('d-none');
                }
            });
        });
    }

    var btnBorrar = document.getElementById('btn-borrar-editar');
    if (btnBorrar) {
        btnBorrar.addEventListener('click', function () {
            if (!window.confirmarAccion) return;

            window.confirmarAccion(
                'Se eliminará esta acción permanentemente. ¿Continuar?',
                function () {
                    if (!modalEl) modalEl = document.getElementById('modalEditarAccion');

                    var textoOrig      = btnBorrar.innerHTML;
                    btnBorrar.disabled = true;
                    btnBorrar.innerHTML = '<i class="bi bi-hourglass me-1"></i>Borrando...';

                    var elErr = document.getElementById('edit-error');

                    fetch('/acciones/' + encodeURIComponent(currentId), {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body:    new URLSearchParams({ _method: 'DELETE' }),
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            document.dispatchEvent(new CustomEvent('accion:eliminada', {
                                detail: { id: currentId },
                            }));
                            btnBorrar.disabled  = false;
                            btnBorrar.innerHTML = textoOrig;
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        } else {
                            btnBorrar.disabled  = false;
                            btnBorrar.innerHTML = textoOrig;
                            if (elErr) {
                                elErr.textContent = data.error || 'Error al borrar.';
                                elErr.classList.remove('d-none');
                            }
                        }
                    })
                    .catch(function () {
                        btnBorrar.disabled  = false;
                        btnBorrar.innerHTML = textoOrig;
                        if (elErr) {
                            elErr.textContent = 'Error de conexión.';
                            elErr.classList.remove('d-none');
                        }
                    });
                }
            );
        });
    }

}());
