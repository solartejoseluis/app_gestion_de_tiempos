<div class="modal fade" id="modalEditarAccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold">Editar acción</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label for="edit-titulo" class="form-label small fw-medium mb-1">
                        Título <span class="text-danger">*</span>
                    </label>
                    <input id="edit-titulo" type="text"
                           class="form-control form-control-sm"
                           maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="edit-area" class="form-label small fw-medium mb-1">Área</label>
                    <select id="edit-area" class="form-select form-select-sm">
                        <option value="">Sin área</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="edit-contexto" class="form-label small fw-medium mb-1">Contexto</label>
                    <select id="edit-contexto" class="form-select form-select-sm">
                        <option value="">Sin contexto</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="edit-proyecto" class="form-label small fw-medium mb-1">Proyecto</label>
                    <select id="edit-proyecto" class="form-select form-select-sm">
                        <option value="">Sin proyecto</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="edit-fecha" class="form-label small fw-medium mb-1">Fecha</label>
                    <input id="edit-fecha" type="date" class="form-control form-control-sm">
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label for="edit-hora-inicio" class="form-label small fw-medium mb-1">
                            Hora inicio
                        </label>
                        <input id="edit-hora-inicio" type="time"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label for="edit-hora-fin" class="form-label small fw-medium mb-1">
                            Hora fin
                        </label>
                        <input id="edit-hora-fin" type="time"
                               class="form-control form-control-sm">
                    </div>
                </div>

                <div id="edit-error"
                     class="alert alert-danger d-none py-2 small mt-2"
                     role="alert"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button"
                        id="btn-guardar-editar"
                        class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>
