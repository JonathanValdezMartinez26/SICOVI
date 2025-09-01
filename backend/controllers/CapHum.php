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
                            valor: () => $("#contactoEmergenciaParentesco").val(),
                            texto: () => $("#contactoEmergenciaParentesco").val(),
                            validacion: () => true,
                            mensaje: "Ingrese un parentesco de contacto de emergencia."
                        },
                        contactoEmergenciaTelefono: {
                            elemento: "contactoEmergenciaTelefono",
                            valor: () => $("#contactoEmergenciaTelefono").val(),
                            texto: () => $("#contactoEmergenciaTelefono").val(),
                            validacion: () => true,
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
                        sucursal: {
                            elemento: "sucursal",
                            valor: () => $("#sucursal option:selected").val(),
                            texto: () => $("#sucursal option:selected").text(),
                            validacion: () => $("#sucursal option:selected").val() !== "",
                            mensaje: "Seleccione una sucursal."
                        },
                        region: {
                            elemento: "region",
                            valor: () => $("#region option:selected").val(),
                            texto: () => $("#region option:selected").text(),
                            validacion: () => $("#region option:selected").val() !== "",
                            mensaje: "Seleccione una región."
                        },
                        jefeInmediato: {
                            elemento: "jefeInmediato",
                            valor: () => $("#jefeInmediato option:selected").val(),
                            texto: () => $("#jefeInmediato option:selected").text(),
                            validacion: () => true, //$("#jefeInmediato option:selected").val() !== "",
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
                                    return correos.join(', ')
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
                        nomina: {
                            elemento: "nomina",
                            valor: () => $("#nomina option:selected").val(),
                            texto: () => $("#nomina option:selected").text(),
                            validacion: () => $("#nomina option:selected").val() !== "",
                            mensaje: "Seleccione una nómina."
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
                        wizardPersona = new Stepper(wizardElement, { linear: true, animation: true })
                        
                        const nextButtons = wizardElement.querySelectorAll('.btn-next')
                        const prevButtons = wizardElement.querySelectorAll('.btn-prev')

                        nextButtons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                if (validarPasoActual()) {
                                    wizardPersona.next()
                                    if (wizardPersona._currentIndex === wizardPersona._steps.length - 2) llenarResumen()
                                }
                            })
                        })

                        prevButtons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                wizardPersona.previous()
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

                    const datosActuales = {
                        nombre: $("#detalleNombre").val(),
                        apellido1: $("#detalleApellido1").val(),
                        apellido2: $("#detalleApellido2").val(),
                        rfc: $("#detalleRfc").val(),
                        curp: $("#detalleCurp").val(),
                        fechaNacimiento: $("#detalleFechaNacimiento").val(),
                        sexo: $("#detalleSexo").val()
                    }

                    for (const campo in datosActuales) {
                        if (datosActuales[campo] !== datosOriginales[campo]) {
                            return true
                        }
                    }
                    return false
                }

                const actualizarBotonEdicion = () => {
                    const tieneModificaciones = verificarCambios() || hayCambios
                    const boton = $("#btnHabilitarEdicion")
                    
                    if (tieneModificaciones) {
                        boton.removeClass("btn-warning").addClass("btn-success")
                        boton.html('<i class="fa fa-save">&nbsp;</i>Guardar')
                    } else {
                        boton.removeClass("btn-success").addClass("btn-warning")
                        boton.html('<i class="fa fa-times">&nbsp;</i>Cancelar')
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
                    const filtro = $("#filtroGeneral").val()
                    
                    const parametros = {
                        filtro: filtro
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

                            return [
                                null,
                                persona.ID,
                                fotoHtml,
                                persona.NOMBRE + " " + persona.APELLIDO_1 + " " + (persona.APELLIDO_2 || ""),
                                persona.RFC,
                                persona.CURP,
                                getFecha(persona.FECHA_NACIMIENTO),
                                getEstatus(persona.ESTATUS == 1 ? "Activo" : "Inactivo", persona.ESTATUS == 1 ? "success" : "danger"),
                                menuAcciones([ver, "divisor", eliminar])
                            ]
                        })

                        actualizaDatosTabla(tabla, datos, persistirVista)
                    })
                }

                const nuevaPersona = () => {
                    limpiarPersona()
                    $("#modalPersona").modal("show")
                    $("#tituloModalPersona").text("Registrar nuevo colaborador")
                    $("#personaId").val("")
                    
                    if (wizardPersona) {
                        wizardPersona.to(1)
                    }
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

                        // Resetear modo edición
                        modoEdicion = false
                        hayCambios = false


                        // Llenar campos del modal de detalle
                        $("#detallePersonaIdHidden").val(persona.ID)
                        $("#detallePersonaId").val(persona.ID)
                        $("#detalleNombre").val(persona.NOMBRE)
                        $("#detalleApellido1").val(persona.APELLIDO_1)
                        $("#detalleApellido2").val(persona.APELLIDO_2 || "")
                        $("#detalleRfc").val(persona.RFC)
                        $("#detalleCurp").val(persona.CURP)
                        $("#detalleFechaNacimiento").val(persona.FECHA_NACIMIENTO)
                        $("#detalleSexo").val(persona.SEXO)
                        $("#detalleEstatus").val(persona.ESTATUS)

                        telefonos.forEach(telefono => {
                            if (telefono.TIPO === "1") {
                                $("#detalleTelefonoPrincipal").val(telefono.NUMERO);
                            } else if (telefono.TIPO === "2") {
                                $("#detalleTelefonoAlterno").val(telefono.NUMERO);
                            }
                        });

                        emails.forEach(email => {
                            if (email.TIPO === "1") {
                                $("#detalleEmail").val(email.DIRECCION);
                            }
                        });

                        $("#detalleCalle").val(persona.CALLE_NUMERO)
                        $("#detalleCP").val(persona.CP);
                        const response = await fetch("https://api.condusef.gob.mx/sepomex/colonias/?cp=" + persona.CP);
                        const data = await response.json();
                        if (data?.colonias.length > 0) {
                            const datosCP = data.colonias.filter(item => item.coloniaId == persona.COLONIA);
                            const colonia = datosCP[0]?.colonia || "";
                            const municipio = datosCP[0]?.municipio || "";
                            const estado = datosCP[0]?.estado || "";
                            $("#detalleColonia").val(colonia);
                            $("#detalleMunicipio").val(municipio);
                            $("#detalleEstado").val(estado);
                        }

                        $("#detalleEmpresa").val(empresa.EMPRESA_NOMBRE);
                        $("#detalleRegion").val(empresa.REGION_NOMBRE);
                        $("#detalleSucursal").val(empresa.SUCURSAL_NOMBRE);
                        $("#detalleJefeDirecto").val(nomina.JEFE);

                        $("#detalleIngreso").val(nomina.INGRESO);
                        $("#detalleTipoNomina").val(nomina.TIPO);
                        $("#detalleNumeroNomina").val(nomina.NUMERO);
                        bancos.forEach(banco => {
                            $("#detalleBanco").val(banco.ID_BANCO);
                            if (banco.TIPO_NUMERO === "1") {
                                $("#detalleCuenta").val(banco.NUMERO);
                            } else if (banco.TIPO_NUMERO === "2") {
                                $("#detalleTarjeta").val(banco.NUMERO);
                            }
                        });

                        $("#detalleContactoEmergencia").val(contactos[0]?.NOMBRE || "");
                        $("#detalleParentescoCE").val(contactos[0]?.PARENTESCO || "");
                        $("#detalleTelefonoCE").val(contactos[0]?.TELEFONO || "");
                        $("#detalleCondicionesMedicas").val(persona.CONDICIONES_MEDICAS || "");
                        $("#detalleInfoAdicional").val(persona.OTROS_DATOS_RELEVANTES || "");


                        // Deshabilitar todos los campos de entrada
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", true)
                        $("#btnCambiarFotoDetalle").prop("disabled", true)
                        
                        // Restaurar botón de edición
                        $("#btnHabilitarEdicion").removeClass("btn-success").addClass("btn-warning")
                        $("#btnHabilitarEdicion").html('<i class="fa fa-edit">&nbsp;</i>Editar')

                        // Reutilizar foto de la tabla en lugar de descargarla nuevamente
                        let fotoSrc = "/assets/img/misc/user.svg" // Imagen por defecto
                        
                        try {
                            // Buscar directamente en el DOM de la tabla visible
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
                                        console.log('Foto encontrada en tabla:', fotoSrc)
                                    }
                                }
                            } else {
                                console.log('No se encontró la fila para ID:', persona.ID)
                            }
                        } catch (error) {
                            console.log('Error al buscar foto en tabla:', error)
                        }
                        
                        // Si no se encuentra la imagen en la tabla y hay foto, usar el endpoint como respaldo
                        if (fotoSrc === "/assets/img/misc/user.svg" && persona.FOTO) {
                            fotoSrc = "/CapHum/getFotoPersona?personaId=" + persona.ID
                            console.log('Usando endpoint como respaldo:', fotoSrc)
                        }
                        
                        console.log('Foto final asignada:', fotoSrc)
                        $("#detalleFoto").attr("src", fotoSrc)

                        // Llenar tabla de usuarios
                        $("#tablaUsuariosDetalle tbody").empty()
                        if (usuarios.length === 0) {
                            $("#tablaUsuariosDetalle tbody").append('<tr><td colspan="5" class="text-center">No hay usuarios asociados</td></tr>')
                        } else {
                            usuarios.forEach(usuario => {
                                // Crear acciones según la cantidad de usuarios
                                let acciones = [{
                                    texto: "Editar",
                                    icono: "fa-edit",
                                    funcion: "editarUsuario(" + usuario.ID + ")"
                                },{
                                    texto: usuario.ESTATUS == 1 ? "Desactivar" : "Activar",
                                    icono: usuario.ESTATUS == 1 ? "fa-ban" : "fa-check",
                                    funcion: "cambiarEstatusUsuario(" + usuario.ID + ")",
                                    clase: usuario.ESTATUS == 1 ? "text-warning" : "text-success"
                                }]
                                
                                // Solo agregar eliminar si hay más de un usuario
                                if (usuarios.length > 1) {
                                    acciones.push({
                                        texto: "Eliminar",
                                        icono: "fa-trash",
                                        funcion: "eliminarUsuario(" + usuario.ID + ")",
                                        clase: "text-danger"
                                    })
                                }
                                
                                const menuAccs = menuAcciones(acciones)
                                const estatusBadge = getEstatus(usuario.ESTATUS == 1 ? "Activo" : "Inactivo", usuario.ESTATUS == 1 ? "success" : "danger")
                                $("#tablaUsuariosDetalle tbody").append("<tr><td>" + usuario.ID + "</td><td>" + usuario.USUARIO + "</td><td>" + (usuario.SUCURSAL_NOMBRE || 'N/A') + "</td><td>" + estatusBadge + "</td><td>" + menuAccs + "</td></tr>")
                            })
                        }

                        $("#modalDetallePersona").modal("show")
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
                    $('.fv-message').text('')
                }

                const inhabilitarPersona = () => {
                    const id = $("#detallePersonaIdHidden").val()
                    if (!id) return showError("No se ha seleccionado una persona")
                    
                    confirmaEliminar("¿Está seguro de desactivar esta persona?", () => {
                        consultaServidor("/CapHum/eliminarPersona", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            $("#modalDetallePersona").modal("hide")
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

                const getPersonalSucursal = (sucursal) => {
                    const parametros = {
                        sucursal
                    }

                    consultaServidor("/CapHum/getPersonalSucursal", parametros, (respuesta) => {
                        if (!respuesta.success) {
                            return showError(respuesta.mensaje)
                        }

                        const personal = respuesta.datos
                        let  opciones = "<option value='' selected disabled>Seleccione un jefe</option>"
                        opciones += personal.map((miembro) => {
                            return "<option value='" + miembro.ID + "'>" + miembro.NOMBRE + "</option>"
                        }).join("")

                        $("#jefeInmediato").html(opciones)
                        $("#jefeInmediato").prop("disabled", false)

                        $("#reporta").html(opciones)
                        $("#reporta").prop("disabled", false)
                    })
                }

                $(document).ready(() => {
                    const maxF = moment().subtract(18, 'years').format('YYYY-MM-DD');
                    const minF = moment().subtract(70, 'years').format('YYYY-MM-DD');
                    setInputFechas("#fechaNacimiento", { minF, maxF, iniF: maxF, enModal: true })
                    setInputFechas("#fechaIngreso", { minD: -1800, maxD: 7, enModal: true })
                    configuraTabla(tabla)
                    initWizard()
                    getPersonas()
                    
                    window.agregarCorreo = agregarCorreo
                    window.eliminarCorreo = eliminarCorreo
                    
                    $("#btnBuscar").click(() => getPersonas())
                    $("#btnNuevaPersona").click(nuevaPersona)
                    $("#btnHabilitarEdicion").click(habilitarEdicion)
                    $("#btnInhabilitarPersona").click(inhabilitarPersona)
                    $("#btnCambiarFoto").click(() => $("#fotoInput").click())
                    $("#btnCambiarFotoDetalle").click(() => $("#detalleFotoInput").click())
                    $("#btnCambiarFotoResumen").click(() => $("#resumenFotoInput").click())

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

                    $('#empresa').on('change', () => {
                        const empresaId = $('#empresa option:selected').val()

                        $('#region').prop('disabled', !empresaId)
                        $('#region').val('')
                        $('#region option').each(function() {
                            const regionEmpresaId = $(this).attr('data-empresa')
                            if (regionEmpresaId !== empresaId) $(this).hide()
                            else $(this).show()
                        })

                        $('#sucursal').prop('disabled', true)
                        $('#sucursal').val('')
                    })


                    $('#region').on('change', () => {
                        const empresaId = $('#empresa option:selected').val()
                        const regionId = $('#region option:selected').val()
                        
                        $('#sucursal').prop('disabled', !regionId)
                        $('#sucursal').val('')
                        $('#sucursal option').each(function() {
                            const sucursalEmpresaId = $(this).attr('data-empresa')
                            const sucursalRegionId = $(this).attr('data-region')
                            
                            if (sucursalRegionId !== regionId || sucursalEmpresaId !== empresaId) $(this).hide()
                            else $(this).show()
                        })
                    })

                    $("#sucursal").on("change", () => {
                        const sucursalId = $("#sucursal option:selected").val()
                        getPersonalSucursal(sucursalId)
                    })

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
                    
                    $('#codigoPostal').on('input', function() {
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

        $this->set('titulo', 'Gestión Capital Humano | ' . CONFIGURACION['EMPRESA']);
        $this->set('script', $script);
        $this->set('css', '<link rel="stylesheet" href="/assets/css/wizard-rh.css">');
        $this->set("empresas", $optionsSucursales['empresas']);
        $this->set("sucursales", $optionsSucursales['sucursales']);
        $this->set("regiones", $optionsSucursales['regiones']);
        $this->set("bancos", $bancos);
        $this->set("perfiles", $perfiles);
        $this->render('rh_gestion');
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
}
