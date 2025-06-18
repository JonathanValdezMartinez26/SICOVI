<h4>Autorización de Viáticos y Gastos</h4>

<div class="card">
    <div class="row justify-content-between m-4">
        <div class="col-4">
            <label for="fechasSolicitudes" class="form-label">Rango de fechas mostrado</label>
            <div class="input-group input-group-merge">
                <input type="text" id="fechasSolicitudes" class="form-control cursor-pointer" readonly>
                <i class="input-group-text fa fa-calendar-days"></i>
                <button id="btnBuscarSolicitudes" class="btn btn-outline-primary">Actualizar</button>
            </div>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="historialSolicitudes" class="dt-responsive table border-top">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Solicitante</th>
                    <th>Fecha de solicitud</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para ver solicitud -->
<div class="modal fade" id="modalVerAutorizacion" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 class="address-title mb-2">Autorización de viáticos/gastos</h4>
                    <p class="address-subtitle"></p>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <label class="form-label">Solicitante</label>
                        <input type="text" id="verSolicitante" class="form-control" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sucursal</label>
                        <input type="text" id="verSucursal" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Tipo de Solicitud</label>
                        <input type="text" id="verTipoSol" class="form-control" readonly>
                        <input type="hidden" id="verSolicitudId">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Fecha de Solicitud</label>
                        <input type="text" id="verFechaSol" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Monto Solicitado</label>
                        <input type="text" id="verMontoSolicitado" class="form-control" readonly>
                    </div>
                    <div class="form-group col-12">
                        <label class="form-label">Proyecto</label>
                        <input type="text" id="verProyecto" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Fecha de inicio del proyecto</label>
                        <input type="text" id="verFechaI" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Fecha de termino del proyecto</label>
                        <input type="text" id="verFechaF" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Monto Autorizado</label>
                        <div class="input-group input-group-merge">
                            <i class="input-group-text fa fa-dollar-sign"></i>
                            <input type="text" id="montoAutorizado" name="montoAutorizado" class="form-control" placeholder="0.00">
                        </div>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-12">
                        <label for="observacionesAutorizacion" class="form-label">Observaciones</label>
                        <textarea id="observacionesAutorizacion" name="observacionesAutorizacion" class="form-control" placeholder="Observaciones. Ej.: Se autorizo un monto menor debido a..." rows="2" maxlength="500"></textarea>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelar" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                <button type="button" id="rechazar" class="btn btn-danger">Rechazar</button>
                <button type="button" id="autorizar" class="btn btn-primary">Autorizar</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para ver solicitud -->