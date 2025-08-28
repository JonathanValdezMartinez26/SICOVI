<h4>Gestión de Capital Humano</h4>

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
            <button id="btnNuevaPersona" class="btn btn-info"><i class="fa fa-plus">&nbsp;</i>Registrar Colaborador</button>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="tablaPersonas" class="dt-responsive table border-top table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Foto</th>
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
                    <h4 id="tituloModalPersona" class="address-title mb-2">Registrar nuevo colaborador</h4>
                    <p class="address-subtitle">Complete los siguientes pasos para el registro</p>
                </div>
            </div>
            <div class="modal-body">
                <input type="hidden" id="personaId" name="personaId">

                <!-- Wizard Container -->
                <div class="bs-stepper wizard-registro-colaborador">
                    <div class="bs-stepper-header">
                        <div class="step justify-center" data-target="#datos-personales">
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
                        <div class="step" data-target="#datos-adicionales">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-notes-medical"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Datos Adicionales</span>
                                    <span class="bs-stepper-subtitle">Contacto y emergencias</span>
                                </span>
                            </button>
                        </div>
                        <div class="step" data-target="#datos-empresa">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-building"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Empresa</span>
                                    <span class="bs-stepper-subtitle">Información laboral</span>
                                </span>
                            </button>
                        </div>
                        <div class="step" data-target="#datos-nomina">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <i class="fa fa-building-lock"></i>
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">Nómina</span>
                                    <span class="bs-stepper-subtitle">Información de nómina</span>
                                </span>
                            </button>
                        </div>
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
                            <!-- Datos Personales -->
                            <div id="datos-personales" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Datos Personales</h6>
                                    <small>Ingrese la información personal básica.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                                        <div class="mb-3">
                                            <input type="file" id="fotoInput" accept="image/*" style="display: none;">
                                            <img src="/assets/img/misc/user.svg" alt="Foto de usuario" id="fotoPreview" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ddd;">
                                        </div>
                                        <button type="button" id="btnCambiarFoto" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-camera">&nbsp;</i>Cambiar Foto
                                        </button>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-md-4 mt-0">
                                                <label for="nombre" class="form-label">Nombre(s)</label>
                                                <input type="text" id="nombre" name="nombre" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="apellido1" class="form-label">Apellido Paterno</label>
                                                <input type="text" id="apellido1" name="apellido1" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="apellido2" class="form-label">Apellido Materno</label>
                                                <input type="text" id="apellido2" name="apellido2" class="form-control mayusculas" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mt-0">
                                                <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <div class="input-group input-group-merge cursor-pointer">
                                                    <input type="text" id="fechaNacimiento" name="fechaNacimiento" class="form-control" readonly>
                                                    <i class="input-group-text fa fa-calendar-days"></i>
                                                </div>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3 mt-0">
                                                <label for="sexo" class="form-label">Sexo</label>
                                                <select id="sexo" name="sexo" class="form-select">
                                                    <option value="" selected disabled>Seleccione</option>
                                                    <option value="M">Masculino</option>
                                                    <option value="F">Femenino</option>
                                                </select>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3 mt-0">
                                                <label for="rfc" class="form-label">RFC</label>
                                                <input type="text" id="rfc" name="rfc" class="form-control mayusculas" maxlength="13">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3 mt-0">
                                                <label for="curp" class="form-label">CURP</label>
                                                <input type="text" id="curp" name="curp" class="form-control mayusculas" maxlength="18">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mt-0">
                                                <label for="estadoCivil" class="form-label">Estado Civil</label>
                                                <select id="estadoCivil" name="estadoCivil" class="form-select">
                                                    <option value="" selected disabled>Seleccione</option>
                                                    <option value="SOLTERO">Soltero(a)</option>
                                                    <option value="CASADO">Casado(a)</option>
                                                    <option value="UNION_LIBRE">Unión Libre</option>
                                                    <option value="DIVORCIADO">Divorciado(a)</option>
                                                    <option value="VIUDO">Viudo(a)</option>
                                                    <option value="SEPARADO">Separado(a)</option>
                                                </select>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="nacionalidad" class="form-label">Nacionalidad</label>
                                                <input type="text" id="nacionalidad" name="nacionalidad" class="form-control mayusculas" value="MEXICANA" maxlength="50">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="nss" class="form-label">NSS (Número de Seguro Social)</label>
                                                <input type="text" id="nss" name="nss" class="form-control" maxlength="11" pattern="[0-9]*" placeholder="11 dígitos (opcional)">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-9 mt-0">
                                                <label for="calle" class="form-label">Calle y numero</label>
                                                <input type="text" id="calle" name="calle" class="form-control" maxlength="150" placeholder="Calle, número exterior e interior">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-3 mt-0">
                                                <label for="codigoPostal" class="form-label">Código Postal</label>
                                                <input type="text" id="codigoPostal" name="codigoPostal" class="form-control" maxlength="5" pattern="[0-9]*" placeholder="5 dígitos">
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="colonia" class="form-label">Colonia</label>
                                                <select id="colonia" name="colonia" class="form-select" disabled>
                                                    <option value="">Ingrese CP</option>
                                                </select>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="municipio" class="form-label">Municipio</label>
                                                <select id="municipio" name="municipio" class="form-select" disabled>
                                                    <option value="">Ingrese CP</option>
                                                </select>
                                                <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                            </div>
                                            <div class="col-md-4 mt-0">
                                                <label for="estado" class="form-label">Estado</label>
                                                <select id="estado" name="estado" class="form-select" disabled>
                                                    <option value="">Ingrese CP</option>
                                                </select>
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

                            <!-- Datos Adicionales -->
                            <div id="datos-adicionales" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Datos Adicionales</h6>
                                    <small>Contacto, emergencias y antecedentes médicos.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoTelefonoPrincipal" class="form-label">Numero de teléfono (Principal)</label>
                                        <input type="text" id="contactoTelefonoPrincipal" name="contactoTelefonoPrincipal" class="form-control" pattern="[0-9]{10}" maxlength="10">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoTelefonoAlterno" class="form-label">Numero de teléfono (Alterno)</label>
                                        <input type="text" id="contactoTelefonoAlterno" name="contactoTelefonoAlterno" class="form-control" pattern="[0-9]{10}" maxlength="10">
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoCorreoPrincipal" class="form-label">Correo electrónico</label>
                                        <input type="email" id="contactoCorreoPrincipal" name="contactoCorreoPrincipal" class="form-control" maxlength="100">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoEmergenciaNombre" class="form-label">Nombre Contacto de Emergencia</label>
                                        <input type="text" id="contactoEmergenciaNombre" name="contactoEmergenciaNombre" class="form-control" maxlength="100">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoEmergenciaParentesco" class="form-label">Parentesco</label>
                                        <input type="text" id="contactoEmergenciaParentesco" name="contactoEmergenciaParentesco" class="form-control" maxlength="50">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="contactoEmergenciaTelefono" class="form-label">Teléfono de Emergencia</label>
                                        <input type="text" id="contactoEmergenciaTelefono" name="contactoEmergenciaTelefono" class="form-control" pattern="[0-9]{10}" maxlength="10">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 mt-0">
                                        <label for="condicionesMedicas" class="form-label">Condiciones Médicas (opcional)</label>
                                        <textarea id="condicionesMedicas" name="condicionesMedicas" class="form-control" rows="3" maxlength="1000" placeholder="Alergias, enfermedades crónicas, medicación, etc."></textarea>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <label for="informacionAdicional" class="form-label">Información Adicional (opcional)</label>
                                        <textarea id="informacionAdicional" name="informacionAdicional" class="form-control" rows="3" maxlength="1000" placeholder="Notas adicionales relevantes"></textarea>
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

                            <!-- Datos de Empresa -->
                            <div id="datos-empresa" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Información Laboral</h6>
                                    <small>Configure la información de empresa y puesto de trabajo.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mt-0">
                                        <label for="empresaWizard" class="form-label">Registro Base</label>
                                        <select id="empresaWizard" name="empresaWizard" class="form-select">
                                            <option value="" selected disabled>Seleccione una empresa</option>
                                            <?= $empresas ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="sucursalWizard" class="form-label">Oficina Base</label>
                                        <select id="sucursalWizard" name="sucursalWizard" class="form-select">
                                            <option value="" selected disabled>Seleccione una sucursal</option>
                                            <?= $sucursales ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="regionWizard" class="form-label">Región</label>
                                        <select id="regionWizard" name="regionWizard" class="form-select" disabled>
                                            <option value="" selected disabled>Seleccione una región</option>
                                            <?= $regiones ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6 mt-0">
                                        <label for="jefeInmediato" class="form-label">Jefe Inmediato</label>
                                        <select id="jefeInmediato" name="jefeInmediato" class="form-select">
                                            <option value="" selected disabled>Seleccione un jefe</option>
                                            <!-- Se cargará vía AJAX -->
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6 mt-0">
                                        <label for="reporta" class="form-label">Reporta a</label>
                                        <select id="reporta" name="reporta" class="form-select">
                                            <option value="" selected disabled>Seleccione a quien reporta</option>
                                            <!-- Se cargará vía AJAX -->
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6 mt-0">
                                        <label for="puesto" class="form-label">Puesto</label>
                                        <select id="puesto" name="puesto" class="form-select">
                                            <option value="" selected disabled>Seleccione un puesto</option>
                                            <option value="1">Gerente General</option>
                                            <option value="2">Gerente de Área</option>
                                            <option value="3">Supervisor</option>
                                            <option value="4">Coordinador</option>
                                            <option value="5">Analista</option>
                                            <option value="6">Asistente</option>
                                            <option value="7">Auxiliar</option>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-6 mt-0">
                                        <label for="correoEmpresa" class="form-label">Correo Electrónico Empresa</label>
                                        <div id="correosContainer">
                                            <div class="input-group mb-2">
                                                <input type="email" name="correoEmpresa[]" class="form-control" placeholder="correo@empresa.com">
                                                <button type="button" class="btn btn-outline-success" onclick="agregarCorreo()">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mt-0">
                                        <label for="usuario" class="form-label">Usuario</label>
                                        <input type="text" id="usuario" name="usuario" class="form-control" maxlength="20" placeholder="Coloca aquí el RFC del colaborador">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label class="form-label" for="password">Contraseña</label>
                                        <div class="input-group input-group-merge">
                                            <input
                                                type="password"
                                                id="password"
                                                class="form-control"
                                                name="password"
                                                placeholder="Ingresa la contraseña"
                                                aria-describedby="password" />
                                            <i class="input-group-text cursor-pointer fa fa-eye-slash z-15" id="passwordIcon" onclick="togglePassword()"></i>
                                        </div>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
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
                                    <button class="btn btn-primary btn-next" id="sigEmpresa">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">Siguiente</span>
                                        <i class="bx bx-right-arrow-alt bx-sm me-sm-n2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Datos de Nomina -->
                            <div id="datos-nomina" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Nómina</h6>
                                    <small>Ingrese la información básica para la nómina</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mt-0">
                                        <label for="fechaIngreso" class="form-label">Fecha de ingreso</label>
                                        <input type="text" id="fechaIngreso" name="fechaIngreso" class="form-control">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-3 mt-0">
                                        <label for="nomina" class="form-label">Nómina</label>
                                        <select id="nomina" name="nomina" class="form-select">
                                            <option value="" disabled>Seleccione tipo</option>
                                            <option value="1">Ejecutivos</option>
                                            <option value="2">Empleados</option>
                                            <option value="3">Operarios</option>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-3 mt-0">
                                        <label for="tipoNomina" class="form-label">Tipo de Nómina</label>
                                        <select id="tipoNomina" name="tipoNomina" class="form-select">
                                            <option value="" disabled>Seleccione frecuencia</option>
                                            <option value="Semanal">Semanal</option>
                                            <option value="Quincenal">Quincenal</option>
                                            <option value="Mensual">Mensual</option>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-3 mt-0">
                                        <label for="numeroNomina" class="form-label"># de Nómina</label>
                                        <input type="text" id="numeroNomina" name="numeroNomina" class="form-control" maxlength="10">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mt-0">
                                        <label for="banco" class="form-label">Banco</label>
                                        <select id="banco" name="banco" class="form-select">
                                            <option value="" selected disabled>Seleccione un banco</option>
                                            <?= $bancos ?? '' ?>
                                        </select>
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="cuentaBancaria" class="form-label">Cuenta bancaria</label>
                                        <input type="text" id="cuentaBancaria" name="cuentaBancaria" class="form-control" maxlength="18" placeholder="18 dígitos">
                                        <div class="fv-message text-danger small" style="min-height: 1.25rem"></div>
                                    </div>
                                    <div class="col-md-4 mt-0">
                                        <label for="noTarjeta" class="form-label">No. de tarjeta</label>
                                        <input type="text" id="noTarjeta" name="noTarjeta" class="form-control" maxlength="16" placeholder="16 dígitos">
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

                            <!-- Paso 4: Confirmación -->
                            <div id="confirmacion" class="content">
                                <div class="content-header mb-4">
                                    <h6 class="mb-0">Confirmación de Registro</h6>
                                    <small>Revise la información antes de proceder con el registro.</small>
                                </div>
                                <div class="row">
                                    <!-- Foto y Datos Personales -->
                                    <div class="col-md-4">
                                        <div class="text-center mb-3">
                                            <h6 class="mb-3">Fotografía</h6>
                                            <img id="resumenFoto" src="/assets/img/misc/user.svg" alt="Foto de usuario" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ddd;">
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="mb-3 mt-3"><strong>Datos Personales</strong></h6>
                                                <p class="mb-1"><strong>Nombre:</strong> <span id="resumenNombre">-</span></p>
                                                <p class="mb-1"><strong>RFC:</strong> <span id="resumenRfc">-</span></p>
                                                <p class="mb-1"><strong>CURP:</strong> <span id="resumenCurp">-</span></p>
                                                <p class="mb-1"><strong>Fecha Nac.:</strong> <span id="resumenFechaNac">-</span></p>
                                                <p class="mb-1"><strong>Sexo:</strong> <span id="resumenSexo">-</span></p>
                                                <p class="mb-1"><strong>Estado Civil:</strong> <span id="resumenEstadoCivil">-</span></p>
                                                <p class="mb-1"><strong>Nacionalidad:</strong> <span id="resumenNacionalidad">-</span></p>
                                                <p class="mb-1"><strong>NSS:</strong> <span id="resumenNss">-</span></p>

                                                <h6 class="mb-3 mt-3"><strong>Domicilio</strong></h6>
                                                <p class="mb-1"><strong>Calle:</strong> <span id="resumenCalle">-</span></p>
                                                <p class="mb-1"><strong>Código Postal:</strong> <span id="resumenCodigoPostal">-</span></p>
                                                <p class="mb-1"><strong>Colonia:</strong> <span id="resumenColonia">-</span></p>
                                                <p class="mb-1"><strong>Municipio:</strong> <span id="resumenMunicipio">-</span></p>
                                                <p class="mb-1"><strong>Estado:</strong> <span id="resumenEstado">-</span></p>
                                                <h6 class="mb-3 mt-3"><strong>Contacto</strong></h6>
                                                <p class="mb-1"><strong>Tel. Principal:</strong> <span id="resumenContactoTelPrincipal">-</span></p>
                                                <p class="mb-1"><strong>Tel. Alterno:</strong> <span id="resumenContactoTelAlterno">-</span></p>
                                                <p class="mb-1"><strong>Correo:</strong> <span id="resumenContactoCorreo">-</span></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información Laboral -->
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0"><strong>Información Laboral</strong></h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Registro Base:</strong> <span id="resumenEmpresa">-</span></p>
                                                        <p class="mb-1"><strong>Región:</strong> <span id="resumenRegion">-</span></p>
                                                        <p class="mb-1"><strong>Oficina Base:</strong> <span id="resumenSucursal">-</span></p>
                                                        <p class="mb-1"><strong>Puesto:</strong> <span id="resumenPuesto">-</span></p>
                                                        <p class="mb-1"><strong>Jefe Inmediato:</strong> <span id="resumenJefeInmediato">-</span></p>
                                                        <p class="mb-1"><strong>Reporta a:</strong> <span id="resumenReporta">-</span></p>
                                                        <p class="mb-1"><strong>Correos Empresa:</strong> <span id="resumenCorreosEmpresa">-</span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Fecha de Ingreso:</strong> <span id="resumenFechaIngreso">-</span></p>
                                                        <p class="mb-1"><strong>Nómina:</strong> <span id="resumenNomina">-</span></p>
                                                        <p class="mb-1"><strong>Tipo Nómina:</strong> <span id="resumenTipoNomina">-</span></p>
                                                        <p class="mb-1"><strong># Nómina:</strong> <span id="resumenNumeroNomina">-</span></p>
                                                        <p class="mb-1"><strong>Banco:</strong> <span id="resumenBanco">-</span></p>
                                                        <p class="mb-1"><strong>Cuenta bancaria:</strong> <span id="resumenCuentaBancaria">-</span></p>
                                                        <p class="mb-0"><strong>No. Tarjeta:</strong> <span id="resumenNoTarjeta">-</span></p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h6 class="mt-3"><strong>Datos Adicionales</strong></h6>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <p class="mb-1"><strong>Contacto Emergencia:</strong> <span id="resumenContactoEmergenciaNombre">-</span></p>
                                                        <p class="mb-1"><strong>Parentesco:</strong> <span id="resumenContactoEmergenciaParentesco">-</span></p>
                                                        <p class="mb-1"><strong>Tel. Emergencia:</strong> <span id="resumenContactoEmergenciaTelefono">-</span></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <p class="mb-1"><strong>Condiciones Médicas:</strong><br><span id="resumenCondicionesMedicas">-</span></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <p class="mb-1"><strong>Información Adicional:</strong><br><span id="resumenInformacionAdicional">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Usuario del Sistema (card angosto) -->
                                        <div class="row mt-1">
                                            <div class="col-12 mx-auto">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><strong>Usuario del Sistema</strong></h6>
                                                    </div>
                                                    <div class="d-flex justify-content-around m-5">
                                                        <p class="mb-1"><strong>Usuario:</strong> <span id="resumenUsuario">-</span></p>
                                                        <p class="mb-0"><strong>Perfil:</strong> <span id="resumenPerfil">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mensaje de confirmación -->
                                <div class="row mt-3">
                                    <div class="col-12 mx-auto">
                                        <div class="alert alert-info" role="alert">
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