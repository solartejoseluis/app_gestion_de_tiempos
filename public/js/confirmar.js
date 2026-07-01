(function () {
    'use strict';

    var modalEl   = document.getElementById('modalConfirmar');
    var tituloEl  = document.getElementById('confirmar-titulo');
    var textoEl   = document.getElementById('confirmar-texto');
    var btnEl     = document.getElementById('btn-confirmar-accion');
    var pendingFn = null;

    if (!modalEl || !btnEl) return;

    // window.confirmarAccion(texto, onConfirmar, opciones?)
    // opciones: { titulo, textoBoton } — ambos opcionales, por defecto es el flujo de "eliminar".
    window.confirmarAccion = function (texto, onConfirmar, opciones) {
        opciones = opciones || {};
        if (tituloEl) tituloEl.textContent = opciones.titulo     || '¿Eliminar permanentemente?';
        if (textoEl)  textoEl.textContent  = texto;
        if (btnEl)    btnEl.textContent    = opciones.textoBoton || 'Eliminar';

        pendingFn = onConfirmar;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    btnEl.addEventListener('click', function () {
        bootstrap.Modal.getInstance(modalEl)?.hide();
        if (pendingFn) { pendingFn(); pendingFn = null; }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pendingFn = null;
    });

}());
