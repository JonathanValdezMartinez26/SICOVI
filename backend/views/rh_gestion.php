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
                                            <?= $perfiles ?? '' ?>
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
                    <h4 class="address-title mb-2">Detalle del colaborador</h4>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="col-md-2 d-flex flex-column justify-content-center align-items-center mb-10" style="width: 100%;">
                            <div class="mb-3">
                                <input type="file" id="fotoInput" accept="image/*" style="display: none;">
                                <img src="/assets/img/misc/user.svg" alt="Foto de usuario" id="detalleFoto" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ddd;">
                            </div>
                            <button type="button" id="btnCambiarFoto" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-camera">&nbsp;</i>Cambiar Foto
                            </button>
                        </div>
                        <!-- Nav pills vertical -->
                        <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" id="tab-personales" data-bs-toggle="pill" data-bs-target="#content-personales" type="button" role="tab" aria-controls="content-personales" aria-selected="true">Personales</button>
                            <button class="nav-link" id="tab-contacto" data-bs-toggle="pill" data-bs-target="#content-contacto" type="button" role="tab" aria-controls="content-contacto" aria-selected="false">Contacto</button>
                            <button class="nav-link" id="tab-empresa" data-bs-toggle="pill" data-bs-target="#content-empresa" type="button" role="tab" aria-controls="content-empresa" aria-selected="false">Empresa</button>
                            <button class="nav-link" id="tab-nomina" data-bs-toggle="pill" data-bs-target="#content-nomina" type="button" role="tab" aria-controls="content-nomina" aria-selected="false">Nómina</button>
                            <button class="nav-link" id="tab-adicionales" data-bs-toggle="pill" data-bs-target="#content-adicionales" type="button" role="tab" aria-controls="content-adicionales" aria-selected="false">Datos Adicionales</button>
                            <button class="nav-link" id="tab-usuarios" data-bs-toggle="pill" data-bs-target="#content-usuarios" type="button" role="tab" aria-controls="content-usuarios" aria-selected="false">Usuarios Asociados</button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <!-- Personales -->
                            <div class="tab-pane fade show active" id="content-personales" role="tabpanel" aria-labelledby="tab-personales">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabPersonales" data-tab="personales">Editar</button>
                                </div>
                                <input type="hidden" id="detallePersonaIdHidden">
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>ID:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detallePersonaId" class="form-control-plaintext" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>Nombre:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleNombre" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>Apellido Paterno:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleApellido1" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>Apellido Materno:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleApellido2" class="form-control mayusculas" maxlength="50" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>RFC:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleRfc" class="form-control mayusculas" maxlength="13" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>CURP:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleCurp" class="form-control mayusculas" maxlength="18" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3"><label class="form-label"><strong>NSS:</strong></label></div>
                                    <div class="col-9">
                                        <input type="text" id="detalleNSS" class="form-control" disabled maxlength="11">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5">
                                        <div class="col-4"><label class="form-label"><strong>F. Nacimiento:</strong></label></div>
                                        <div class="col-12">
                                            <input type="date" id="detalleFechaNacimiento" class="form-control" disabled>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="col-3"><label class="form-label"><strong>Sexo:</strong></label></div>
                                        <div class="col-12">
                                            <select id="detalleSexo" class="form-select" disabled>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="col-3"><label class="form-label"><strong>Estatus:</strong></label></div>
                                        <div class="col-12">
                                            <select id="detalleEstatus" class="form-select" disabled>
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contacto -->
                            <div class="tab-pane fade" id="content-contacto" role="tabpanel" aria-labelledby="tab-contacto">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabContacto" data-tab="contacto">Editar</button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Teléfono principal:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleTelefonoPrincipal" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Teléfono alterno:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleTelefonoAlterno" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Email:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleEmail" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-8">
                                        <div class="col-4"><label class="form-label"><strong>Calle:</strong></label></div>
                                        <input type="text" id="detalleCalle" class="form-control" disabled placeholder="Calle y número">
                                    </div>
                                    <div class="col-4">
                                        <div class="col-4"><label class="form-label"><strong>CP:</strong></label></div>
                                        <input type="text" id="detalleCP" class="form-control" disabled placeholder="Código Postal">
                                    </div>
                                    <div class="col-4">
                                        <div class="col-4"><label class="form-label"><strong>Colonia:</strong></label></div>
                                        <input type="text" id="detalleColonia" class="form-control" disabled placeholder="Colonia">
                                    </div>
                                    <div class="col-4">
                                        <div class="col-4"><label class="form-label"><strong>Municipio:</strong></label></div>
                                        <input type="text" id="detalleMunicipio" class="form-control" disabled placeholder="Municipio">
                                    </div>
                                    <div class="col-4">
                                        <div class="col-4"><label class="form-label"><strong>Estado:</strong></label></div>
                                        <input type="text" id="detalleEstado" class="form-control" disabled placeholder="Estado">
                                    </div>
                                </div>
                            </div>

                            <!-- Empresa -->
                            <div class="tab-pane fade" id="content-empresa" role="tabpanel" aria-labelledby="tab-empresa">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabEmpresa" data-tab="empresa">Editar</button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Empresa:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleEmpresa" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Región:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleRegion" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Sucursal:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleSucursal" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Puesto:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detallePuesto" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Jefe Directo:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleJefeDirecto" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- Nómina -->
                            <div class="tab-pane fade" id="content-nomina" role="tabpanel" aria-labelledby="tab-nomina">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabNomina" data-tab="nomina">Editar</button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Ingreso:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleIngreso" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Nomina:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleNomina" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Tipo:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleTipoNomina" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Numero:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleNumeroNomina" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Banco:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleBanco" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Cuenta:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleCuenta" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Tarjeta:</strong></label></div>
                                    <div class="col-8">
                                        <input type="text" id="detalleTarjeta" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Adicionales -->
                            <div class="tab-pane fade" id="content-adicionales" role="tabpanel" aria-labelledby="tab-adicionales">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabAdicionales" data-tab="adicionales">Editar</button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="col-6"><label class="form-label"><strong>Contacto de emergencia:</strong></label></div>
                                        <input type="text" id="detalleContactoEmergencia" class="form-control" disabled>
                                    </div>
                                    <div class="col-3">
                                        <div class="col-4"><label class="form-label"><strong>Parentesco:</strong></label></div>
                                        <input type="text" id="detalleParentescoCE" class="form-control" disabled>
                                    </div>
                                    <div class="col-3">
                                        <div class="col-4"><label class="form-label"><strong>Telefono:</strong></label></div>
                                        <input type="text" id="detalleTelefonoCE" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Condiciones médicas:</strong></label></div>
                                    <div class="col-8">
                                        <textarea id="detalleCondicionesMedicas" class="form-control" rows="2" disabled></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4"><label class="form-label"><strong>Información adicional:</strong></label></div>
                                    <div class="col-8">
                                        <textarea id="detalleInformacionAdicional" class="form-control" rows="2" disabled></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Usuarios Asociados -->
                            <div class="tab-pane fade" id="content-usuarios" role="tabpanel" aria-labelledby="tab-usuarios">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-tab" id="btnEditTabUsuarios" data-tab="usuarios">Editar</button>
                                </div>
                                <div class="card-header mb-2">
                                    <h5 class="card-title mb-0">Usuarios Asociados</h5>
                                </div>
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

<script>
    // Gestión de edición por pestaña en modalDetallePersona
    (function() {
        const tabs = ['personales', 'contacto', 'empresa', 'nomina', 'adicionales', 'usuarios'];
        const state = {};

        // helper: obtener inputs de una tab
        function getTabFields(tab) {
            switch (tab) {
                case 'personales':
                    return Array.from(document.querySelectorAll('#content-personales input, #content-personales select'));
                case 'contacto':
                    return Array.from(document.querySelectorAll('#content-contacto input, #content-contacto textarea'));
                case 'empresa':
                    return Array.from(document.querySelectorAll('#content-empresa input'));
                case 'nomina':
                    return Array.from(document.querySelectorAll('#content-nomina input'));
                case 'adicionales':
                    return Array.from(document.querySelectorAll('#content-adicionales input, #content-adicionales textarea'));
                case 'usuarios':
                    // usuarios es una tabla; no hay campos editables por ahora
                    return [];
            }
            return [];
        }

        function snapshot(tab) {
            const fields = getTabFields(tab);
            return fields.map(f => ({
                id: f.id,
                value: f.value,
                disabled: f.disabled
            }));
        }

        function restore(tab, snap) {
            if (!snap) return;
            snap.forEach(s => {
                const el = document.getElementById(s.id);
                if (el) el.value = s.value;
            });
        }

        function setDisabled(tab, disabled) {
            getTabFields(tab).forEach(f => {
                f.disabled = disabled;
            });
        }

        function hasChanges(tab, snap) {
            if (!snap) return false;
            return getTabFields(tab).some(f => {
                const s = snap.find(x => x.id === f.id);
                return s && s.value !== f.value;
            });
        }

        // Inicializar estado por tab
        tabs.forEach(t => {
            state[t] = {
                mode: 'view',
                snap: null
            };
        });

        // Botones editar
        document.querySelectorAll('.btn-edit-tab').forEach(btn => {
            const tab = btn.dataset.tab;
            btn.addEventListener('click', function(e) {
                const s = state[tab];
                if (s.mode === 'view') {
                    // entrar en edición
                    s.snap = snapshot(tab);
                    s.mode = 'edit';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                    btn.textContent = 'Cancelar';
                    setDisabled(tab, false);
                } else if (s.mode === 'edit') {
                    // cancelar edición (si no hay cambios) o pedir confirmación
                    if (hasChanges(tab, s.snap)) {
                        // cambiar a estado 'pending-save'
                        s.mode = 'pending';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');
                        btn.textContent = 'Guardar';
                    } else {
                        // revertir y volver a view
                        restore(tab, s.snap);
                        s.mode = 'view';
                        s.snap = null;
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                        btn.textContent = 'Editar';
                        setDisabled(tab, true);
                    }
                } else if (s.mode === 'pending') {
                    // Simulación de guardado: aplicar y volver a view
                    // aquí deberías disparar la llamada AJAX real; por ahora solo resetear estado
                    s.mode = 'view';
                    s.snap = null;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                    btn.textContent = 'Editar';
                    setDisabled(tab, true);
                }
            });
        });

        // Detectar cambios en inputs para cada tab y actualizar texto del botón
        tabs.forEach(tab => {
            getTabFields(tab).forEach(f => {
                f.addEventListener('input', () => {
                    const s = state[tab];
                    const btn = document.querySelector('.btn-edit-tab[data-tab="' + tab + '"]');
                    if (!s) return;
                    if (s.mode === 'edit') {
                        if (hasChanges(tab, s.snap)) {
                            // mostrar guardar
                            s.mode = 'pending';
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-success');
                            btn.textContent = 'Guardar';
                        } else {
                            // volver a cancelar
                            s.mode = 'edit';
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-primary');
                            btn.textContent = 'Cancelar';
                        }
                    } else if (s.mode === 'pending') {
                        if (!hasChanges(tab, s.snap)) {
                            s.mode = 'edit';
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-primary');
                            btn.textContent = 'Cancelar';
                        }
                    }
                });
            });
        });

        // Al cambiar de tab: si hay edición pendiente en la tab actual, revertir cambios
        const pillButtons = document.querySelectorAll('#v-pills-tab button[data-bs-toggle="pill"]');
        pillButtons.forEach(btn => {
            btn.addEventListener('show.bs.tab', function(e) {
                // obtener la tab que se está ocultando
                const prev = document.querySelector('#v-pills-tab button.active');
                if (prev) {
                    const prevTab = prev.id.replace('tab-', '');
                    const s = state[prevTab];
                    if (s && s.mode !== 'view') {
                        // revertir cambios no guardados
                        restore(prevTab, s.snap);
                        setDisabled(prevTab, true);
                        s.mode = 'view';
                        s.snap = null;
                        const prevBtn = document.querySelector('.btn-edit-tab[data-tab="' + prevTab + '"]');
                        if (prevBtn) {
                            prevBtn.className = 'btn btn-sm btn-outline-primary btn-edit-tab';
                            prevBtn.textContent = 'Editar';
                        }
                    }
                }
            });
        });

        // Al abrir el modal, deshabilitar todos los campos
        const modal = document.getElementById('modalDetallePersona');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                tabs.forEach(t => setDisabled(t, true));
                // reset botones
                document.querySelectorAll('.btn-edit-tab').forEach(b => {
                    b.className = 'btn btn-sm btn-outline-primary btn-edit-tab';
                    b.textContent = 'Editar';
                });
                // take initial snapshots
                tabs.forEach(t => state[t].snap = snapshot(t));
            });
        }

    })();
</script>