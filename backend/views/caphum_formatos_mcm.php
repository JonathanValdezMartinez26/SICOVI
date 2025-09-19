<h4>Formatos cargados para la empresa MCM</h4>

<div class="card">
    <div class="row justify-content-between m-4">
        <div class="col-4">
            <label for="filtroFechas" class="form-label">Rango de fechas mostrado</label>
            <div class="input-group input-group-merge">
                <input type="text" id="filtroFechas" class="form-control cursor-pointer" readonly>
                <i class="input-group-text fa fa-calendar-days"></i>
                <button id="btnBuscarFormatos" class="btn btn-outline-primary">Actualizar</button>
            </div>
        </div>
        <div class="col-4 d-flex align-self-end justify-content-end">
            <button id="btnAgregar" class="btn btn-info"><i class="fa fa-plus">&nbsp;</i>Subir nuevo formato</button>
            <input type="hidden" id="solActivas" value="<?= $activas; ?>">
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="historialFormatos" class="dt-responsive table border-top">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Nombre del archivo</th>
                    <th>Fecha de carga</th>
                    <th>Vigencia</th>
                    <th>Permisos y empresas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para subir un nuevo formato -->
<div class="modal fade" id="modalSubirFormato" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 class="address-title mb-2">Agregar nuevo formato al repositorio MCM</h4>
                    <p class="address-subtitle">Capture los datos solicitados</p>
                </div>
            </div>
            <div class="form-group col-12 text-center">
                <label id="notificacionEntrega" class="text-danger">Este documento estará disponible unicamente en el sistema SICAFIN</label>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-12">
                        <label for="nombre" class="form-label">Nombre del archivo (Así sera publicado) *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control mayusculas" placeholder="Proyecto o actividad a cubrir. Ej.: FORMATO PARA EL ALTA DE VACACIONES 2025" maxlength="100">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-6">
                        <label for="fechasVigencia" class="form-label">Vigencia del formato en Sistema *</label>
                        <div class="input-group input-group-merge cursor-pointer">
                            <input type="text" id="fechasVigencia" name="fechasVigencia" class="form-control cursor-pointer" readonly>
                            <span class="input-group-text">
                                <i class="fa fa-calendar-days"></i>
                            </span>
                        </div>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-6">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select id="categoria" name="categoria" class="form-select">
                            <?= $categorias ?>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label for="permisos" class="form-label">¿Quién tiene acceso?</label><br>
                        <select id="permisos" name="permisos" class="form-select">
                            <option value="0">Todos</option>
                            <option value="1">Solamente gerentes</option>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-8">
                        <label for="archivoFormato" class="form-label">Archivo a subir *</label>
                        <input type="file" id="archivoFormato" name="archivoFormato" class="form-control" accept=".pdf">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelaSubirFormato" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                    <button type="button" id="subirFormato" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para subir un nuevo formato -->