<?php

namespace Controllers;

use Core\Controller;
use Models\CapHum as CapHumDAO;

class CapHum extends Controller
{
    public function GestionCapHum()
    {
        $script = <<<HTML
            <script>
                const tabla = "#tablaPersonas"
                let valPersona = null
                let modalPersona = null
                let wizardPersona = null
                let modoEdicion = false
                let datosOriginales = {}
                let hayCambios = false
                let cargandoEmpleados = false

                const camposPersona = {
                    personales: {
                        nombre: {
                            elemento: "nombre",
                            valor: () => $("#nombre").val(),
                            texto: () => $("#nombre").val(),
                            validacion: () => $("#nombre").val().trim() !== "",
                            mensaje: "Ingrese un nombre."
                        },
                        apellido1: {
                            elemento: "apellido1",
                            valor: () => $("#apellido1").val(),
                            texto: () => $("#apellido1").val(),
                            validacion: () => $("#apellido1").val().trim() !== "",
                            mensaje: "Ingrese un apellido."
                        },
                        apellido2: {
                            elemento: "apellido2",
                            valor: () => $("#apellido2").val(),
                            texto: () => $("#apellido2").val(),
                            validacion: () => true,
                            mensaje: "Ingrese un apellido."
                        },
                        rfc: {
                            elemento: "rfc",
                            valor: () => $("#rfc").val(),
                            texto: () => $("#rfc").val(),
                            validacion: () => $("#rfc").val().trim() !== "",
                            mensaje: "Ingrese un RFC."
                        },
                        curp: {
                            elemento: "curp",
                            valor: () => $("#curp").val(),
                            texto: () => $("#curp").val(),
                            validacion: () => $("#curp").val().trim() !== "",
                            mensaje: "Ingrese un CURP."
                        },
                        fechaNacimiento: {
                            elemento: "fechaNacimiento",
                            valor: () => getInputFechas("#fechaNacimiento", false, true),
                            texto: () => $("#fechaNacimiento").val(),
                            validacion: () => $("#fechaNacimiento").val() !== '',
                            mensaje: "Ingrese la fecha de nacimiento."
                        },
                        sexo: {
                            elemento: "sexo",
                            valor: () => $("#sexo option:selected").val(),
                            texto: () => $("#sexo option:selected").text(),
                            validacion: () => $("#sexo option:selected").val() !== "",
                            mensaje: "Seleccione un sexo."
                        },
                        estadoCivil: {
                            elemento: "estadoCivil",
                            valor: () => $("#estadoCivil option:selected").val(),
                            texto: () => $("#estadoCivil option:selected").text(),
                            validacion: () => $("#estadoCivil option:selected").val() !== "",
                            mensaje: "Seleccione un estado civil."
                        },
                        nacionalidad: {
                            elemento: "nacionalidad",
                            valor: () => $("#nacionalidad").val(),
                            texto: () => $("#nacionalidad").val(),
                            validacion: () => $("#nacionalidad").val().trim() !== "",
                            mensaje: "Ingrese una nacionalidad."
                        },
                        nss: {
                            elemento: "nss",
                            valor: () => $("#nss").val(),
                            texto: () => $("#nss").val(),
                            validacion: () => $("#nss").val().trim() === "" || ($("#nss").val().trim() !== "" && $("#nss").val().trim().length === 11),
                            mensaje: "El NSS debe tener 11 dígitos."
                        },
                        infonavit: {
                            elemento: "infonavit",
                            valor: () => $("#infonavit").is(":checked") ? 1 : 0,
                            texto: () => $("#infonavit").is(":checked") ? "Sí" : "No",
                            validacion: () => true,
                            mensaje: ""
                        },
                        calle: {
                            elemento: "calle",
                            valor: () => $("#calle").val(),
                            texto: () => $("#calle").val(),
                            validacion: () => $("#calle").val().trim() !== "",
                            mensaje: "Ingrese la calle y el número."
                        },
                        codigoPostal: {
                            elemento: "codigoPostal",
                            valor: () => $("#codigoPostal").val(),
                            texto: () => $("#codigoPostal").val(),
                            validacion: () => $("#codigoPostal").val().trim() !== "" && $("#codigoPostal").val().trim().length === 5,
                            mensaje: "El CP debe tener 5 dígitos."
                        },
                        colonia: {
                            elemento: "colonia",
                            valor: () => $("#colonia option:selected").val(),
                            texto: () => $("#colonia option:selected").text(),
                            validacion: () => $("#colonia option:selected").val() !== "",
                            mensaje: "Seleccione una colonia."
                        },
                        municipio: {
                            elemento: "municipio",
                            valor: () => $("#municipio option:selected").val(),
                            texto: () => $("#municipio option:selected").text(),
                            validacion: () => $("#municipio option:selected").val() !== "",
                            mensaje: "Ingrese un CP."
                        },
                        estado: {
                            elemento: "estado",
                            valor: () => $("#estado option:selected").val(),
                            texto: () => $("#estado option:selected").text(),
                            validacion: () => $("#estado option:selected").val() !== "",
                            mensaje: "Ingrese un CP."
                        }
                    },
                    contacto: {
                        telefonoPrincipal: {
                            elemento: "telefonoPrincipal",
                            valor: () => $("#telefonoPrincipal").val(),
                            texto: () => $("#telefonoPrincipal").val(),
                            validacion: () => $("#telefonoPrincipal").val().trim() !== "",
                            mensaje: "Ingrese un número de teléfono."
                        },
                        telefonoAlterno: {
                            elemento: "telefonoAlterno",
                            valor: () => $("#telefonoAlterno").val(),
                            texto: () => $("#telefonoAlterno").val(),
                            validacion: () => true,
                            mensaje: "Ingrese un número de teléfono."
                        },
                        correoPrincipal: {
                            elemento: "correoPrincipal",
                            valor: () => $("#correoPrincipal").val(),
                            texto: () => $("#correoPrincipal").val(),
                            validacion: () => true,
                            mensaje: "Ingrese un correo electrónico."
                        },
                        contactoEmergenciaNombre: {
                            elemento: "contactoEmergenciaNombre",
                            valor: () => $("#contactoEmergenciaNombre").val(),
                            texto: () => $("#contactoEmergenciaNombre").val(),
                            validacion: () => true,
                            mensaje: "Ingrese un nombre de contacto de emergencia."
                        },
                        contactoEmergenciaParentesco: {
                            elemento: "contactoEmergenciaParentesco",
                            valor: () => $("#contactoEmergenciaParentesco option:selected").val(),
                            texto: () => $("#contactoEmergenciaParentesco option:selected").text(),
                            validacion: () => {
                                if ($("#contactoEmergenciaNombre").val().trim() !== "") {
                                    return $("#contactoEmergenciaParentesco option:selected").val() !== "";
                                }
                                return true;
                            },
                            mensaje: "Ingrese un parentesco de contacto de emergencia."
                        },
                        contactoEmergenciaTelefono: {
                            elemento: "contactoEmergenciaTelefono",
                            valor: () => $("#contactoEmergenciaTelefono").val(),
                            texto: () => $("#contactoEmergenciaTelefono").val(),
                            validacion: () => {
                                if ($("#contactoEmergenciaNombre").val().trim() !== "") {
                                    return $("#contactoEmergenciaTelefono").val().trim() !== "";
                                }
                                return true;
                            },
                            mensaje: "Ingrese un número de teléfono de contacto de emergencia."
                        },
                        condicionesMedicas: {
                            elemento: "condicionesMedicas",
                            valor: () => $("#condicionesMedicas").val(),
                            texto: () => $("#condicionesMedicas").val(),
                            validacion: () => true,
                            mensaje: "Ingrese las condiciones médicas."
                        },
                        informacionAdicional: {
                            elemento: "informacionAdicional",
                            valor: () => $("#informacionAdicional").val(),
                            texto: () => $("#informacionAdicional").val(),
                            validacion: () => true,
                            mensaje: "Ingrese información adicional."
                        }
                    },
                    laborales: {
                        empresa: {
                            elemento: "empresa",
                            valor: () => $("#empresa option:selected").val(),
                            texto: () => $("#empresa option:selected").text(),
                            validacion: () => $("#empresa option:selected").val() !== "",
                            mensaje: "Seleccione una empresa."
                        },
                        region: {
                            elemento: "region",
                            valor: () => $("#region option:selected").val(),
                            texto: () => $("#region option:selected").text(),
                            validacion: () => $("#region option:selected").val() !== "",
                            mensaje: "Seleccione una región."
                        },
                        sucursal: {
                            elemento: "sucursal",
                            valor: () => $("#sucursal option:selected").val(),
                            texto: () => $("#sucursal option:selected").text(),
                            validacion: () => $("#sucursal option:selected").val() !== "",
                            mensaje: "Seleccione una sucursal."
                        },
                        jefeInmediato: {
                            elemento: "jefeInmediato",
                            valor: () => $("#jefeInmediato option:selected").val(),
                            texto: () => $("#jefeInmediato option:selected").text(),
                            validacion: () => $("#jefeInmediato option:selected").val() !== "",
                            mensaje: "Seleccione un jefe inmediato."
                        },
                        reporta: {
                            elemento: "reporta",
                            valor: () => $("#reporta option:selected").val(),
                            texto: () => $("#reporta option:selected").text(),
                            validacion: () => true, //$("#reporta option:selected").val() !== "",
                            mensaje: "Seleccione a quién reportar."
                        },
                        puesto: {
                            elemento: "puesto",
                            valor: () => $("#puesto option:selected").val(),
                            texto: () => $("#puesto option:selected").text(),
                            validacion: () => $("#puesto option:selected").val() !== "",
                            mensaje: "Seleccione un puesto."
                        },
                        correoLaboral: {
                            elemento: "correoLaboral",
                            valor: () => {
                                    const correos = []
                                    $('input[name="correoEmpresa[]"]').each(function() {
                                        const valor = $(this).val().trim()
                                        if (valor) correos.push(valor)
                                    })
                                    return correos.join(',')
                                },
                            texto: () => {
                                const correos = []
                                $('input[name="correoEmpresa[]"]').each(function() {
                                    const valor = $(this).val().trim()
                                    if (valor) correos.push(valor)
                                })
                                return correos.join(', ')
                            },
                            validacion: () => true,
                            mensaje: "Ingrese al menos un correo electrónico."
                        },
                        usuario: {
                            elemento: "usuario",
                            valor: () => $("#usuario").val(),
                            texto: () => $("#usuario").val(),
                            validacion: () => $("#usuario").val() !== "",
                            mensaje: "Ingrese un nombre de usuario."
                        },
                        password: {
                            elemento: "password",
                            valor: () => $("#password").val(),
                            texto: () => $("#password").val(),
                            validacion: () => $("#password").val().length >= 8,
                            mensaje: "Ingrese una contraseña de al menos 8 caracteres."
                        },
                        perfil: {
                            elemento: "perfil",
                            valor: () => $("#perfil option:selected").val(),
                            texto: () => $("#perfil option:selected").text(),
                            validacion: () => $("#perfil option:selected").val() !== "",
                            mensaje: "Seleccione un perfil."
                        }
                    },
                    nomina: {
                        fechaIngreso: {
                            elemento: "fechaIngreso",
                            valor: () => getInputFechas("#fechaIngreso", false, true),
                            texto: () => $("#fechaIngreso").val(),
                            validacion: () => $("#fechaIngreso").val() !== "",
                            mensaje: "Seleccione una fecha de ingreso."
                        },
                        proveedor: {
                            elemento: "proveedor",
                            valor: () => $("#proveedor option:selected").val(),
                            texto: () => $("#proveedor option:selected").text(),
                            validacion: () => $("#proveedor option:selected").val() !== "",
                            mensaje: "Seleccione un proveedor."
                        },
                        tipoNomina: {
                            elemento: "tipoNomina",
                            valor: () => $("#tipoNomina option:selected").val(),
                            texto: () => $("#tipoNomina option:selected").text(),
                            validacion: () => $("#tipoNomina option:selected").val() !== "",
                            mensaje: "Seleccione un tipo de nómina."
                        },
                        numeroNomina: {
                            elemento: "numeroNomina",
                            valor: () => $("#numeroNomina").val(),
                            texto: () => $("#numeroNomina").val(),
                            validacion: () => $("#numeroNomina").val() !== "",
                            mensaje: "Ingrese un número de nómina."
                        },
                        banco: {
                            elemento: "banco",
                            valor: () => $("#banco").val(),
                            texto: () => $("#banco option:selected").text(),
                            validacion: () => $("#banco option:selected").val() !== "",
                            mensaje: "Seleccione un banco."
                        },
                        cuentaBancaria: {
                            elemento: "cuentaBancaria",
                            valor: () => $("#cuentaBancaria").val(),
                            texto: () => $("#cuentaBancaria").val(),
                            validacion: () => $("#cuentaBancaria").val() !== "",
                            mensaje: "Ingrese una cuenta bancaria válida."
                        },
                        tarjeta: {
                            elemento: "tarjeta",
                            valor: () => $("#tarjeta").val(),
                            texto: () => $("#tarjeta").val(),
                            validacion: () => $("#tarjeta").val().length === 16,
                            mensaje: "El número de tarjeta debe tener 16 dígitos."
                        }
                    }
                }

                const getFecha = (fecha) => {
                    return fecha ? moment(fecha).format(MOMENT_FRONT) : '-'
                }

                const getEstatus = (texto, tipo) => {
                    const colorClass = tipo === 'success' ? 'text-bg-success' : 'text-bg-danger'
                    return `<span class="badge \${colorClass}">\${texto}</span>`
                }

                const getFvMessage = (campo) => {
                    if (campo.parent().hasClass('input-group'))
                        return campo.parent().siblings('.fv-message')

                    return campo.siblings('.fv-message')
                }
                
                const initWizard = () => {
                    const wizardElement = document.querySelector('.wizard-registro-colaborador')
                    if (wizardElement) {
                        // Inicializar con linear: true por defecto (para registro)
                        wizardPersona = new Stepper(wizardElement, { linear: true, animation: true })
                        
                        const nextButtons = wizardElement.querySelectorAll('.btn-next')
                        const prevButtons = wizardElement.querySelectorAll('.btn-prev')

                        nextButtons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                const esNuevoRegistro = $("#personaIdHidden").val() === ""
                                
                                if (esNuevoRegistro) {
                                    if (validarPasoActual()) {
                                        wizardPersona.next()
                                        if (wizardPersona._currentIndex === wizardPersona._steps.length - 2) llenarResumen()
                                    }
                                } else {
                                    if (wizardPersona._steps[wizardPersona._currentIndex + 2].id === "stepConfirmacion") btn.disabled = true
                                    wizardPersona.next()
                                }
                            })
                        })

                        prevButtons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                nextButtons.forEach(btn => btn.disabled = false)
                                wizardPersona.previous()
                            })
                        })

                        // Agregar navegación directa por pasos en visualización/edición
                        const stepHeaders = wizardElement.querySelectorAll('.bs-stepper-header .step')
                        stepHeaders.forEach((step, index) => {
                            step.addEventListener('click', () => {
                                const esNuevoRegistro = $("#personaIdHidden").val() === ""
                                
                                // Solo permitir navegación directa en modo visualización/edición
                                if (!esNuevoRegistro) {
                                    wizardPersona.to(index + 1)
                                }
                            })
                        })
                    }
                }

                const validarPasoActual = () => {
                    if (!wizardPersona) return false
                    
                    const pasoActual = wizardPersona._currentIndex
                    
                    switch(pasoActual) {
                        case 0:
                            return validarWizard("personales")
                        case 1:
                            return validarWizard("contacto")
                        case 2:
                            return validarWizard("laborales")
                        case 3:
                            return validarWizard("nomina")
                        case 4:
                            return true
                        default:
                            return true
                    }
                }

                const validarWizard = (grupo) => {
                    let valido = true
                    const items = camposPersona[grupo]

                    Object.keys(items).forEach(campoKey => {
                        const campo = items[campoKey]
                        const valor = campo.valor()
                        const campoMensaje = getFvMessage($("#" + campo.elemento))
                        const validacion = campo.validacion()
                        valido = valido ? validacion : false
                        campoMensaje.text(!validacion ? campo.mensaje : "")
                    })

                    if (!valido) showError('Por favor complete todos los campos requeridos')

                    return valido
                }

                const llenarResumen = () => {
                    const fotoSrc = $('#fotoPreview').attr('src')
                    const nombre = camposPersona.personales.nombre.valor()
                    const apellido1 = camposPersona.personales.apellido1.valor()
                    const apellido2 = camposPersona.personales.apellido2.valor() || ''
                    const nombreCompleto = nombre + ' ' + apellido1 + ' ' + apellido2

                    const capitaliza = (s) => s.charAt(0).toUpperCase() + s.slice(1)

                    $('#resumenFoto').attr('src', fotoSrc)
                    $('#resumenNombreCompleto').text(nombreCompleto)

                    Object.keys(camposPersona).forEach(campoKey => {
                        const grupo = camposPersona[campoKey]
                        Object.keys(grupo).forEach(campoKey => {
                            const campo = grupo[campoKey]
                            $('#resumen' + capitaliza(campoKey)).text(campo.texto() || "-")
                        })
                    })
                }

                const guardarCambiosPersona = () => {
                    if (!verificarCambios()) {
                        showError("No se han detectado cambios para guardar")
                        return
                    }

                    const formData = new FormData()
                    formData.append('id', $("#personaIdHidden").val())
                    
                    // Recopilar todos los campos del formulario
                    Object.keys(camposPersona).forEach(campo => {
                        const grupo = camposPersona[campo]
                        Object.keys(grupo).forEach(campoKey => {
                            const campo = grupo[campoKey]
                            formData.append(campoKey, campo.valor() || "")
                        })
                    })
                    
                    // Agregar foto si se cambió
                    const fotoInput = $("#fotoInput")[0]
                    if (fotoInput.files && fotoInput.files[0]) {
                        formData.append('foto', fotoInput.files[0])
                    }

                    consultaServidor("/CapHum/actualizarPersona", formData, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        
                        // Volver a modo visualización
                        modoEdicion = false
                        hayCambios = false
                        datosOriginales = obtenerDatosFormulario()
                        bloquearCamposModal(true)
                        actualizarBotonesModal()
                        
                        // Actualizar tabla
                        getPersonas(true)
                    }, {
                        procesar: false,
                        tipoContenido: false
                    })
                }

                const guardarPersona = () => {
                    const formData = new FormData()
                    Object.keys(camposPersona).forEach(campo => {
                        const grupo = camposPersona[campo]
                        Object.keys(grupo).forEach(campoKey => {
                            const campo = grupo[campoKey]
                            formData.append(campoKey, campo.valor() || "")
                        })
                    })
                    
                    const fotoInput = $("#fotoInput")[0]
                    if (fotoInput.files && fotoInput.files[0]) {
                        formData.append('foto', fotoInput.files[0])
                    }

                    consultaServidor("/CapHum/guardarPersona", formData, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        $("#modalPersona").modal("hide")
                        getPersonas(true)
                    }, {
                        procesar: false,
                        tipoContenido: false
                    })
                }

                const confirmaEliminar = (mensaje, callback) => {
                    Swal.fire({
                        title: '¿Está seguro?',
                        text: mensaje,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            callback()
                        }
                    })
                }
                
                const agregarCorreo = () => {
                    const container = $('#correosContainer')
                    const nuevoCorreo = '<div class="input-group mb-2"><input type="email" name="correoEmpresa[]" class="form-control" placeholder="correo@empresa.com"><button type="button" class="btn btn-outline-danger" onclick="eliminarCorreo(this)"><i class="fa fa-minus"></i></div>'
                    container.append(nuevoCorreo)
                }
                
                const eliminarCorreo = (btn) => {
                    $(btn).closest('.input-group').remove()
                }

                const manejarCambiosFoto = (inputId, previewId) => {
                    $(inputId).on('change', function(e) {
                        const file = e.target.files[0]
                        if (file) {
                            const reader = new FileReader()
                            reader.onload = function(e) {
                                $(previewId).attr('src', e.target.result)
                                if (modoEdicion) {
                                    hayCambios = true
                                    actualizarBotonEdicion()
                                }
                            }
                            reader.readAsDataURL(file)
                        }
                    })
                }

                const verificarCambios = () => {
                    if (!modoEdicion) return false

                    let hayCambiosDetectados = false
                    const formActual = $('#modalPersona')
                    
                    // Verificar todos los campos del formulario
                    formActual.find('input, select, textarea').each(function() {
                        const elemento = $(this)
                        const id = elemento.attr('id')
                        
                        if (id && datosOriginales && datosOriginales.hasOwnProperty(id)) {
                            const valorActual = elemento.attr('type') === 'checkbox' ? 
                                elemento.is(':checked') : elemento.val()
                            const valorOriginal = datosOriginales[id]
                            
                            if (valorActual !== valorOriginal) {
                                hayCambiosDetectados = true
                                return false // Salir del each
                            }
                        }
                    })
                    
                    return hayCambiosDetectados || hayCambios
                }

                const configurarWizardParaModo = (esNuevoRegistro) => {
                    const stepConfirmacion = document.getElementById('stepConfirmacion')
                    const contentConfirmacion = document.getElementById('confirmacion')
                    
                    if (esNuevoRegistro) {
                        // Modo registro: mostrar confirmación
                        if (stepConfirmacion) stepConfirmacion.style.display = ''
                        if (contentConfirmacion) contentConfirmacion.style.display = ''
                    } else {
                        // Modo visualización/edición: ocultar confirmación
                        if (stepConfirmacion) stepConfirmacion.style.display = 'none'
                        if (contentConfirmacion) contentConfirmacion.style.display = 'none'
                    }
                    
                    // Reinicializar wizard si es necesario
                    if (wizardPersona) {
                        // Forzar actualización del stepper
                        wizardPersona._updateStepperStructure && wizardPersona._updateStepperStructure()
                    }
                }

                const actualizarBotonesModal = () => {
                    const tieneModificaciones = verificarCambios()
                    const btnGuardar = $("#btnGuardarCambiosPersona")
                    const btnEditar = $("#btnEditarPersona")
                    const btnCancelar = $("#btnCancelarGuardarPersona")
                    const esNuevoRegistro = $("#personaIdHidden").val() === ""
                    
                    if (esNuevoRegistro) {
                        // Modo registro: solo mostrar cancelar
                        btnEditar.hide()
                        btnGuardar.hide()
                        btnCancelar.text("Cancelar").show()
                    } else if (!modoEdicion) {
                        // Modo visualización: mostrar editar y cerrar
                        btnEditar.show().text("Editar")
                        btnGuardar.hide()
                        btnCancelar.text("Cerrar").show()
                    } else {
                        // Modo edición: mostrar cancelar y guardar (si hay cambios)
                        btnEditar.hide()
                        btnCancelar.text("Cancelar").show()
                        
                        if (tieneModificaciones) {
                            btnGuardar.show().text("Guardar Cambios")
                        } else {
                            btnGuardar.hide()
                        }
                    }
                }

                const habilitarEdicion = () => {
                    if (!modoEdicion) {
                        modoEdicion = true
                        hayCambios = false
                        
                        datosOriginales = {
                            nombre: $("#detalleNombre").val(),
                            apellido1: $("#detalleApellido1").val(),
                            apellido2: $("#detalleApellido2").val(),
                            rfc: $("#detalleRfc").val(),
                            curp: $("#detalleCurp").val(),
                            fechaNacimiento: $("#detalleFechaNacimiento").val(),
                            sexo: $("#detalleSexo").val()
                        }
                        
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", false)
                        $("#btnCambiarFotoDetalle").prop("disabled", false)
                        $("#btnHabilitarEdicion").removeClass("btn-warning").addClass("btn-warning")
                        $("#btnHabilitarEdicion").html('<i class="fa fa-times">&nbsp;</i>Cancelar')
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").on('input change', actualizarBotonEdicion)
                        
                    } else {
                        if (verificarCambios() || hayCambios) guardarEdicion()
                        else cancelarEdicion()
                    }
                }

                const cancelarEdicion = () => {
                    modoEdicion = false
                    hayCambios = false
                    
                    $("#detalleNombre").val(datosOriginales.nombre)
                    $("#detalleApellido1").val(datosOriginales.apellido1)
                    $("#detalleApellido2").val(datosOriginales.apellido2)
                    $("#detalleRfc").val(datosOriginales.rfc)
                    $("#detalleCurp").val(datosOriginales.curp)
                    $("#detalleFechaNacimiento").val(datosOriginales.fechaNacimiento)
                    $("#detalleSexo").val(datosOriginales.sexo)
                    $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", true)
                    $("#btnCambiarFotoDetalle").prop("disabled", true)
                    $("#btnHabilitarEdicion").removeClass("btn-success btn-warning").addClass("btn-warning")
                    $("#btnHabilitarEdicion").html('<i class="fa fa-edit">&nbsp;</i>Editar')
                    $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").off('input change', actualizarBotonEdicion)
                }

                const guardarEdicion = () => {
                    const datos = {
                        id: $("#detallePersonaIdHidden").val(),
                        nombre: $("#detalleNombre").val(),
                        apellido1: $("#detalleApellido1").val(),
                        apellido2: $("#detalleApellido2").val(),
                        rfc: $("#detalleRfc").val(),
                        curp: $("#detalleCurp").val(),
                        fechaNacimiento: $("#detalleFechaNacimiento").val(),
                        sexo: $("#detalleSexo").val()
                    }

                    consultaServidor("/CapHum/guardarPersona", datos, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        cancelarEdicion()
                        getPersonas(true)
                        
                        const nombreCompleto = datos.nombre + " " + datos.apellido1 + " " + (datos.apellido2 || "")
                        $("#detalleNombre").val(datos.nombre)
                        $("#detalleApellido1").val(datos.apellido1)
                        $("#detalleApellido2").val(datos.apellido2)
                    })
                }

                const getPersonas = (persistirVista = false) => {
                    const empresa = $("#filtroEmpresa").val()
                    const region = $("#filtroRegion").val()
                    const sucursal = $("#filtroSucursal").val()
                    const filtroColaborador = $("#filtroColaborador").val()
                    
                    const parametros = {
                        empresa,
                        region,
                        sucursal,
                        filtroColaborador
                    }

                    consultaServidor("/CapHum/getPersonas", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        
                        const datos = respuesta.datos.map((persona) => {
                            const ver = {
                                texto: "Ver detalle",
                                icono: "fa-eye",
                                funcion: "verPersona(" + persona.ID + ")"
                            }

                            const eliminar = {
                                texto: "Eliminar",
                                icono: "fa-trash",
                                funcion: "eliminarPersona(" + persona.ID + ")",
                                clase: "text-danger delete-record"
                            }
                            
                            const fotoUrl = persona.FOTO ? "/CapHum/getFotoPersona?personaId=" + persona.ID : "/assets/img/misc/user.svg"
                            const fotoHtml = '<img src="' + fotoUrl + '" alt="Foto" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">'

                            const id = getID(persona.ID, persona.EMPRESA, persona.EMPRESA_NOMBRE)
                            const colaborador = getColaborador(persona.NOMBRE_COMPLETO, persona.PUESTO)
                            const dni = getDNI(persona.RFC, persona.CURP)

                            return [
                                null,
                                id,
                                fotoHtml,
                                colaborador,
                                dni,
                                getFecha(persona.FECHA_NACIMIENTO),
                                getFecha(persona.FECHA_INGRESO),
                                getEstatus(persona.ESTATUS == 1 ? "Activo" : "Inactivo", persona.ESTATUS == 1 ? "success" : "danger"),
                                menuAcciones([ver, "divisor", eliminar])
                            ]
                        })

                        actualizaDatosTabla(tabla, datos, persistirVista)
                    })
                }

                const getID = (id, empresa, empresaNombre) => {
                    const contenedor = $('<div></div>').addClass('d-flex flex-column align-items-center')
                    const idText = $('<strong></strong>').text(id)
                    const badge = $('<span></span>').text(empresaNombre).addClass('badge rounded-pill text-muted').css({
                        'background-color': empresa === '2' ? '#4C1013' : "red",
                        'font-size': '0.5rem',
                    })
                    contenedor.append(idText).append(badge)
                    return contenedor.prop('outerHTML')
                }

                const getColaborador = (nombreCompleto, puesto) => {
                    const contenedor = $('<div></div>').addClass('d-flex flex-column')
                    const nombreElem = $('<span></span>').text(nombreCompleto).addClass('fw-bold')
                    const puestoElem = $('<span></span>').text(puesto || '-').addClass('text-muted').css('font-size', '0.8rem')
                    contenedor.append(nombreElem).append(puestoElem)
                    return contenedor.prop('outerHTML')
                }

                const getDNI = (rfc, curp) => {
                    const contenedor = $('<div></div>')
                    if (rfc) {
                        const rfcElem = $('<div></div>').append($('<strong></strong>').text('RFC: ')).append(document.createTextNode(rfc))
                        contenedor.append(rfcElem)
                    }
                    if (curp) {
                        const curpElem = $('<div></div>').append($('<strong></strong>').text('CURP: ')).append(document.createTextNode(curp))
                        contenedor.append(curpElem)
                    }
                    return contenedor.prop('outerHTML')
                }

                const nuevaPersona = () => {
                    limpiarPersona()
                    
                    // Configurar modal para modo registro
                    modoEdicion = false // En registro iniciamos en modo visualización hasta que el usuario empiece a llenar datos
                    $("#tituloModalPersona").text("Registrar nuevo colaborador")
                    $(".address-subtitle").text("Complete los siguientes pasos para el registro")
                    $("#personaId").val("")
                    $("#personaIdHidden").val("")
                    
                    // Configurar wizard para mostrar confirmación
                    configurarWizardParaModo(true)
                    
                    // Configurar botones para modo registro
                    actualizarBotonesModal()
                    
                    // Habilitar todos los campos para registro
                    bloquearCamposModal(false)
                    
                    // Ir al primer step del wizard
                    if (wizardPersona) {
                        wizardPersona.to(1)
                    }
                    
                    // Mostrar modal
                    modalPersona.show()
                }

                const verPersona = (id) => {
                    consultaServidor("/CapHum/getPersonaDetalle", {id: id}, async (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        
                        const persona = respuesta.datos.persona
                        const nomina = respuesta.datos.nomina
                        const empresa = respuesta.datos.empresa
                        const contactos = respuesta.datos.contactos
                        const telefonos = respuesta.datos.telefonos
                        const emails = respuesta.datos.emails
                        const usuarios = respuesta.datos.usuarios
                        const bancos = respuesta.datos.bancos

                        // Configurar modal para modo visualización
                        modoEdicion = false
                        $("#tituloModalPersona").text("Detalles del colaborador")
                        $(".address-subtitle").text("Información completa del colaborador")
                        $("#personaIdHidden").val(persona.ID)

                        configurarWizardParaModo(false)
                        actualizarBotonesModal()

                        $("#personaId").val(persona.ID)
                        $("#nombre").val(persona.NOMBRE)
                        $("#apellido1").val(persona.APELLIDO_1)
                        $("#apellido2").val(persona.APELLIDO_2 || "")
                        $("#rfc").val(persona.RFC)
                        $("#curp").val(persona.CURP)
                        $("#estadoCivil").val(persona.ESTADO_CIVIL)
                        $("#nss").val(persona.NSS)
                        $("#infonavit").prop("checked", persona.INFONAVIT == 1)
                        $("#fechaNacimiento").val(moment(persona.FECHA_NACIMIENTO).format(MOMENT_FRONT))
                        $("#sexo").val(persona.SEXO)
                        $("#calle").val(persona.CALLE_NUMERO)
                        $("#codigoPostal").val(persona.CP);
                        $("#codigoPostal").trigger("blur"); // Para cargar colonias
                        setTimeout(() => {
                            $("#colonia").val(persona.COLONIA)
                            $("#colonia").attr("disabled", "disabled")
                            $("#municipio").val(persona.MUNICIPIO)
                            $("#estado").val(persona.ESTADO)
                        }, 500)
                        
                        telefonos.forEach(telefono => {
                            if (telefono.TIPO === "1") {
                                $("#telefonoPrincipal").val(telefono.NUMERO);
                            } else if (telefono.TIPO === "2") {
                                $("#telefonoAlterno").val(telefono.NUMERO);
                            }
                        });
                        emails.forEach(email => {
                            if (email.TIPO === "1") {
                                $("#correoPrincipal").val(email.DIRECCION);
                            }
                        });
                        $("#contactoEmergenciaNombre").val(contactos[0]?.NOMBRE || "");
                        $("#contactoEmergenciaParentesco").val(contactos[0]?.PARENTESCO || "");
                        $("#contactoEmergenciaTelefono").val(contactos[0]?.TELEFONO || "");
                        $("#condicionesMedicas").val(persona.CONDICIONES_MEDICAS || "");
                        $("#informacionAdicional").val(persona.OTROS_DATOS_RELEVANTES || "");

                        // Cargar datos de empresa
                        $("#empresa").val(empresa.EMPRESA);
                        $("#empresa").trigger("change")

                        Array.from($("#region").find('option[data-empresa="' + empresa.EMPRESA + '"]')).forEach(opt => {
                            if (opt.value == empresa.REGION) opt.selected = true
                        });
                        $("#region").trigger("change");

                        Array.from($("#sucursal").find('option[data-empresa="' + empresa.EMPRESA + '"]')).forEach(opt => {
                            if (opt.value == empresa.SUCURSAL) opt.selected = true
                        });
                        $("#sucursal").trigger("change");

                        while (cargandoEmpleados) { 
                            await new Promise(r => setTimeout(r, 1000))
                            console.log("Esperando empleados...");
                            
                        }

                        $("#jefeInmediato").val(nomina.JEFE);
                        $("#jefeInmediato").attr("disabled", "disabled");
                        $("#reporta").val(usuarios[0].AUTORIZADOR);
                        $("#reporta").attr("disabled", "disabled");

                        $("#puesto").val(nomina.PUESTO);
                        emails.forEach(email => {
                            if (email.TIPO === "4") {
                                // el input tiene el name "correoEmpresa[]"
                                if ($("#correosContainer").children().length === 1 && $("#correosContainer").children().first().find('input').val() === "") {
                                    // Si solo hay un input vacío, reutilizarlo
                                    $("#correosContainer").children().first().find('input').val(email.DIRECCION);
                                    return;
                                    
                                }

                                const nuevoCorreo = $("#correosContainer").children().first().clone();
                                nuevoCorreo.find('input').val(email.DIRECCION);
                                $("#correosContainer").append(nuevoCorreo);
                            }
                        });


                        $("#fechaIngreso").val(moment(nomina.INGRESO).format(MOMENT_FRONT));
                        $("#proveedor").val(nomina.PROVEEDOR);
                        $("#tipoNomina").val(nomina.TIPO_NOMINA);
                        $("#numeroNomina").val(nomina.NUMERO_NOMINA);
                        
                        // Cargar datos bancarios
                        bancos.forEach(banco => {
                            $("#banco").val(banco.ID_BANCO);
                            if (banco.TIPO_NUMERO === "1") {
                                $("#cuentaBancaria").val(banco.NUMERO);
                            } else if (banco.TIPO_NUMERO === "2") {
                                $("#tarjeta").val(banco.NUMERO);
                            }
                        });

                        $("#usuario").val(usuarios[0]?.USUARIO || "");
                        $("#perfil").val(usuarios[0]?.PERFIL || "");

                        // Mostrar ID de usuario arriba de la foto
                        $("#usuarioIdDisplay").text(persona?.ID || "-");

                        // Configurar imagen de perfil
                        let fotoSrc = "/assets/img/misc/user.svg"
                        try {
                            const filaEncontrada = $(tabla + ' tbody tr').filter(function() {
                                const idCelda = $(this).find('td').eq(1).text().trim()
                                return idCelda == persona.ID
                            }).first()
                            
                            if (filaEncontrada.length > 0) {
                                const imgTabla = filaEncontrada.find('td').eq(2).find('img')
                                if (imgTabla.length > 0) {
                                    const srcTabla = imgTabla.attr('src')
                                    if (srcTabla && srcTabla !== "/assets/img/misc/user.svg") {
                                        fotoSrc = srcTabla
                                    }
                                }
                            }
                        } catch (error) {
                            console.log('Error al buscar foto en tabla:', error)
                        }
                        
                        if (fotoSrc === "/assets/img/misc/user.svg" && persona.FOTO) {
                            fotoSrc = "/CapHum/getFotoPersona?personaId=" + persona.ID
                        }
                        
                        $("#fotoPreview").attr("src", fotoSrc)

                        // Bloquear todos los campos para modo visualización
                        bloquearCamposModal(true)
                        
                        // Ir al primer step del wizard
                        wizardPersona.to(1)
                        
                        // Mostrar el modal
                        modalPersona.show()
                    })
                }

                const bloquearCamposModal = (bloquear) => {
                    const campos = $('#modalPersona input, #modalPersona select, #modalPersona textarea')
                    campos.prop('disabled', bloquear)
                    if (bloquear) {
                        $('#btnCambiarFoto').hide()
                    } else {
                        $('#btnCambiarFoto').show()
                    }
                }

                const toggleModoEdicion = () => {
                    const esNuevoRegistro = $("#personaIdHidden").val() === ""
                    
                    if (esNuevoRegistro) return // No aplicar en modo registro
                    
                    if (!modoEdicion) {
                        // Activar modo edición
                        modoEdicion = true
                        hayCambios = false
                        
                        // Guardar estado original para poder cancelar
                        datosOriginales = obtenerDatosFormulario()
                        
                        // Habilitar campos
                        bloquearCamposModal(false)
                        
                        // Agregar listeners para detectar cambios
                        $('#modalPersona input, #modalPersona select, #modalPersona textarea').on('input change', () => {
                            setTimeout(actualizarBotonesModal, 10) // Pequeño delay para que se actualice el valor
                        })
                        
                    } else {
                        // Cancelar edición
                        modoEdicion = false
                        hayCambios = false
                        
                        // Bloquear campos
                        bloquearCamposModal(true)
                        
                        // Restaurar datos originales
                        if (datosOriginales) {
                            restaurarDatosFormulario(datosOriginales)
                        }
                        
                        // Remover listeners
                        $('#modalPersona input, #modalPersona select, #modalPersona textarea').off('input change')
                    }
                    
                    // Actualizar botones
                    actualizarBotonesModal()
                }

                const obtenerDatosFormulario = () => {
                    const datos = {}
                    $('#modalPersona input, #modalPersona select, #modalPersona textarea').each(function() {
                        const elemento = $(this)
                        if (elemento.attr('type') === 'checkbox') {
                            datos[elemento.attr('id')] = elemento.is(':checked')
                        } else {
                            datos[elemento.attr('id')] = elemento.val()
                        }
                    })
                    return datos
                }

                const restaurarDatosFormulario = (datos) => {
                    Object.keys(datos).forEach(id => {
                        const elemento = $('#' + id)
                        if (elemento.attr('type') === 'checkbox') {
                            elemento.prop('checked', datos[id])
                        } else {
                            elemento.val(datos[id])
                        }
                    })
                }

                const eliminarPersona = (id) => {
                    confirmaEliminar("¿Está seguro de eliminar esta persona?", () => {
                        consultaServidor("/CapHum/eliminarPersona", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            getPersonas(true)
                        })
                    })
                }

                const limpiarPersona = () => {
                    $("#personaId").val("")
                    $("#nombre").val("")
                    $("#apellido1").val("")
                    $("#apellido2").val("")
                    $("#rfc").val("")
                    $("#curp").val("")
                    $("#fechaNacimiento").val("")
                    $("#sexo").val("")
                    $("#usuario").val("")
                    $("#password").val("")
                    $("#perfil").val("")
                    $("#empresa").val("")
                    $("#region").val("")
                    $("#sucursal").val("")
                    $("#puesto").val("")
                    $("#nomina").val("")
                    $("#tipoNomina").val("")
                    $("#numeroNomina").val("")
                    $("#jefeInmediato").val("")
                    $("#reporta").val("")
                    $('#correosContainer').html('<div class="input-group mb-2"><input type="email" name="correoEmpresa[]" class="form-control" placeholder="correo@empresa.com"><button type="button" class="btn btn-outline-success" onclick="agregarCorreo()"><i class="fa fa-plus"></i></button></div>')
                    $("#fotoInput").val("")
                    $("#fotoPreview").attr("src", "/assets/img/misc/user.svg")
                    $("#contactoEmergenciaNombre").val("")
                    $("#contactoEmergenciaParentesco").val("")
                    $("#contactoEmergenciaTelefono").val("")
                    $("#condicionesMedicas").val("")
                    $("#informacionAdicional").val("")
                    $("#usuarioIdDisplay").text("-")
                    $('.fv-message').text('')
                }

                const inhabilitarPersona = () => {
                    const id = $("#detallePersonaIdHidden").val()
                    if (!id) return showError("No se ha seleccionado una persona")
                    
                    confirmaEliminar("¿Está seguro de desactivar esta persona?", () => {
                        consultaServidor("/CapHum/eliminarPersona", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            modalPersona.hide()
                            getPersonas(true)
                        })
                    })
                }

                const editarUsuario = (id) => {
                    consultaServidor("/CapHum/getUsuarioDetalle", {id: id}, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        
                        const usuario = respuesta.datos
                        
                        // Llenar campos del modal de edición
                        $("#editUsuarioId").val(usuario.ID)
                        $("#editPersonaId").val(usuario.PERSONA)
                        $("#editUsuario").val(usuario.USUARIO)
                        $("#editPass").val("") // Contraseña siempre vacía
                        $("#editEmpresa").val(usuario.EMPRESA || "")
                        $("#editRegion").val(usuario.REGION || "")
                        $("#editSucursal").val(usuario.SUCURSAL || "")
                        $("#editPerfil").val(usuario.PERFIL || "")
                        $("#editEstatusUsuario").val(usuario.ESTATUS)
                        
                        // Limpiar mensajes de validación
                        $('.fv-message').text('')
                        
                        $("#modalEditarUsuario").modal("show")
                    })
                }

                const guardarUsuario = () => {
                    // Validar campos requeridos
                    let valido = true
                    $('.fv-message').text('')
                    
                    const campos = ['editUsuario', 'editRegion', 'editSucursal', 'editPerfil', 'editEmpresa']
                    
                    campos.forEach(campo => {
                        const valor = $("#" + campo).val()
                        if (!valor || valor.trim() === '') {
                            $("#" + campo).siblings('.fv-message').text('Este campo es requerido')
                            valido = false
                        }
                    })

                    // Validar contraseña si se proporcionó
                    const password = $('#editPass').val()
                    if (password && password.length < 6) {
                        $('#editPass').siblings('.fv-message').text('La contraseña debe tener al menos 6 caracteres')
                        valido = false
                    }

                    if (!valido) {
                        showError('Por favor complete todos los campos requeridos')
                        return
                    }

                    const datos = {
                        id: $("#editUsuarioId").val(),
                        persona: $("#editPersonaId").val(),
                        usuario: $("#editUsuario").val(),
                        pass: $("#editPass").val(),
                        region: $("#editRegion").val(),
                        sucursal: $("#editSucursal").val(),
                        perfil: $("#editPerfil").val(),
                        empresa: $("#editEmpresa").val(),
                        estatus: $("#editEstatusUsuario").val()
                    }

                    consultaServidor("/CapHum/guardarUsuario", datos, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        $("#modalEditarUsuario").modal("hide")
                        
                        // Recargar el detalle de la persona
                        const personaId = $("#detallePersonaIdHidden").val()
                        if (personaId) {
                            verPersona(personaId)
                        }
                    })
                }

                const cambiarEstatusUsuario = (id) => {
                    consultaServidor("/CapHum/getUsuarioDetalle", {id: id}, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        
                        const usuario = respuesta.datos
                        const accion = usuario.ESTATUS == 1 ? "desactivar" : "activar"
                        const nuevoEstatus = usuario.ESTATUS == 1 ? 0 : 1
                        
                        confirmaEliminar("¿Está seguro de " + accion + " este usuario?", () => {
                            consultaServidor("/CapHum/cambiarEstatusUsuario", {id: id, estatus: nuevoEstatus}, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                showSuccess(respuesta.mensaje)
                                
                                // Recargar el detalle de la persona
                                const personaId = $("#detallePersonaIdHidden").val()
                                if (personaId) {
                                    verPersona(personaId)
                                }
                            })
                        })
                    })
                }

                const eliminarUsuario = (id) => {
                    confirmaEliminar("¿Está seguro de eliminar este usuario?", () => {
                        consultaServidor("/CapHum/eliminarUsuario", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            
                            // Recargar el detalle de la persona
                            const personaId = $("#detallePersonaIdHidden").val()
                            if (personaId) {
                                verPersona(personaId)
                            }
                        })
                    })
                }

                const resetSelectsSepomex = () => {
                    $('#estado, #municipio, #localidad, #colonia').prop('disabled', true).html('<option value="">Seleccione CP primero</option>');
                }

                const consultarSepomex = async (cp) => {
                    try {
                        $('#estado, #municipio, #localidad, #colonia').prop('disabled', true).html('<option value="">Cargando...</option>');
                        
                        const response = await fetch("https://api.condusef.gob.mx/sepomex/colonias/?cp=" + cp);
                        const data = await response.json();

                        if (!data.colonias || data.colonias.length === 0) {
                            $('#codigoPostal').siblings('.fv-message').text('Código postal no encontrado');
                            resetSelectsSepomex();
                            return;
                        }
                        
                        $('#codigoPostal').siblings('.fv-message').text('');
                        const colonias = data.colonias;
                        validaEstado(colonias);
                        validaMunicipio(colonias);
                        validaColonia(colonias);

                    } catch (error) {
                        console.error('Error al consultar SEPOMEX:', error);
                        $('#codigoPostal').siblings('.fv-message').text('Error al consultar código postal. Verifique su conexión a internet.');
                        resetSelectsSepomex();
                    }
                }

                const validaEstado = (edo) => {
                    const estado = document.querySelector("#estado")
                    const estados = getOpciones(edo, "estadoId", "estado")
                    insertaOpciones(estado, estados)
                }

                const validaMunicipio = (mun) => {
                    const municipio = document.querySelector("#municipio")
                    const municipios = getOpciones(mun, "municipioId", "municipio")
                    insertaOpciones(municipio, municipios)
                }

                const validaColonia = (col) => {
                    const colonia = document.querySelector("#colonia")
                    const colonias = getOpciones(col, "coloniaId", "colonia")
                    insertaOpciones(colonia, colonias)
                }

                const getOpciones = (elementos, key, value) => {
                    const opciones = []
                    elementos.forEach((elemento) => {
                        const opcion = "<option value='" + elemento[key] + "'>" + elemento[value] + "</option>"
                        if (!opciones.includes(opcion)) opciones.push(opcion)
                    })
                    return opciones
                }

                const insertaOpciones = (elemento, opciones = []) => {
                    if (opciones.length > 1) opciones.unshift("<option value='' disabled>Seleccione</option>")

                    elemento.innerHTML = opciones.join("")
                    elemento.selectedIndex = 0
                    elemento.disabled = !(opciones.length > 1)
                }

                const camposNumericos = (campo, largo) => {
                    let valor = campo.val().replace(/\D/g, '')
                    if (valor.length > largo) valor = valor.substring(0, largo)
                    campo.val(valor)
                }

                const getPersonalSucursal = (empresa, region, sucursal) => {
                    const parametros = {
                        empresa,
                        region,
                        sucursal
                    }

                    cargandoEmpleados = true
                    consultaServidor("/CapHum/getPersonalSucursal", parametros, (respuesta) => {
                        cargandoEmpleados = false
                        if (!respuesta.success) return showError(respuesta.mensaje)

                        const personal = respuesta.datos
                        let  opciones = "<option value='' selected disabled>Seleccione un jefe</option>"
                        opciones += personal.map((miembro) => {
                            return "<option value='" + miembro.PERSONA + "'>" + miembro.NOMBRE + "</option>"
                        }).join("")

                        $("#jefeInmediato").html(opciones)
                        $("#jefeInmediato").prop("disabled", false)

                        $("#reporta").html(opciones)
                        $("#reporta").prop("disabled", false)
                    })
                }

                const bloqueaJefe = () => {
                    $("#jefeInmediato").prop("disabled", true).html('<option value="" selected disabled>Seleccione un jefe</option>')
                    $("#reporta").prop("disabled", true).html('<option value="" selected disabled>Seleccione a quien reporta</option>')
                }

                const cambioFiltroEmpresa = () => {
                    const empresa = $("#filtroEmpresa").val()
                    const region = $("#filtroRegion option[data-empresa='" + empresa + "']").first().val()
                    $("#filtroRegion").val(region).trigger("change")
                    const sucursal = $("#filtroSucursal option[data-empresa='" + empresa + "'][data-region='" + region + "']").first().val()
                    $("#filtroSucursal").val(sucursal).trigger("change")
                    getPersonas()
                }

                const cambioFiltroRegion = () => {
                    const empresa = $("#filtroEmpresa").val()
                    const region = $("#filtroRegion").val()
                    const sucursal = $("#filtroSucursal option[data-empresa='" + empresa + "'][data-region='" + region + "']").first().val()
                    $("#filtroSucursal").val(sucursal).trigger("change")
                    getPersonas()
                }

                const cambioFiltroSucursal = () => {
                    getPersonas()
                }

                $(document).ready(() => {
                    const maxF = moment().subtract(18, 'years').format(MOMENT_FRONT);
                    const minF = moment().subtract(70, 'years').format(MOMENT_FRONT);
                    setInputFechas("#fechaNacimiento", { minF, maxF, iniF: maxF, enModal: true })
                    setInputFechas("#fechaIngreso", { minF: '01/01/2013', maxD: 7, enModal: true })
                    configuraTabla(tabla)
                    initWizard()
                    getPersonas()
                    
                    // Inicializar modal de persona
                    modalPersona = new bootstrap.Modal(document.getElementById('modalPersona'))
                    
                    window.agregarCorreo = agregarCorreo
                    window.eliminarCorreo = eliminarCorreo
                    
                    $("#btnBuscar").click(() => getPersonas())
                    $("#btnNuevaPersona").click(nuevaPersona)
                    $("#btnHabilitarEdicion").click(habilitarEdicion)
                    $("#btnInhabilitarPersona").click(inhabilitarPersona)
                    $("#btnCambiarFoto").click(() => $("#fotoInput").click())
                    $("#btnCambiarFotoDetalle").click(() => $("#detalleFotoInput").click())
                    $("#btnCambiarFotoResumen").click(() => $("#resumenFotoInput").click())
                    
                    // Event listeners para el modal unificado
                    $("#btnEditarPersona").click(toggleModoEdicion)
                    $("#btnCancelarGuardarPersona").click(function() {
                        const esNuevoRegistro = $("#personaIdHidden").val() === ""
                        
                        if (esNuevoRegistro) modalPersona.hide()
                        else if (modoEdicion) toggleModoEdicion()
                        else modalPersona.hide()
                    })
                    
                    $("#btnGuardarCambiosPersona").click(function() {
                        const esNuevoRegistro = $("#personaIdHidden").val() === ""
                        
                        if (esNuevoRegistro) guardarPersona()
                        else guardarCambiosPersona()
                    })

                    manejarCambiosFoto("#fotoInput", "#fotoPreview")
                    manejarCambiosFoto("#detalleFotoInput", "#detalleFotoPreview")
                    
                    $(document).on('click', '#btnGuardarPersona', guardarPersona)
                    $(document).on('click', '#btnGuardarUsuario', guardarUsuario)
                    
                    $("#filtroTabla").on("keyup", function() {
                        const valor = $(this).val().toLowerCase()
                        const tabla = $("#tablaPersonas").DataTable()
                        tabla.search(valor).draw()
                    })
                    
                    $('#modalPersona input, #modalPersona select').on('blur change', function() {
                        const valor = $(this).val()
                        if (valor && valor.trim() !== '') {
                            $(this).siblings('.fv-message').text('')
                        }
                    })
                    
                    $('#modalEditarUsuario input, #modalEditarUsuario select').on('blur change', function() {
                        const valor = $(this).val()
                        if (valor && valor.trim() !== '') {
                            $(this).siblings('.fv-message').text('')
                        }
                    })

                    setSelectEmpresaRegionSucursal("#empresa", "#region", "#sucursal", { empChange: bloqueaJefe, regChange: bloqueaJefe, sucChange: getPersonalSucursal })
                    setSelectEmpresaRegionSucursal("#filtroEmpresa", "#filtroRegion", "#filtroSucursal", { empChange: cambioFiltroEmpresa, regChange: cambioFiltroRegion, sucChange: cambioFiltroSucursal })
                    $("#filtroEmpresa").trigger("change")

                    $('#jefeInmediato').change(function() {
                        const valor = $(this).val()
                        const texto = $(this).find('option:selected').text()
                        if (valor) {
                            $('#reporta').val(valor)
                            $('#reporta option').prop('selected', false)
                            $('#reporta option[value="' + valor + '"]').prop('selected', true)
                        }
                    })

                    $('#contactoTelefonoPrincipal, #contactoTelefonoAlterno, #contactoEmergenciaTelefono').on('input', function() {
                        camposNumericos($(this), 10)
                    })
                    
                    $('#codigoPostal').on('input, blur', function() {
                        camposNumericos($(this), 5);
                        if ($(this).val().length < 5) resetSelectsSepomex()
                        if ($(this).val().length === 5) consultarSepomex($(this).val())
                    })
                    
                    $('#cuentaBancaria').on('input', function() {
                        camposNumericos($(this), 18)
                    })

                    $('#tarjeta').on('input', function() {
                        camposNumericos($(this), 18)
                    })
                })
            </script>
        HTML;

        $catSucursales = CapHumDAO::getCatalogoSucursales();

        if ($catSucursales['success']) $optionsSucursales = self::getOptionsSucursales($catSucursales['datos']);

        $catBancos = CapHumDAO::getCatalogoBancos();
        if ($catBancos['success']) $bancos = self::getOptions($catBancos['datos'], 'ID', 'NOMBRE');

        $catPerfiles = CapHumDAO::getPerfiles();
        if ($catPerfiles['success']) $perfiles = self::getOptions($catPerfiles['datos'], 'ID', 'NOMBRE');

        $catParentescos = CapHumDAO::getCatalogoParentescos();
        if ($catParentescos['success']) $parentescos = self::getOptions($catParentescos['datos'], 'ID_PARENTESCO', 'DESCRIPCION');

        $catPuestos = CapHumDAO::getCatalogoPuestos();
        if ($catPuestos['success']) $puestos = self::getOptions($catPuestos['datos'], 'ID_PUESTO', 'DESCRIPCION');

        $catProveedores = CapHumDAO::getCatalogoNominaProveedores();
        if ($catProveedores['success']) $proveedores = self::getOptions($catProveedores['datos'], 'ID_PROVEEDOR', 'NOMBRE_PROVEEDOR');

        $this->set('titulo', 'Gestión Capital Humano | ' . CONFIGURACION['EMPRESA']);
        $this->set('script', $script);
        $this->set('css', '<link rel="stylesheet" href="/assets/css/wizard-rh.css">');
        $this->set("parentescos", $parentescos);
        $this->set("puestos", $puestos);
        $this->set("proveedores", $proveedores);
        $this->set("empresas", $optionsSucursales['empresas']);
        $this->set("regiones", $optionsSucursales['regiones']);
        $this->set("sucursales", $optionsSucursales['sucursales']);
        $this->set("filtroEmpresa", $optionsSucursales['empresas']);
        $this->set("filtroRegion", $optionsSucursales['regiones']);
        $this->set("filtroSucursal", $optionsSucursales['sucursales']);
        $this->set("bancos", $bancos);
        $this->set("perfiles", $perfiles);
        $this->render('caphum_gestion');
    }

    public function getPersonas()
    {
        $personas = CapHumDAO::getPersonas($_POST);
        $this->respuestaJSON($personas);
    }

    public function getPersonaDetalle()
    {
        $detalle = CapHumDAO::getPersonaDetalle($_POST);
        $this->respuestaJSON($detalle);
    }

    public function getPersonalSucursal()
    {
        $personal = CapHumDAO::getPersonalSucursal($_POST);
        $this->respuestaJSON($personal);
    }

    public function guardarPersona()
    {
        $fotoData = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'La foto excede el tamaño máximo permitido de 5 MB.'
                ]);
            }

            // Validar que sea una imagen
            $tipoArchivo = $_FILES['foto']['type'];
            if (!in_array($tipoArchivo, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'Solo se permiten archivos de imagen (JPEG, PNG, GIF).'
                ]);
            }

            try {
                $fotoData = [
                    'foto' => fopen($_FILES['foto']['tmp_name'], 'rb'),
                ];
            } catch (\Exception $e) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'Error al procesar la foto: ' . $e->getMessage()
                ]);
            }
        }

        $resultado = CapHumDAO::guardarPersona($_POST, $fotoData);

        // Cerrar el recurso de la foto si se abrió
        if ($fotoData && is_resource($fotoData['foto'])) {
            fclose($fotoData['foto']);
        }

        $this->respuestaJSON($resultado);
    }

    public function actualizarPersona()
    {
        $fotoData = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'La foto excede el tamaño máximo permitido de 5 MB.'
                ]);
            }

            // Validar que sea una imagen
            $tipoArchivo = $_FILES['foto']['type'];
            if (!in_array($tipoArchivo, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'Solo se permiten archivos de imagen (JPEG, PNG, GIF).'
                ]);
            }

            try {
                $fotoData = [
                    'foto' => fopen($_FILES['foto']['tmp_name'], 'rb'),
                ];
            } catch (\Exception $e) {
                return $this->respuestaJSON([
                    'success' => false,
                    'mensaje' => 'Error al procesar la foto: ' . $e->getMessage()
                ]);
            }
        }

        $resultado = CapHumDAO::actualizarPersona($_POST, $fotoData);

        // Cerrar el recurso de la foto si se abrió
        if ($fotoData && is_resource($fotoData['foto'])) {
            fclose($fotoData['foto']);
        }

        $this->respuestaJSON($resultado);
    }

    public function eliminarPersona()
    {
        $resultado = CapHumDAO::eliminarPersona($_POST);
        $this->respuestaJSON($resultado);
    }

    public function getUsuarioDetalle()
    {
        $detalle = CapHumDAO::getUsuarioDetalle($_POST);
        $this->respuestaJSON($detalle);
    }

    public function guardarUsuario()
    {
        $resultado = CapHumDAO::guardarUsuario($_POST);
        $this->respuestaJSON($resultado);
    }

    public function cambiarEstatusUsuario()
    {
        $resultado = CapHumDAO::cambiarEstatusUsuario($_POST);
        $this->respuestaJSON($resultado);
    }

    public function eliminarUsuario()
    {
        $resultado = CapHumDAO::eliminarUsuario($_POST);
        $this->respuestaJSON($resultado);
    }

    public function getFotoPersona()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;

        $foto = CapHumDAO::getFotoPersona($datos);
        if (!$foto['success']) return $this->respuestaJSON($foto);

        $archivo = $foto['datos']['FOTO'];
        $archivo = is_resource($archivo) ? stream_get_contents($archivo) : $archivo;
        if ($archivo === false) {
            return $this->respuestaJSON(self::respuesta(false, 'Error al leer el archivo de la foto.'));
        }

        header('Content-Transfer-Encoding: binary');
        echo $archivo;
        if (is_resource($archivo)) {
            fclose($archivo);
        }
    }

    public function FormatosMCM()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialFormatos"
                let valNuevoFormato = null

                const getFormatos = () => {
                    const fechas = getInputFechas("#filtroFechas", true, true)
                    const parametros = {
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/CapHum/getListaFormatosMCM", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)

                        const datos = respuesta.datos.map((formato) => {
                            return [
                                null,
                                formato.ID,
                                formato.NOMBRE,
                                moment(formato.FECHA_SUBIDA).format(MOMENT_FRONT_HORA),
                                moment(formato.VIGENCIA_FIN).format(MOMENT_FRONT),
                                formato.ACCESO,
                                menuAcciones([{
                                    texto: "Formato",
                                    icono: "fa-eye",
                                    funcion: "verFormato(" + formato.ID + ")"
                                }])
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const verFormato = (id) => {
                    showWait("Obteniendo archivo...")
                    const parametro = new FormData()
                    parametro.append("idFormato", id)
                    mostrarArchivoDescargado("/CapHum/getFormatoMCM", parametro)
                }

                const setvalNuevoFormato = () => {
                    const campos = {
                        nombre: {
                            notEmpty: {
                                message: "Debe ingresar el nombre del archivo"
                            }
                        },
                        archivoFormato: {
                            notEmpty: {
                                message: "Debe seleccionar un archivo para subir"
                            },
                            file: {
                                maxSize: 5 * 1024 * 1024, // 5 MB
                                message: "El archivo no debe exceder 5MB"
                            }
                        }
                    }

                    valNuevoFormato = setValidacionModal(
                        "#modalSubirFormato",
                        campos,
                        "#subirFormato",
                        subirFormato,
                        "#cancelaSubirFormato"
                    )
                }

                const subirFormato = () => {
                    confirmarMovimiento("¿Desea subir este nuevo formato?").then((continuar) => {
                        if (!continuar) return

                        const archivo = $("#archivoFormato")[0].files[0]
                        const nombre = $("#nombre").val().trim()
                        const fechas = getInputFechas("#fechasVigencia", true, false)

                        const formData = new FormData();
                        formData.append("nombre", nombre);
                        formData.append("archivo", archivo);

                        consultaServidor("/CapHum/registrarFormatoMCM", formData, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)

                            $("#modalSubirFormato").modal("hide")
                            showSuccess(respuesta.mensaje).then(getFormatos)
                        }, {
                            procesar: false,
                            tipoContenido: false
                        })
                    })
                }

                $(document).ready(function() {
                    setInputFechas("#filtroFechas", { rango: true, iniD: -30 })
                    setInputFechas("#fechasVigencia", { rango: true, enModal: true, finD: 365, minD: 0 })
                    configuraTabla(tabla)
                    setvalNuevoFormato()

                    $("#btnBuscarFormatos").on("click", getFormatos)
                    $("#btnAgregar").on("click", function() {
                        $("#modalSubirFormato").modal("show")
                    })

                    getFormatos()
                })
            </script>
        HTML;

        self::set("titulo", "Formatos MCM");
        self::set("script", $script);
        self::render("caphum_formatos_mcm");
    }

    public function getListaFormatosMCM()
    {
        self::respuestaJSON(CapHumDAO::getListaFormatosMCM($_POST));
    }

    public function getFormatoMCM()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;

        $formato = CapHumDAO::getFormatoMCM($datos);
        if (!$formato['success']) return self::respuestaJSON($formato);

        $archivo = $formato['datos']['ARCHIVO'];
        $archivo = is_resource($archivo) ? stream_get_contents($archivo) : $archivo;
        if ($archivo === false) {
            return self::respuestaJSON(self::respuesta(false, 'Error al leer el archivo del formato.'));
        }

        header('Content-Transfer-Encoding: binary');
        header("Content-Type: {$formato['datos']['TIPO']}");
        header("Content-Disposition: inline; filename={$formato['datos']['NOMBRE']}");
        echo $archivo;

        if (is_resource($archivo)) fclose($archivo);
    }

    public function registrarFormatoMCM()
    {
        $datos = self::getDatosSubirArchivo();
        $resultado = CapHumDAO::registraFormatoMCM($datos);

        if (is_resource($datos['archivo'])) fclose($datos['archivo']);
        self::respuestaJSON($resultado);
    }

    public function FormatosCultiva()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialFormatos"
                let valNuevoFormato = null

                const getFormatos = () => {
                    const fechas = getInputFechas("#filtroFechas", true, true)
                    const parametros = {
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/CapHum/getListaFormatosCultiva", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)

                        const datos = respuesta.datos.map((formato) => {
                            return [
                                null,
                                formato.ID,
                                formato.NOMBRE,
                                moment(formato.FECHA_SUBIDA).format(MOMENT_FRONT_HORA),
                                moment(formato.VIGENCIA_FIN).format(MOMENT_FRONT),
                                formato.ACCESO,
                                menuAcciones([{
                                    texto: "Formato",
                                    icono: "fa-eye",
                                    funcion: "verFormato(" + formato.ID + ")"
                                }])
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const verFormato = (id) => {
                    showWait("Obteniendo archivo...")
                    const parametro = new FormData()
                    parametro.append("idFormato", id)
                    mostrarArchivoDescargado("/CapHum/getFormatoCultiva", parametro)
                }

                const setvalNuevoFormato = () => {
                    const campos = {
                        nombre: {
                            notEmpty: {
                                message: "Debe ingresar el nombre del archivo"
                            }
                        },
                        archivoFormato: {
                            notEmpty: {
                                message: "Debe seleccionar un archivo para subir"
                            },
                            file: {
                                maxSize: 5 * 1024 * 1024, // 5 MB
                                message: "El archivo no debe exceder 5MB"
                            }
                        }
                    }

                    valNuevoFormato = setValidacionModal(
                        "#modalSubirFormato",
                        campos,
                        "#subirFormato",
                        subirFormato,
                        "#cancelaSubirFormato"
                    )
                }

                const subirFormato = () => {
                    confirmarMovimiento("¿Desea subir este nuevo formato?").then((continuar) => {
                        if (!continuar) return

                        const archivo = $("#archivoFormato")[0].files[0]
                        const nombre = $("#nombre").val().trim()
                        const fechas = getInputFechas("#fechasVigencia", true, false)

                        const formData = new FormData();
                        formData.append("nombre", nombre);
                        formData.append("archivo", archivo);

                        consultaServidor("/CapHum/registrarFormatoCultiva", formData, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)

                            $("#modalSubirFormato").modal("hide")
                            showSuccess(respuesta.mensaje).then(getFormatos)
                        }, {
                            procesar: false,
                            tipoContenido: false
                        })
                    })
                }

                $(document).ready(function() {
                    setInputFechas("#filtroFechas", { rango: true, iniD: -30 })
                    setInputFechas("#fechasVigencia", { rango: true, enModal: true, finD: 365, minD: 0 })
                    configuraTabla(tabla)
                    setvalNuevoFormato()

                    $("#btnBuscarFormatos").on("click", getFormatos)
                    $("#btnAgregar").on("click", function() {
                        $("#modalSubirFormato").modal("show")
                    })

                    getFormatos()
                })
            </script>
        HTML;

        self::set("titulo", "Formatos CULTIVA");
        self::set("script", $script);
        self::render("caphum_formatos_cultiva");
    }

    public function getListaFormatosCultiva()
    {
        self::respuestaJSON(CapHumDAO::getListaFormatosCultiva($_POST));
    }

    public function getFormatoCultiva()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;

        $formato = CapHumDAO::getFormatoCultiva($datos);
        if (!$formato['success']) return self::respuestaJSON($formato);

        $archivo = $formato['datos']['ARCHIVO'];
        $archivo = is_resource($archivo) ? stream_get_contents($archivo) : $archivo;
        if ($archivo === false) {
            return self::respuestaJSON(self::respuesta(false, 'Error al leer el archivo del formato.'));
        }

        header('Content-Transfer-Encoding: binary');
        header("Content-Type: {$formato['datos']['TIPO']}");
        header("Content-Disposition: inline; filename={$formato['datos']['NOMBRE']}");
        echo $archivo;

        if (is_resource($archivo)) fclose($archivo);
    }

    public function registrarFormatoCultiva()
    {
        $datos = self::getDatosSubirArchivo();
        $resultado = CapHumDAO::registraFormatoCultiva($datos);

        if (is_resource($datos['archivo'])) fclose($datos['archivo']);
        self::respuestaJSON($resultado);
    }

    public function getDatosSubirArchivo()
    {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return self::respuestaJSON(false, 'Archivo no recibido o error en la carga.');
        }

        if ($_FILES['archivo']['size'] > 5 * 1024 * 1024) {
            return self::respuestaJSON(false, "El archivo {$_FILES['archivo']['name']} excede el tamaño máximo permitido de 5 MB.");
        }

        return [
            'nombre' => $_POST['nombre'] ?? '',
            'archivo' => fopen($_FILES['archivo']['tmp_name'], 'rb'),
            'tipo' => $_FILES['archivo']['type']
        ];
    }

    public function RecuperacionViaticos()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"

                const getSolicitudes = () => {
                    consultaServidor("/CapHum/getSolicitudesRecuperacion", null, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)

                        const datos = respuesta.datos.map((solicitud) => {
                            const acciones = [
                                {
                                    texto: "Resolver con descuento vía nómina",
                                    icono: "fa-money-check-dollar",
                                    funcion: "descuentoNomina(" + solicitud.ID + ", " + solicitud.VIATICOS + ")"
                                },
                                {
                                    texto: "Cerrar sin descuento",
                                    icono: "fa-money-bill-trend-up",
                                    funcion: "cerrarSinDescuento(" + solicitud.ID + ", " + solicitud.VIATICOS + ")"
                                }
                            ]
                            
                            const diferencia = numeral(solicitud.DIFERENCIA)
                            let dif

                            if (diferencia.value() < 0) {
                                dif = "down text-danger"
                                acciones.push({
                                    texto: "Turnar a Tesorería",
                                    icono: "fa-user-tie",
                                    funcion: "enviarTS(" + solicitud.ID + ", " + solicitud.VIATICOS + ")"
                                })
                            } else {
                                dif = "up text-success"
                            }

                            const empresaSpan = "<span><strong style='color:" + (solicitud.EMPRESA == 1 ? "red" : "#4C1013") + "'>" + solicitud.EMPRESA_NOMBRE + "</strong> - " + solicitud.VIATICOS + "</span>"
                            const dias = moment().diff(moment(solicitud.FECHA_REGISTRO), 'days')
                            let badgeDias = ""

                            if (dias < 4) {
                                badgeDias = "<span class='badge bg-success'>" + dias + " días</span>"
                            } else if (dias > 4) {
                                badgeDias = "<span class='badge bg-danger text-dark'>" + dias + " días</span>"
                            } else {
                                badgeDias = "<span class='badge bg-warning'>" + dias + " días</span>"
                            }
                            
                            return [
                                null,
                                empresaSpan,
                                solicitud.USUARIO_NOMBRE,
                                diferencia.format(NUMERAL_MONEDA),
                                solicitud.MOTIVO,
                                null,
                                moment(solicitud.FECHA_REGISTRO).format(MOMENT_FRONT_HORA),
                                badgeDias,
                                menuAcciones(acciones)
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const descuentoNomina = (id, viaticos) => {
                    confirmarMovimiento("¿Confirma que desea resolver esta solicitud con descuento vía nómina?").then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            caso: id,
                            viaticos,
                            usuario: "{$_SESSION['usuario_id']}",
                            empresa: "{$_SESSION['empresa_id']}",
                            region: "{$_SESSION['region_id']}",
                            sucursal: "{$_SESSION['sucursal_id']}"
                        }
                        
                        consultaServidor("/CapHum/recuperacionPorNomina", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)

                            showSuccess(respuesta.mensaje).then(getSolicitudes)
                        })
                    })
                }

                const cerrarSinDescuento = (id, viaticos) => {
                    confirmarMovimiento("¿Confirma que desea cerrar esta solicitud sin descuento?").then((continuar) => {
                        if (!continuar.isConfirmed) return

                        showSuccess("La solicitud se cerro sin descuento.").then(getSolicitudes)
                    })
                }

                const enviarTS = (id, viaticos) => {
                    confirmarMovimiento("¿Confirma que desea turnar esta solicitud a Tesorería?").then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            caso: id,
                            viaticos,
                            usuario: "{$_SESSION['usuario_id']}",
                        }
                        
                        consultaServidor("/CapHum/delegarSaldoTS", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)

                            showSuccess(respuesta.mensaje).then(getSolicitudes)
                        })
                    })
                }

                $(document).ready(function() {
                    configuraTabla(tabla)
                    getSolicitudes()
                })
            </script>
        HTML;

        self::set("titulo", "Recuperación de Viáticos");
        self::set("script", $script);
        self::render("caphum_recuperacion_viaticos");
    }

    public function getSolicitudesRecuperacion()
    {
        self::respuestaJSON(CapHumDAO::getSolicitudesRecuperacion());
    }

    public function recuperacionPorNomina()
    {
        self::respuestaJSON(CapHumDAO::recuperacionPorNomina($_POST));
    }

    public function delegarSaldoTS()
    {
        self::respuestaJSON(CapHumDAO::delegarSaldoTS($_POST));
    }
}
