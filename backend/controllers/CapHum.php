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
                let valPersona = null,
                    modalPersona = null,
                    wizardPersona = null

                const getFecha = (fecha) => {
                    return fecha ? moment(fecha).format(MOMENT_FRONT) : '-'
                }

                const getEstatus = (texto, tipo) => {
                    const colorClass = tipo === 'success' ? 'text-bg-success' : 'text-bg-danger'
                    return `<span class="badge \${colorClass}">\${texto}</span>`
                }
                
                const initWizard = () => {
                    const wizardElement = document.querySelector('.wizard-icons-example')
                    if (wizardElement) {
                        wizardPersona = new Stepper(wizardElement, {
                            linear: false
                        })
                        
                        const nextButtons = wizardElement.querySelectorAll('.btn-next')
                        const prevButtons = wizardElement.querySelectorAll('.btn-prev')

                        nextButtons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                if (validarPasoActual()) {
                                    wizardPersona.next()
                                    if (wizardPersona._currentIndex === 3) {
                                        llenarResumen()
                                    }
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
                            return validarDatosPersonales()
                        case 1:
                            return validarDatosEmpresa()
                        case 2:
                            return validarDatosUsuario()
                        case 3:
                            return true
                        default:
                            return true
                    }
                }

                const validarDatosPersonales = () => {
                    let valido = true
                    
                    $('.fv-message').text('')
                    
                    const campos = ['nombre', 'apellido1', 'sexo', 'fechaNacimiento', 'rfc', 'curp']
                    
                    campos.forEach(campo => {
                        const valor = $(`#\${campo}`).val()
                        if (!valor || valor.trim() === '') {
                            let parent = null
                            if (campo === 'fechaNacimiento') parent = $(`#\${campo}`).parent()
                            else parent = $(`#\${campo}`)
                            parent.siblings('.fv-message').text('Este campo es requerido')
                            valido = false
                        } else {
                            if (campo === 'rfc') {
                                if (valor && valor.length !== 13) {
                                    $(`#\${campo}`).siblings('.fv-message').text('El RFC debe tener 13 caracteres')
                                    valido = false
                                }
                            } else if (campo === 'curp') {
                                if (valor && valor.length !== 18) {
                                    $(`#\${campo}`).siblings('.fv-message').text('La CURP debe tener 18 caracteres')
                                    valido = false
                                }
                            } 
                        }


                    })

                    if (!valido) {
                        showError('Por favor complete todos los campos requeridos')
                    }

                    return valido
                }

                const validarDatosEmpresa = () => {
                    let valido = true
                    $('.fv-message').text('')
                    
                    const campos = ['empresaWizard', 'sucursalWizard', 'puesto', 'nomina', 'tipoNomina', 'numeroNomina']
                    
                    campos.forEach(campo => {
                        const valor = $(`#\${campo}`).val()
                        if (!valor || valor.trim() === '') {
                            $(`#\${campo}`).siblings('.fv-message').text('Este campo es requerido')
                            valido = false
                        }
                    })

                    // Validar al menos un correo empresarial
                    const correos = $('input[name="correoEmpresa[]"]').filter(function() {
                        return $(this).val().trim() !== ''
                    })
                    
                    if (correos.length === 0) {
                        $('#correosContainer .fv-message').first().text('Debe agregar al menos un correo empresarial')
                        valido = false
                    }

                    if (!valido) {
                        showError('Por favor complete todos los campos requeridos del paso empresa')
                    }

                    return valido
                }

                const validarDatosUsuario = () => {
                    let valido = true
                    $('.fv-message').text('')
                    
                    const campos = ['usuario', 'pass', 'perfil']
                    
                    campos.forEach(campo => {
                        const valor = $(`#\${campo}`).val()
                        if (!valor || valor.trim() === '') {
                            $(`#\${campo}`).siblings('.fv-message').text('Este campo es requerido')
                            valido = false
                        }
                    })

                    const password = $('#pass').val()
                    if (password && password.length < 6) {
                        $('#pass').siblings('.fv-message').text('La contraseña debe tener al menos 6 caracteres')
                        valido = false
                    }

                    if (!valido) {
                        showError('Por favor complete todos los campos requeridos')
                    }

                    return valido
                }

                const llenarResumen = () => {
                    const fotoSrc = $('#fotoPreview').attr('src')
                    const nombre = $('#nombre').val()
                    const apellido1 = $('#apellido1').val()
                    const apellido2 = $('#apellido2').val() || ''
                    const nombreCompleto = nombre + ' ' + apellido1 + ' ' + apellido2

                    // Datos personales
                    $('#resumenFoto').attr('src', fotoSrc)
                    $('#resumenNombre').text(nombreCompleto.trim())
                    $('#resumenRfc').text($('#rfc').val())
                    $('#resumenCurp').text($('#curp').val())
                    $('#resumenFechaNac').text($('#fechaNacimiento').val())
                    $('#resumenSexo').text($('#sexo option:selected').text())
                    
                    // Datos empresa
                    $('#resumenEmpresa').text($('#empresaWizard option:selected').text())
                    $('#resumenRegion').text($('#regionWizard').val())
                    $('#resumenSucursal').text($('#sucursalWizard option:selected').text())
                    $('#resumenPuesto').text($('#puesto option:selected').text())
                    $('#resumenNomina').text($('#nomina option:selected').text())
                    $('#resumenTipoNomina').text($('#tipoNomina option:selected').text())
                    $('#resumenNumeroNomina').text($('#numeroNomina').val())
                    $('#resumenJefeInmediato').text($('#jefeInmediato option:selected').text())
                    $('#resumenReporta').text($('#reporta option:selected').text())
                    
                    // Correos empresariales
                    const correos = []
                    $('input[name="correoEmpresa[]"]').each(function() {
                        const valor = $(this).val().trim()
                        if (valor) correos.push(valor)
                    })
                    $('#resumenCorreosEmpresa').text(correos.join(', '))
                    
                    // Datos usuario
                    $('#resumenUsuario').text($('#usuario').val())
                    $('#resumenPerfil').text($('#perfil option:selected').text())
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
                
                
                // Función para agregar correos adicionales
                const agregarCorreo = () => {
                    const container = $('#correosContainer')
                    const nuevoCorreo = '<div class="input-group mb-2"><input type="email" name="correoEmpresa[]" class="form-control" placeholder="correo@empresa.com"><button type="button" class="btn btn-outline-danger" onclick="eliminarCorreo(this)"><i class="fa fa-minus"></i></div>'
                    container.append(nuevoCorreo)
                }
                
                // Función para eliminar correo
                const eliminarCorreo = (btn) => {
                    $(btn).closest('.input-group').remove()
                }
                
                // Función para toggle de contraseña
                const togglePassword = () => {
                    const passwordField = document.getElementById('pass')
                    const passwordIcon = document.getElementById('passwordIcon')
                    
                    if (passwordField && passwordIcon) {
                        if (passwordField.type === 'password') {
                            passwordField.type = 'text'
                            passwordIcon.classList.remove('fa-eye-slash')
                            passwordIcon.classList.add('fa-eye')
                        } else {
                            passwordField.type = 'password'
                            passwordIcon.classList.remove('fa-eye')
                            passwordIcon.classList.add('fa-eye-slash')
                        }
                    }
                }
                
                // Función para llenar región automáticamente
                const actualizarRegion = () => {
                    const sucursalId = $('#sucursalWizard').val()
                    if (sucursalId) {
                        // Aquí se haría una consulta AJAX para obtener la región
                        // Por ahora simularemos con datos estáticos
                        const regionTexto = "Región " + sucursalId // Placeholder
                        $('#regionWizard').val(regionTexto)
                    }
                }
                
                let modoEdicion = false
                let datosOriginales = {}
                let hayCambios = false

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
                            
                            const fotoUrl = persona.FOTO ? "/uploads/fotos/" + persona.FOTO : "/assets/img/misc/user.svg"
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
                    consultaServidor("/CapHum/getPersonaDetalle", {id: id}, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        
                        const persona = respuesta.datos.persona
                        const usuarios = respuesta.datos.usuarios

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
                        
                        // Deshabilitar todos los campos de entrada
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", true)
                        $("#btnCambiarFotoDetalle").prop("disabled", true)
                        
                        // Restaurar botón de edición
                        $("#btnHabilitarEdicion").removeClass("btn-success").addClass("btn-warning")
                        $("#btnHabilitarEdicion").html('<i class="fa fa-edit">&nbsp;</i>Editar')
                        
                        // Mostrar estatus
                        const estatusBadge = getEstatus(persona.ESTATUS == 1 ? "Activo" : "Inactivo", persona.ESTATUS == 1 ? "success" : "danger")
                        $("#detalleEstatus").html(estatusBadge)

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

                const guardarPersona = () => {
                    if (!validarDatosPersonales() || !validarDatosEmpresa() || !validarDatosUsuario()) {
                        showError('Por favor complete todos los campos requeridos')
                        return
                    }

                    const fecha = getInputFechas("#fechaNacimiento", false, true)

                    const datos = {
                        id: $("#personaId").val(),
                        nombre: $("#nombre").val(),
                        apellido1: $("#apellido1").val(),
                        apellido2: $("#apellido2").val(),
                        rfc: $("#rfc").val(),
                        curp: $("#curp").val(),
                        fechaNacimiento: fecha,
                        sexo: $("#sexo").val(),
                        usuario: $("#usuario").val(),
                        pass: $("#pass").val(),
                        perfil: $("#perfil").val(),
                        empresa: $("#empresaWizard").val(),
                        region: $("#regionWizard").val(),
                        sucursal: $("#sucursalWizard").val(),
                        puesto: $("#puesto").val(),
                        nomina: $("#nomina").val(),
                        tipoNomina: $("#tipoNomina").val(),
                        numeroNomina: $("#numeroNomina").val(),
                        jefeInmediato: $("#jefeInmediato").val(),
                        reporta: $("#reporta").val(),
                        correosEmpresa: []
                    }
                    
                    // Recopilar correos empresariales
                    $('input[name="correoEmpresa[]"]').each(function() {
                        const correo = $(this).val().trim()
                        if (correo) datos.correosEmpresa.push(correo)
                    })

                    consultaServidor("/CapHum/guardarPersona", datos, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        $("#modalPersona").modal("hide")
                        getPersonas(true)
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
                    $("#pass").val("")
                    $("#perfil").val("")
                    $("#empresaWizard").val("")
                    $("#regionWizard").val("")
                    $("#sucursalWizard").val("")
                    $("#puesto").val("")
                    $("#nomina").val("")
                    $("#tipoNomina").val("")
                    $("#numeroNomina").val("")
                    $("#jefeInmediato").val("")
                    $("#reporta").val("")
                    
                    // Limpiar correos empresariales
                    $('#correosContainer').html('<div class="input-group mb-2"><input type="email" name="correoEmpresa[]" class="form-control" placeholder="correo@empresa.com"><button type="button" class="btn btn-outline-success" onclick="agregarCorreo()"><i class="fa fa-plus"></i></button></div>')
                    
                    $("#fotoInput").val("")
                    $("#fotoPreview").attr("src", "/assets/img/misc/user.svg")
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

                $(document).ready(() => {
                    const maxF = moment().subtract(18, 'years').format('YYYY-MM-DD');
                    const minF = moment().subtract(70, 'years').format('YYYY-MM-DD');
                    setInputFechas("#fechaNacimiento", { minF, maxF, iniF: maxF, enModal: true })
                    configuraTabla(tabla)
                    initWizard()
                    getPersonas()

                    
                // Declarar funciones globalmente para uso en onclick
                window.agregarCorreo = agregarCorreo
                window.eliminarCorreo = eliminarCorreo
                window.togglePassword = togglePassword
                    
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
                    
                    // Event listener para toggle de contraseña
                    $(document).on('click', '#togglePassword', togglePassword)
                    
                    // También agregar event listener directo cuando el modal se abra
                    $('#modalPersona').on('shown.bs.modal', function() {
                        $('#togglePassword').off('click').on('click', togglePassword)
                    })
                    
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
                    
                    // Event listeners para el nuevo paso de empresa
                    $('#sucursalWizard').change(actualizarRegion)
                    
                    // Event listener para llenar reporta cuando se selecciona jefe inmediato
                    $('#jefeInmediato').change(function() {
                        const valor = $(this).val()
                        const texto = $(this).find('option:selected').text()
                        if (valor) {
                            $('#reporta').val(valor)
                            $('#reporta option').prop('selected', false)
                            $('#reporta option[value="' + valor + '"]').prop('selected', true)
                        }
                    })
                })
            </script>
        HTML;

        $catSucursales = CapHumDAO::getCatalogoSucursales();
        $catEmpresas = CapHumDAO::getCatalogoEmpresas();
        $sucursales = '';
        $regiones = '';
        $empresas = '';
        $rgnTmp = [];

        if ($catSucursales['success']) {
            foreach ($catSucursales['datos'] as $sucursal) {
                $seleccion = $_SESSION['sucursal_id'] == $sucursal['ID'] ? 'selected' : '';
                $sucursales .= "<option value='{$sucursal['ID']}' data-region='{$sucursal['REGION_ID']}' $seleccion>{$sucursal['NOMBRE']}</option>";

                if (!in_array($sucursal['REGION_ID'], $rgnTmp)) {
                    $rgnTmp[] = $sucursal['REGION_ID'];
                    $regiones .= "<option value='{$sucursal['REGION_ID']}'>{$sucursal['REGION_NOMBRE']}</option>";
                }
            }
        }

        if ($catEmpresas['success']) {
            foreach ($catEmpresas['datos'] as $empresa) {
                $empresas .= "<option value='{$empresa['ID']}'>{$empresa['RAZON_SOCIAL']}</option>";
            }
        }

        $this->set('titulo', 'Gestión Capital Humano | ' . CONFIGURACION['EMPRESA']);
        $this->set('script', $script);
        $this->set('css', '<link rel="stylesheet" href="/assets/css/wizard-rh.css">');
        $this->set("regiones", $regiones);
        $this->set("sucursales", $sucursales);
        $this->set("empresas", $empresas);
        $this->render('rh_gestion');
    }

    public function getPersonas()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $filtro = $_POST['filtro'] ?? '';

        $personas = CapHumDAO::getPersonas(['filtro' => $filtro]);
        $this->respuestaJSON($personas);
    }

    public function getPersonaDetalle()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $detalle = CapHumDAO::getPersonaDetalle(['id' => $id]);
        $this->respuestaJSON($detalle);
    }

    public function guardarPersona()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $datos = [
            'id' => $_POST['id'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'apellido1' => $_POST['apellido1'] ?? '',
            'apellido2' => $_POST['apellido2'] ?? '',
            'rfc' => $_POST['rfc'] ?? '',
            'curp' => $_POST['curp'] ?? '',
            'fechaNacimiento' => $_POST['fechaNacimiento'] ?? '',
            'sexo' => $_POST['sexo'] ?? '',
            'usuario' => $_POST['usuario'] ?? '',
            'pass' => $_POST['pass'] ?? '',
            'region' => $_POST['region'] ?? '',
            'sucursal' => $_POST['sucursal'] ?? '',
            'perfil' => $_POST['perfil'] ?? '',
            'empresa' => $_POST['empresa'] ?? ''
        ];

        $resultado = CapHumDAO::guardarPersona($datos);
        $this->respuestaJSON($resultado);
    }

    public function eliminarPersona()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $resultado = CapHumDAO::eliminarPersona(['id' => $id]);
        $this->respuestaJSON($resultado);
    }

    public function getUsuarioDetalle()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $detalle = CapHumDAO::getUsuarioDetalle(['id' => $id]);
        $this->respuestaJSON($detalle);
    }

    public function guardarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $datos = [
            'id' => $_POST['id'] ?? '',
            'persona' => $_POST['persona'] ?? '',
            'usuario' => $_POST['usuario'] ?? '',
            'pass' => $_POST['pass'] ?? '',
            'region' => $_POST['region'] ?? '',
            'sucursal' => $_POST['sucursal'] ?? '',
            'perfil' => $_POST['perfil'] ?? '',
            'empresa' => $_POST['empresa'] ?? '',
            'estatus' => $_POST['estatus'] ?? 1
        ];

        $resultado = CapHumDAO::guardarUsuario($datos);
        $this->respuestaJSON($resultado);
    }

    public function cambiarEstatusUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;
        $estatus = $_POST['estatus'] ?? 1;

        $resultado = CapHumDAO::cambiarEstatusUsuario(['id' => $id, 'estatus' => $estatus]);
        $this->respuestaJSON($resultado);
    }

    public function eliminarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $resultado = CapHumDAO::eliminarUsuario(['id' => $id]);
        $this->respuestaJSON($resultado);
    }
}
