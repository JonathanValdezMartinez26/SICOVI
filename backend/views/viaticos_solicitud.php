<h4>Mis Solicitudes de Viáticos y Gastos</h4>

<div class="card">
    <div class="row justify-content-between m-4">
        <div class="col-4">
            <label for="fechasSolicitudes" class="form-label">Rango de fechas mostrado</label>
            <div class="input-group input-group-merge">
                <input type="text" id="fechasSolicitudes" class="form-control cursor-pointer">
                <i class="input-group-text fa fa-calendar-days"></i>
                <button id="btnBuscarSolicitudes" class="btn btn-outline-primary">Actualizar</button>
            </div>
        </div>
        <div class="col-4 d-flex align-self-end justify-content-end">
            <button id="btnAgregar" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalNuevaSolicitud"><i class="fa fa-plus">&nbsp;</i>Nueva Solicitud</button>
        </div>
    </div>

    <div class="card-datatable pt-0">
        <table id="historialSolicitudes" class="table table-bordered datatables-basic">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Proyecto</th>
                    <th>Fecha de Registro</th>
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

<!-- Modal para agregar solicitud -->
<div class="modal fade" id="modalNuevaSolicitud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center mb-6">
                    <h4 class="address-title mb-2">Nueva solicitud de viáticos/gastos</h4>
                    <p class="address-subtitle">Capture los datos solicitados</p>
                </div>
                <div class="row">
                    <div class="form-group col-3">
                        <label for="tipoSolicitud" class="form-label">Tipo</label>
                        <select class="form-select" id="tipoSolicitud">
                            <option value="1">Viáticos (por comprobar)</option>
                            <option value="2">Gastos (reembolso)</option>
                        </select>
                    </div>
                    <div class="form-group col-5">
                        <label for="fechasNuevaSolicitud" class="form-label">Rango de fechas</label>
                        <div class="input-group input-group-merge">
                            <input type="text" id="fechasNuevaSolicitud" name="fechasNuevaSolicitud" class="form-control cursor-pointer">
                            <i class="input-group-text fa fa-calendar-days"></i>
                        </div>
                    </div>
                    <div class="form-group col-4">
                        <label id="lblMontoVG" for="montoVG" class="form-label">Monto Solicitado</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base fa fa-dollar-sign"></i>
                            </span>
                            <input type="text" id="montoVG" name="montoVG" class="form-control" placeholder="0.00">
                        </div>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-12">
                        <label for="proyecto" class="form-label">Proyecto</label>
                        <input type="text" id="proyecto" name="proyecto" class="form-control" placeholder="Nombre del proyecto">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>

                <div id="comprobantesGastos" class="row mt-5" style="display: none;">
                    <div class="col-12">
                        <h5 class="text-center">Comprobantes de Gastos</h5>
                        <table id="tablaComprobantes" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Comprobante</th>
                                    <th>Monto</th>
                                    <th>Observaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyComprobantes">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <button type="button" id="btnAgregarComprobante" class="btn btn-success btn-sm">
                                            <i class="fa fa-plus">&nbsp;</i>Agregar
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancelaSolicitud" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                <button type="button" id="registraSolicitud" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para agregar solicitud -->

<!-- Modal para agregar comprobante -->
<div class="modal fade" id="modalAgregarComprobante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-simple">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center mb-6">
                    <h4 class="address-title mb-2">Agregar Comprobante</h4>
                    <p class="address-subtitle">Capture los datos del comprobante</p>
                </div>
                <div class="row gy-2">
                    <div class="form-group col-8">
                        <label for="comprobante" class="form-label">Comprobante</label>
                        <input type="file" id="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group col-4">
                        <label for="montoComprobante" class="form-label">Monto</label>
                        <div class="input-group input-group-merge">
                            <i class="input-group-text fa fa-dollar-sign"></i>
                            <input type="text" id="montoComprobante" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group col-12">
                        <label for="observacionesComprobante" class="form-label">Observaciones</label>
                        <input type="text" id="observacionesComprobante" class="form-control"
                            placeholder="Observaciones del comprobante">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                <button type="button" id="agregarComprobante" class="btn btn-primary">Agregar Comprobante</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para agregar comprobante -->