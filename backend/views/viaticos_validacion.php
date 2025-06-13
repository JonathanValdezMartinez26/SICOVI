<h4>Validación de comprobantes de Viáticos/Gastos</h4>

<div id="solicitudes" class="card">
    <div class="row g-0">
        <div class="col">
            <div class="row justify-content-between m-4">
                <div class="col-4">
                    <label class="form-label">Rango de fechas mostrado</label>
                    <div class="input-group input-group-merge cursor-pointer">
                        <input type="text" id="fechasComprobaciones" class="form-control" readonly>
                        <i class="input-group-text fa fa-calendar-days"></i>
                        <button id="btnBuscarComprobaciones" class="btn btn-outline-primary">Actualizar</button>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table id="historialComprobaciones" class="dt-responsive table border-top table-hover">
                    <thead>
                        <tr>
                            <th rowspan="2" class="d-none"></th>
                            <th rowspan="2">ID</th>
                            <th rowspan="2">Tipo</th>
                            <th rowspan="2">Fecha Registro</th>
                            <th rowspan="2">Proyecto</th>
                            <th rowspan="2">Entregado</th>
                            <th colspan="3" class="text-center">Comprobantes</th>
                            <th rowspan="2">Acciones</th>
                        </tr>
                        <tr>
                            <th><i class="fa fa-file-arrow-up"></i></th>
                            <th><i class="fa fa-ban text-danger"></i></th>
                            <th><i class="fa fa-check text-success"></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="solicitudDetalles" class="col bg-lighter d-flex flex-column">
            <div class="card shadow-none px-5 py-2 rounded-bottom-0">
                <div class="d-flex justify-content-between align-items-center gap-5">
                    <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="sidebar" data-target="#solicitudDetalles">
                        <span class="btn p-0"><i class="fa fa-arrow-left">&nbsp;</i>Volver</span>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Solicitante</label>
                            <input type="text" id="solicitante" class="form-control" readonly>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Fecha solicitud</label>
                            <input type="text" id="fechaSolicitud" class="form-control" readonly>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Fecha limite</label>
                            <input type="text" id="fechaLimite" class="form-control" readonly>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Proyecto</label>
                            <input type="text" id="proyecto" class="form-control" readonly>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Tipo</label>
                            <input type="text" id="tipo" class="form-control" readonly>
                        </div>
                        <div class="col-2">
                            <label class="form-label">Monto entregado</label>
                            <input type="text" id="montoSolicitud" class="form-control" readonly>
                        </div>
                        <div class="col-2">
                            <label class="form-label">Monto comprobado</label>
                            <input type="text" id="montoComprobado" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-grow-1 d-flex m-3 gap-3">
                <div id="visorArchivos" class="card flex-grow-1 w-100 h-100 d-flex flex-column">
                    <button type="button" id="btnFullscreen" class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2" title="Pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>

                    <div class="controlesPDF d-none d-flex justify-content-center align-items-center p-2">
                        <div class="col d-flex justify-content-center">
                            <button type="button" id="btnMenosZoom" class="btn btn-primary btn-sm" title="Alejar">
                                <i class="fa fa-search-minus"></i>
                            </button>
                            <span class="mx-5">Zoom</span>
                            <button type="button" id="btnMasZoom" class="btn btn-primary btn-sm" title="Acercar">
                                <i class="fa fa-search-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div id="visor" class="flex-grow-1 d-flex justify-content-center align-items-center h-0 overflow-auto">
                        <div id="cargandoArchivo" class="loading-overlay d-flex flex-column justify-content-center align-items-center">
                            <img src="/assets/img/wait.svg" alt="Cargando archivo" class="loading-image">
                            <h3 class="loading-text">Cargando archivo...</h3>
                        </div>
                        <div id="sinArchivo" class="alert alert-info m-3 d-none" role="alert">
                            <i class="fa fa-info-circle me-2">&nbsp;</i>No hay comprobantes por validar para esta solicitud.
                        </div>
                        <div id="errorArchivo" class="alert alert-danger m-3 d-none" role="alert">
                            <i class="fa fa-exclamation-triangle me-2">&nbsp;</i>Error al cargar el archivo.
                        </div>
                    </div>

                    <div class="controlesPDF d-none d-flex justify-content-center align-items-center p-2">
                        <div class="col d-flex justify-content-center">
                            <button type="button" id="btnPagAnt" class="btn btn-primary btn-sm" title="Anterior">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <span id="paginaActual" class="mx-5 page-info">1 / 1</span>
                            <button type="button" id="btnPagSig" class="btn btn-primary btn-sm" title="Siguiente">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card w-25">
                    <div class="card-header p-3 text-center">
                        <h5 id="noComprobante" class="card-title">Comprobante 1 de 1</h5>
                        <div class="col d-flex justify-content-between">
                            <button id="btnCompAnt" class="btn text-primary" disabled>
                                <i class="fa fa-chevron-left">&nbsp;</i>Anterior
                            </button>
                            <button id="btnCompSig" class="btn text-primary" disabled>
                                Siguiente<i class="fa fa-chevron-right">&nbsp;</i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="col">
                            <label class="form-label">Fecha captura</label>
                            <input type="text" id="fechaCaptura" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label class="form-label">Concepto</label>
                            <input type="text" id="concepto" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label class="form-label">Fecha comprobante</label>
                            <input type="text" id="fechaComprobante" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label class="form-label">Monto</label>
                            <input type="text" id="montoComprobante" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label class="form-label">Observaciones</label>
                            <textarea id="observaciones" class="form-control" readonly>
                            </textarea>
                        </div>
                    </div>
                    <div class="card-footer p-3 d-flex justify-content-between">
                        <button type="button" id="btnRechazarComprobante" class="btn btn-danger"><i class="fa fa-ban">&nbsp;</i>Rechazar</button>
                        <button type="button" id="btnAceptarComprobante" class="btn btn-success"><i class="fa fa-check">&nbsp;</i>Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>