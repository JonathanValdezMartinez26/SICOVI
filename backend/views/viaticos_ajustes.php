<h4>Entrega de Viáticos y devolución de Gastos</h4>

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
                    <th>Solicitante</th>
                    <th>Diferencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para ver solicitud -->
<div class="modal fade" id="modalVerAjuste" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 class="address-title mb-2">Ajuste de diferencia en gastos</h4>
                    <p class="address-subtitle"></p>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-2">
                        <img id="verFotoUsuario" src="/assets/img/misc/user.svg" alt="Foto del usuario" class="rounded-circle" style="width: 100px; height: 100px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Solicitante</label>
                        <input type="text" id="verSolicitante" class="form-control" readonly>
                        <input type="hidden" id="verSolicitudId">
                    </div>
                    <div class="col-4">
                        <label class="form-label">Sucursal de ajuste</label>
                        <select id="verSucursal" class="form-select" readonly>
                            <?= $sucursales ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-4">
                        <label class="form-label">Tipo de Solicitud</label>
                        <input type="text" id="verTipoSol" class="form-control" readonly>
                        <input type="hidden" id="verTipoSolId">
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Fecha de Finalización</label>
                        <input type="text" id="verFechaFinalizado" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Tipo de Diferencia</label>
                        <input type="text" id="verTipoDiferencia" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Monto Entregado</label>
                        <input type="text" id="verMontoEntregado" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Monto Comprobado</label>
                        <input type="text" id="verMontoComprobado" class="form-control" readonly>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Diferencia</label>
                        <input type="text" id="verMontoDiferencia" class="form-control" readonly>
                    </div>
                    <div class="form-group col-12">
                        <label for="observacionesAjuste" class="form-label">Observaciones</label>
                        <textarea id="observacionesAjuste" name="observacionesAjuste" class="form-control mayusculas" placeholder="Observaciones al entregar. Ej.: Se entrego un monto menor debido a..." rows="2" maxlength="500"></textarea>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelar" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                <button type="button" id="ajustar" class="btn btn-primary">Ajustar</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para ver solicitud -->