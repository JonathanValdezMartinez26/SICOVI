<h4>Gestión de Recursos Humanos</h4>

<div class="card">
    <div class="row justify-content-between m-4">
        <div class="col-4">
            <label class="form-label">Búsqueda general</label>
            <div class="input-group input-group-merge">
                <input type="text" id="filtroGeneral" class="form-control" placeholder="Buscar por nombre, RFC, CURP...">
                <i class="input-group-text fa fa-magnifying-glass"></i>
                <button id="btnBuscar" class="btn btn-outline-primary">Buscar</button>
            </div>
        </div>
        <div class="col-4 d-flex align-self-end justify-content-end">
            <button id="btnNuevaPersona" class="btn btn-info"><i class="fa fa-plus">&nbsp;</i>Registrar Persona</button>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="tablaPersonas" class="dt-responsive table border-top table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>RFC</th>
                    <th>CURP</th>
                    <th>Fecha Nacimiento</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para registrar/editar persona -->
<div class="modal fade" id="modalPersona" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 id="tituloModalPersona" class="address-title mb-2">Registrar nueva persona</h4>
                    <p class="address-subtitle">Capture los datos personales y de usuario</p>
                </div>
            </div>
            <div class="modal-body">
                <input type="hidden" id="personaId" name="personaId">
                <div class="row">
                    <div class="col-md-6 d-flex flex-column justify-content-around align-items-center">
                        <div class="mb-3">
                            <input type="file" id="fotoInput" accept="image/*" style="display: none;">
                            <img src="/assets/img/misc/user.svg" alt="Foto de usuario" id="fotoPreview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #ddd;">
                        </div>
                        <button type="button" id="btnCambiarFoto" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-camera">&nbsp;</i>Cambiar Foto
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label">Nombre(s)</label>
                            <input type="text" id="nombre" name="nombre" class="form-control mayusculas" maxlength="50">
                            <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="apellido1" class="form-label">Apellido Paterno</label>
                            <input type="text" id="apellido1" name="apellido1" class="form-control mayusculas" maxlength="50">
                            <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="apellido2" class="form-label">Apellido Materno</label>
                            <input type="text" id="apellido2" name="apellido2" class="form-control mayusculas" maxlength="50">
                            <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="form-group col-md-3">
                        <label for="sexo" class="form-label">Sexo</label>
                        <select id="sexo" name="sexo" class="form-select">
                            <option value="" selected disabled>Seleccione</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" id="fechaNacimiento" name="fechaNacimiento" class="form-control">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="rfc" class="form-label">RFC</label>
                        <input type="text" id="rfc" name="rfc" class="form-control mayusculas" maxlength="13">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="curp" class="form-label">CURP</label>
                        <input type="text" id="curp" name="curp" class="form-control mayusculas" maxlength="18">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="text-center mb-3">Usuario en sistema</h5>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" maxlength="20">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="pass" class="form-label">Contraseña</label>
                        <input type="password" id="pass" name="pass" class="form-control" minlength="6">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <!-- Nuevos selects para Región y Sucursal -->
                    <div class="form-group col-md-6">
                        <label for="region" class="form-label">Región</label>
                        <select id="region" name="region" class="form-select">
                            <?= $regiones ?>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sucursal" class="form-label">Sucursal</label>
                        <select id="sucursal" name="sucursal" class="form-select">
                            <?= $sucursales ?>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarPersona" class="btn btn-primary">Registrar</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para registrar/editar persona -->

<!-- Modal para detalle de persona -->
<div class="modal fade" id="modalDetallePersona" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 class="address-title mb-2">Detalle de Persona</h4>
                    <p class="address-subtitle">Información personal y usuarios asociados</p>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <!-- Sección de foto -->
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <img src="/assets/img/misc/user.svg" alt="Foto de usuario" id="detalleFotoPreview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #ddd;">
                                </div>
                                <input type="file" id="detalleFotoInput" accept="image/*" style="display: none;">
                                <button type="button" id="btnCambiarFotoDetalle" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="fa fa-camera">&nbsp;</i>Cambiar Foto
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <input type="hidden" id="detallePersonaIdHidden">
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>ID:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detallePersonaId" class="form-control-plaintext" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Nombre:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleNombre" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Apellido Paterno:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleApellido1" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Apellido Materno:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleApellido2" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>RFC:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleRfc" class="form-control mayusculas" maxlength="13" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>CURP:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleCurp" class="form-control mayusculas" maxlength="18" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>F. Nacimiento:</strong></label></div>
                                    <div class="col-8">
                                        <input type="date" id="detalleFechaNacimiento" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Sexo:</strong></label></div>
                                    <div class="col-8">
                                        <select id="detalleSexo" class="form-select" disabled>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Estatus:</strong></label></div>
                                    <div class="col-8">
                                        <span id="detalleEstatus"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <button type="button" id="btnHabilitarEdicion" class="btn btn-warning">
                                    <i class="fa fa-edit">&nbsp;</i>Editar
                                </button>
                                <button type="button" id="btnInhabilitarPersona" class="btn btn-danger">
                                    <i class="fa fa-ban">&nbsp;</i>Desactivar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Usuarios Asociados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaUsuariosDetalle" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para detalle de persona -->