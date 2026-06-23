<div class="modal fade" id="modalProcesar"
     data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-labelledby="modalProcesarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-semibold" id="modalProcesarLabel">Procesar ítem</h6>
            </div>

            <div class="modal-body pt-2">

                <!-- ENCABEZADO — siempre visible -->
                <div id="proc-header" class="proc-section">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span id="proc-titulo-text" class="flex-grow-1 fw-medium text-break"></span>
                        <input id="proc-titulo-input"
                               type="text"
                               class="form-control form-control-sm flex-grow-1 d-none"
                               maxlength="255">
                        <button type="button"
                                id="btn-editar-titulo"
                                class="btn btn-sm btn-link p-0 text-muted flex-shrink-0"
                                title="Editar título">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <label for="proc-area" class="form-label small fw-medium mb-1">Área</label>
                    <select id="proc-area" class="form-select form-select-sm">
                        <option value="">Selecciona un área</option>
                    </select>
                </div>

                <!-- ── B1 — ¿Requiere acción? ── -->
                <hr class="proc-separator">
                <div id="proc-b1" class="proc-section">
                    <p class="proc-section-label">¿Requiere acción?</p>
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-si-accion"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-check-circle me-1"></i>Sí, requiere acción
                        </button>
                        <button type="button" id="btn-no-accion"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-x-circle me-1"></i>No requiere acción
                        </button>
                    </div>
                </div>

                <!-- ── RAMA A — No accionable ── -->
                <hr class="proc-separator d-none" id="sep-rama-a">
                <div id="proc-rama-a" class="proc-section d-none">
                    <p class="proc-section-label">¿Qué hacemos con esto?</p>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="btn-a1"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-3">
                            <i class="bi bi-trash d-block mb-1 proc-btn-icon"></i>Eliminar
                        </button>
                        <button type="button" id="btn-a2"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-3">
                            <i class="bi bi-clock d-block mb-1 proc-btn-icon"></i>Incubar
                        </button>
                        <button type="button" id="btn-a3"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-3">
                            <i class="bi bi-file-text d-block mb-1 proc-btn-icon"></i>Referencia
                        </button>
                    </div>

                    <!-- A1 — Eliminar -->
                    <div id="proc-a1-form" class="d-none">
                        <p class="small text-muted mb-2">
                            Este ítem se moverá a eliminados y no aparecerá en ninguna lista.
                        </p>
                        <button type="button" id="btn-confirmar-eliminar"
                                class="btn btn-sm btn-danger w-100">
                            <i class="bi bi-trash me-1"></i>Confirmar eliminación
                        </button>
                    </div>

                    <!-- A2 — Incubar -->
                    <div id="proc-a2-form" class="d-none">
                        <div class="mb-2">
                            <label for="proc-a2-proyecto" class="form-label small mb-1">
                                Proyecto <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <select id="proc-a2-proyecto" class="form-select form-select-sm">
                                <option value="">Ninguno</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="proc-a2-fecha-tipo" class="form-label small mb-1">Revisar</label>
                            <select id="proc-a2-fecha-tipo" class="form-select form-select-sm">
                                <option value="ninguno">Sin fecha</option>
                                <option value="fecha">Fecha específica</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input id="proc-a2-fecha" type="date"
                                   class="form-control form-control-sm d-none">
                        </div>
                        <button type="button" id="btn-guardar-incubar"
                                class="btn btn-sm btn-proc-bifurcacion w-100">
                            <i class="bi bi-star me-1"></i>Guardar en Algún día
                        </button>
                    </div>

                    <!-- A3 — Referencia -->
                    <div id="proc-a3-form" class="d-none">
                        <div class="mb-2">
                            <label for="proc-a3-proyecto" class="form-label small mb-1">
                                Proyecto <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <select id="proc-a3-proyecto" class="form-select form-select-sm">
                                <option value="">Ninguno</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="proc-a3-etiquetas" class="form-label small mb-1">Etiquetas</label>
                            <input id="proc-a3-etiquetas" type="text"
                                   class="form-control form-control-sm"
                                   placeholder="etiqueta1, etiqueta2">
                        </div>
                        <button type="button" id="btn-guardar-referencia"
                                class="btn btn-sm btn-proc-bifurcacion w-100">
                            <i class="bi bi-file-text me-1"></i>Guardar en Referencia
                        </button>
                    </div>
                </div>

                <!-- ── B2 — ¿Es un proyecto? ── -->
                <hr class="proc-separator d-none" id="sep-b2">
                <div id="proc-b2" class="proc-section d-none">
                    <p class="proc-section-label">¿Cuántos pasos tiene?</p>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="btn-accion-unica"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-check-square me-1"></i>Es una acción única
                        </button>
                        <button type="button" id="btn-es-proyecto"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-folder me-1"></i>Es un proyecto (más de un paso)
                        </button>
                    </div>

                    <!-- Subformulario proyecto -->
                    <div id="proc-proyecto-form" class="d-none">
                        <div class="mb-2">
                            <label for="proc-resultado-deseado" class="form-label small mb-1">
                                Resultado deseado <span class="text-danger">*</span>
                            </label>
                            <textarea id="proc-resultado-deseado"
                                      class="form-control form-control-sm" rows="2"
                                      placeholder="¿Cómo se verá cuando esté completo?"></textarea>
                        </div>
                        <div class="mb-2">
                            <label for="proc-proyecto-existente" class="form-label small mb-1">Proyecto</label>
                            <select id="proc-proyecto-existente" class="form-select form-select-sm">
                                <option value="nuevo">Crear nuevo proyecto</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <input id="proc-nombre-proyecto" type="text"
                                   class="form-control form-control-sm d-none"
                                   placeholder="Nombre del nuevo proyecto" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label for="proc-proyecto-contexto" class="form-label small mb-1">
                                Contexto <span class="text-danger">*</span>
                            </label>
                            <select id="proc-proyecto-contexto" class="form-select form-select-sm">
                                <option value="">Selecciona un contexto</option>
                            </select>
                        </div>
                        <button type="button" id="btn-guardar-proyecto"
                                class="btn btn-sm btn-proc-bifurcacion w-100">
                            <i class="bi bi-folder-plus me-1"></i>Guardar proyecto y crear acción
                        </button>
                    </div>
                </div>

                <!-- ── B3 — ¿Menos de 2 minutos? ── -->
                <hr class="proc-separator d-none" id="sep-b3">
                <div id="proc-b3" class="proc-section d-none">
                    <p class="proc-section-label">¿Lo puedes hacer ahora mismo?</p>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" id="btn-menos-2min"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-lightning me-1"></i>Sí, menos de 2 minutos
                        </button>
                        <button type="button" id="btn-mas-2min"
                                class="btn btn-sm btn-proc-bifurcacion flex-fill py-2">
                            <i class="bi bi-hourglass me-1"></i>No, toma más tiempo
                        </button>
                    </div>
                    <!-- Inline: aparece al elegir "menos de 2 min" -->
                    <button type="button" id="btn-completar-ahora"
                            class="btn btn-sm btn-success w-100 d-none">
                        <i class="bi bi-lightning-fill me-1"></i>Marcar como hecho ahora
                    </button>
                </div>

                <!-- ── B4 — ¿Quién ejecuta? ── -->
                <hr class="proc-separator d-none" id="sep-b4">
                <div id="proc-b4" class="proc-section d-none">
                    <p class="proc-section-label">¿Quién ejecuta esta acción?</p>
                    <select id="proc-quien" class="form-select form-select-sm">
                        <option value="yo">Yo mismo</option>
                    </select>
                </div>

                <!-- ── DELEGAR ── -->
                <hr class="proc-separator d-none" id="sep-delegar">
                <div id="proc-delegar" class="proc-section d-none">
                    <p class="proc-section-label">Delegar</p>
                    <div class="mb-2">
                        <label for="proc-del-proyecto" class="form-label small mb-1">
                            Proyecto <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <select id="proc-del-proyecto" class="form-select form-select-sm">
                            <option value="">Ninguno</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="proc-del-contexto" class="form-label small mb-1">
                            Contexto <span class="text-danger">*</span>
                        </label>
                        <select id="proc-del-contexto" class="form-select form-select-sm">
                            <option value="">Selecciona un contexto</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="proc-del-seguimiento" class="form-label small mb-1">Seguimiento</label>
                        <select id="proc-del-seguimiento" class="form-select form-select-sm">
                            <option value="ninguno">Sin fecha</option>
                            <option value="dia">Recordatorio de día</option>
                            <option value="cita">Cita con hora</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input id="proc-del-fecha" type="date"
                               class="form-control form-control-sm d-none">
                    </div>
                    <button type="button" id="btn-guardar-delegar"
                            class="btn btn-sm btn-proc-bifurcacion w-100">
                        <i class="bi bi-hourglass-split me-1"></i>Delegar → En espera de
                    </button>
                </div>

                <!-- ── PROGRAMAR ── -->
                <hr class="proc-separator d-none" id="sep-programar">
                <div id="proc-programar" class="proc-section d-none">
                    <p class="proc-section-label">Programar acción</p>
                    <div class="mb-2">
                        <label for="proc-prog-proyecto" class="form-label small mb-1">
                            Proyecto <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <select id="proc-prog-proyecto" class="form-select form-select-sm">
                            <option value="">Ninguno</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="proc-prog-contexto" class="form-label small mb-1">
                            Contexto <span class="text-danger">*</span>
                        </label>
                        <select id="proc-prog-contexto" class="form-select form-select-sm">
                            <option value="">Selecciona un contexto</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="proc-prog-tiempo" class="form-label small mb-1">Programación</label>
                        <select id="proc-prog-tiempo" class="form-select form-select-sm">
                            <option value="ninguno">Sin fecha — próximas acciones</option>
                            <option value="dia">Recordatorio de día</option>
                            <option value="cita">Cita con día y hora</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input id="proc-prog-fecha" type="date"
                               class="form-control form-control-sm d-none">
                    </div>
                    <button type="button" id="btn-guardar-programar"
                            class="btn btn-sm btn-proc-bifurcacion w-100">
                        <i class="bi bi-check2-square me-1"></i>Guardar en Próximas acciones
                    </button>
                </div>

            </div><!-- /.modal-body -->

            <div id="proc-error" class="alert alert-danger d-none mx-3 mb-2 py-2 small" role="alert"></div>

            <div class="modal-footer border-0 pt-0 justify-content-start">
                <button type="button" id="btn-procesar-despues"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Procesar después
                </button>
            </div>

        </div>
    </div>
</div>
