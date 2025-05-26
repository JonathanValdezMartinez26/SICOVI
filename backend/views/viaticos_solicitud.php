<h4>Mis Solicitudes de Viáticos y Gastos</h4>

<div class="card">
    <div class="row m-10">
        <div class="col-8 d-flex flex-row align-items-end justify-content-start">
            <div class="form-group m-2">
                <label>Desde</label>
                <input type="date" id="fechaI" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group m-2">
                <label>Hasta</label>
                <input type="date" id="fechaF" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn-primary m-2" id="btnBuscar">Buscar</button>
        </div>
        <div class="col-4 d-flex align-items-end justify-content-between">
            <button id="btnAgregar" class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#modalNuevaSolicitud"><i class="fa-solid fa-plus">&nbsp;</i>Nueva Solicitud</button>
            <button id="btnExportar" class="btn btn-success mb-2"><i class="fa-solid fa-file-excel">&nbsp;</i>Exportar</button>
        </div>
    </div>

    <div class="card-datatable">
        <table id="historialSolicitudes" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Fin</th>
                    <th>Monto Solicitado</th>
                    <th>Estado</th>
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
                <div class="row gy-2">
                    <div id="grpTipoSolicitud" class="form-group col-4">
                        <label for="tipoSolicitud">Tipo</label>
                        <select class="form-select" id="tipoSolicitud">
                            <option value="1">Viáticos</option>
                            <option value="2">Gastos</option>
                        </select>
                    </div>
                    <div id="grpProyecto" class="form-group col-12">
                        <label for="proyecto">Proyecto</label>
                        <input type="text" id="proyecto" class="form-control" placeholder="Nombre del proyecto">
                    </div>
                    <div id="grpFechaI" class="form-group col-4">
                        <label for="fechaI">Fecha de Inicio</label>
                        <input type="date" id="fechaI" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div id="grpFechaF" class="form-group col-4">
                        <label for="fechaF">Fecha de Fin</label>
                        <input type="date" id="fechaF" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div id="grpMontoVG" class="form-group col-4">
                        <label id="lblMontoVG" for="montoVG">Monto Solicitado</label>
                        <input type="text" id="montoVG" class="form-control" placeholder="0.00">
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
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <button type="button" id="btnAgregarComprobante" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
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
                    <div id="grpComprobante" class="form-group col-8">
                        <label for="comprobante">Comprobante</label>
                        <input type="file" id="comprobante" class="form-control"
                            placeholder="Seleccione un archivo" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div id="grpMontoComprobante" class="form-group col-4">
                        <label for="montoComprobante">Monto</label>
                        <input type="text" id="montoComprobante" class="form-control" placeholder="0.00">
                    </div>
                    <div id="grpObservacionesComprobante" class="form-group col-12">
                        <label for="observacionesComprobante">Observaciones</label>
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