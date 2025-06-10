<h4>Validación de comprobantes de Viáticos/Gastos</h4>

<div id="solicitudes" class="card">
    <div class="row g-0">
        <div class="col">
            <div class="row justify-content-between m-4">
                <div class="col-4">
                    <label class="form-label">Rango de fechas mostrado</label>
                    <div class="input-group input-group-merge cursor-pointer">
                        <input type="text" id="fechasSolicitudes" class="form-control" readonly>
                        <i class="input-group-text fa fa-calendar-days"></i>
                        <button id="btnBuscarSolicitudes" class="btn btn-outline-primary">Actualizar</button>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table id="historialSolicitudes" class="dt-responsive table border-top table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Fecha Registro</th>
                            <th>Proyecto</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí se llenarán las filas con JavaScript -->
                        <tr class="cursor-pointer">
                            <td>1</td>
                            <td>Viáticos</td>
                            <td>01/01/2025</td>
                            <td>Viáticos de Negocios</td>
                            <td>$500.00</td>
                            <td>
                                <button id="verDemo" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVerEntrega">Ver Detalles</button>
                            </td>
                        </tr>
                        <!-- Más filas según los datos -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="solicitud-detalles" class="col bg-lighter show d-flex flex-column">
            <div class="card shadow-none px-5 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="sidebar" data-target="#solicitud-detalles">
                        <span class="btn p-0"><i class="fa fa-arrow-left">&nbsp;</i>Volver</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="btn text-gray cursor-not-allowed">
                            <i class="fa fa-chevron-left">&nbsp;</i>Anterior
                        </span>
                        <span class="mx-2">|</span>
                        <span class="btn text-gray cursor-not-allowed">
                            Siguiente<i class="fa fa-chevron-right">&nbsp;</i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="m-3 flex-grow-1 d-flex flex-column">
                <div class="flex-grow-1 d-flex">
                    <div class="card w-80 me-3 mb-3">
                        <div class="card-body">
                            <embed src="" type="" class="w-100 h-100" id="comprobanteEmbed" style="border: none;">
                            <!-- Aquí se cargará el comprobante -->
                            <p class="text-center">Cargando comprobante...</p>
                            </embed>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <span>Comprobante 1 de 1</span>
                            <div>
                                <label class="form-label">Fecha captura</label>
                                <input type="text" id="fechaCaptura" class="form-control" readonly>
                            </div>
                            <div>
                                <label class="form-label">Fecha comprobante</label>
                                <input type="text" id="fechaComprobante" class="form-control" readonly>
                            </div>
                            <div>
                                <label class="form-label">Monto</label>
                                <input type="text" id="montoComprobante" class="form-control" readonly>
                            </div>
                            <div>
                                <label class="form-label">Observaciones</label>
                                <input type="text" id="observaciones" class="form-control" readonly>
                            </div>
                            <div class="mt-3">
                                <button id="btnValidarComprobante" class="btn btn-success">Aceptar comprobante</button>
                                <button id="btnRechazarComprobante" class="btn btn-danger">Rechazar comprobante</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Solicitante</label>
                                    <input type="text" id="solicitante" class="form-control" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Proyecto</label>
                                    <input type="text" id="proyecto" class="form-control" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Fecha solicitud</label>
                                    <input type="text" id="fechaSolicitud" class="form-control" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Fecha limite</label>
                                    <input type="text" id="fechaLimite" class="form-control" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Monto</label>
                                    <input type="text" id="montoSolicitud" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>