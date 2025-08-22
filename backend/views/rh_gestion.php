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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 id="tituloModalPersona" class="address-title mb-2">Registrar nueva persona</h4>
                    <p class="address-subtitle">Complete los siguientes pasos para el registro</p>
                </div>
            </div>
            <div class="modal-body">
                <input type="hidden" id="personaId" name="personaId">

                <!-- Wizard Container -->
                <div class="bs-stepper wizard-icons-example">
                    <div class="bs-stepper-header">
                        <div class="step" data-target="#datos-personales">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-user"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Datos Personales</span>
                                    <span class="bs-stepper-subtitle">Información personal</span>
                                </span>
                            </button>
                        </div>
                        <div class="line"></div>
                        <div class="step" data-target="#datos-usuario">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-building-lock"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Usuario Sistema</span>
                                    <span class="bs-stepper-subtitle">Credenciales de acceso</span>
                                </span>
                            </button>
                        </div>
                        <div class="line"></div>
                        <div class="step" data-target="#confirmacion">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-check-circle"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Confirmación</span>
                                    <span class="bs-stepper-subtitle">Revisar y confirmar</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="bs-stepper-content">
                        <form onSubmit="return false">
                            <!-- Paso 1: Datos Personales -->
                            <div id="datos-personales" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Datos Personales</h6>
                                    <small>Ingrese la información personal básica.</small>
                                </div>
                                <div class="row">
                                    <!-- Sección de foto -->
                                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
                                        <div class="mb-3">
                                            <input type="file" id="fotoInput" accept="image/*" style="display: none;">
                                            <img src="/assets/img/misc/user.svg" alt="Foto de usuario" id="fotoPreview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #ddd;">
                                        </div>
                                        <button type="button" id="btnCambiarFoto" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-camera">&nbsp;</i>Cambiar Foto
                                        </button>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="nombre" class="form-label">Nombre(s)</label>
                                                <input type="text" id="nombre" name="nombre" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="apellido1" class="form-label">Apellido Paterno</label>
                                                <input type="text" id="apellido1" name="apellido1" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="apellido2" class="form-label">Apellido Materno</label>
                                                <input type="text" id="apellido2" name="apellido2" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="sexo" class="form-label">Sexo</label>
                                                <select id="sexo" name="sexo" class="form-select">
                                                    <option value="" selected disabled>Seleccione</option>
                                                    <option value="M">Masculino</option>
                                                    <option value="F">Femenino</option>
                                                </select>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="text" id="fechaNacimiento" name="fechaNacimiento" class="form-control">
                                                    <i class="input-group-text fa fa-calendar-days"></i>
                                                </div>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="rfc" class="form-label">RFC</label>
                                                <input type="text" id="rfc" name="rfc" class="form-control mayusculas" maxlength="13">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="curp" class="form-label">CURP</label>
                                                <input type="text" id="curp" name="curp" class="form-control mayusculas" maxlength="18">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between mt-4">
                                    <button class="btn btn-label-secondary btn-prev" disabled>
                                        <i class="bx bx-left-arrow-alt bx-sm ms-sm-n2 me-sm-2"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Anterior</span>
                                    </button>
                                    <button class="btn btn-primary btn-next" id="sigPersona">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">Siguiente</span>
                                        <i class="bx bx-right-arrow-alt bx-sm me-sm-n2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Paso 2: Datos de Usuario -->
                            <div id="datos-usuario" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Usuario del Sistema</h6>
                                    <small>Configure las credenciales de acceso al sistema.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="usuario" class="form-label">Usuario</label>
                                        <input type="text" id="usuario" name="usuario" class="form-control" maxlength="20">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pass" class="form-label">Contraseña</label>
                                        <input type="password" id="pass" name="pass" class="form-control" minlength="6">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="empresa" class="form-label">Empresa</label>
                                        <select id="empresa" name="empresa" class="form-select">
                                            <option value="" selected disabled>Seleccione una empresa</option>
                                            <?= $empresas ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="region" class="form-label">Región</label>
                                        <select id="region" name="region" class="form-select">
                                            <option value="" selected disabled>Seleccione una región</option>
                                            <?= $regiones ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sucursal" class="form-label">Sucursal</label>
                                        <select id="sucursal" name="sucursal" class="form-select">
                                            <option value="" selected disabled>Seleccione una sucursal</option>
                                            <?= $sucursales ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="perfil" class="form-label">Perfil de Usuario</label>
                                        <select id="perfil" name="perfil" class="form-select">
                                            <option value="" selected disabled>Seleccione un perfil</option>
                                            <option value="1">Administrador</option>
                                            <option value="2">Usuario</option>
                                            <option value="3">Consulta</option>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between mt-4">
                                    <button class="btn btn-primary btn-prev">
                                        <i class="bx bx-left-arrow-alt bx-sm ms-sm-n2 me-sm-2"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Anterior</span>
                                    </button>
                                    <button class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">Siguiente</span>
                                        <i class="bx bx-right-arrow-alt bx-sm me-sm-n2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Paso 3: Confirmación -->
                            <div id="confirmacion" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Confirmación de Registro</h6>
                                    <small>Revise la información antes de proceder con el registro.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <h6 class="mb-3">Fotografía</h6>
                                        <img id="resumenFoto" src="/assets/img/misc/user.svg" alt="Foto de usuario" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ddd;">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Resumen de Información</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="text-muted mb-2">Datos Personales:</h6>
                                                        <p class="mb-1"><strong>Nombre:</strong> <span id="resumenNombre">-</span></p>
                                                        <p class="mb-1"><strong>RFC:</strong> <span id="resumenRfc">-</span></p>
                                                        <p class="mb-1"><strong>CURP:</strong> <span id="resumenCurp">-</span></p>
                                                        <p class="mb-1"><strong>Fecha Nac.:</strong> <span id="resumenFechaNac">-</span></p>
                                                        <p class="mb-0"><strong>Sexo:</strong> <span id="resumenSexo">-</span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="text-muted mb-2">Usuario del Sistema:</h6>
                                                        <p class="mb-1"><strong>Usuario:</strong> <span id="resumenUsuario">-</span></p>
                                                        <p class="mb-0"><strong>Empresa:</strong> <span id="resumenEmpresa">-</span></p>
                                                        <p class="mb-1"><strong>Región:</strong> <span id="resumenRegion">-</span></p>
                                                        <p class="mb-1"><strong>Sucursal:</strong> <span id="resumenSucursal">-</span></p>
                                                        <p class="mb-1"><strong>Perfil:</strong> <span id="resumenPerfil">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3" role="alert">
                                            <h6 class="alert-heading mb-2">
                                                <i class="bx bx-info-circle me-2"></i>Confirmación de Registro
                                            </h6>
                                            <p class="mb-0">¿Está seguro que desea registrar esta persona con la información mostrada? Una vez confirmado, se creará el registro en el sistema.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between mt-4">
                                    <button class="btn btn-primary btn-prev">
                                        <i class="bx bx-left-arrow-alt bx-sm ms-sm-n2 me-sm-2"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Anterior</span>
                                    </button>
                                    <button class="btn btn-success btn-submit" id="btnGuardarPersona">
                                        <i class="bx bx-check me-2"></i>Confirmar Registro
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Wizard Container -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Información Personal</h5>
                                <div>
                                    <button type="button" id="btnHabilitarEdicion" class="btn btn-warning btn-sm me-2">
                                        <i class="fa fa-edit">&nbsp;</i>Editar
                                    </button>
                                    <button type="button" id="btnInhabilitarPersona" class="btn btn-danger btn-sm">
                                        <i class="fa fa-ban">&nbsp;</i>Desactivar
                                    </button>
                                </div>
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
                                            <th>Sucursal</th>
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

<!-- Modal para editar usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center w-100">
                    <h4 class="address-title mb-2">Editar Usuario</h4>
                    <p class="address-subtitle">Modifique los datos del usuario</p>
                </div>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUsuarioId" name="editUsuarioId">
                <input type="hidden" id="editPersonaId" name="editPersonaId">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="editUsuario" class="form-label">Usuario</label>
                        <input type="text" id="editUsuario" name="editUsuario" class="form-control" maxlength="20">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editPass" class="form-label">Nueva Contraseña (opcional)</label>
                        <input type="password" id="editPass" name="editPass" class="form-control" minlength="6" placeholder="Dejar vacío para mantener actual">
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editEmpresa" class="form-label">Empresa</label>
                        <select id="editEmpresa" name="editEmpresa" class="form-select">
                            <option value="" selected disabled>Seleccione una empresa</option>
                            <?= $empresas ?? '' ?>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editRegion" class="form-label">Región</label>
                        <select id="editRegion" name="editRegion" class="form-select">
                            <option value="" selected disabled>Seleccione una región</option>
                            <?= $regiones ?? '' ?>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editSucursal" class="form-label">Sucursal</label>
                        <select id="editSucursal" name="editSucursal" class="form-select">
                            <option value="" selected disabled>Seleccione una sucursal</option>
                            <?= $sucursales ?? '' ?>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editPerfil" class="form-label">Perfil de Usuario</label>
                        <select id="editPerfil" name="editPerfil" class="form-select">
                            <option value="" selected disabled>Seleccione un perfil</option>
                            <option value="1">Administrador</option>
                            <option value="2">Usuario</option>
                            <option value="3">Consulta</option>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="editEstatusUsuario" class="form-label">Estatus</label>
                        <select id="editEstatusUsuario" name="editEstatusUsuario" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarUsuario" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
<!-- / Modal para editar usuario -->