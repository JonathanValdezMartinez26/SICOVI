<?php

namespace Controllers;

use Core\Controller;
use Models\Rh as RhDAO;

class Rh extends Controller
{
    public function GestionRh()
    {
        $script = <<<HTML
            <script>
                const tabla = "#tablaPersonas"
                let valPersona = null,
                    modalPersona = null

                const getFecha = (fecha) => {
                    return fecha ? moment(fecha).format(MOMENT_FRONT) : '-'
                }

                const getEstatus = (texto, tipo) => {
                    const colorClass = tipo === 'success' ? 'text-bg-success' : 'text-bg-danger'
                    return `<span class="badge \${colorClass}">\${texto}</span>`
                }

                const validacionPersona = () => {
                    const campos = {
                        nombre: {
                            notEmpty: {
                                message: "El nombre es requerido"
                            },
                            stringLength: {
                                max: 50,
                                message: "Máximo 50 caracteres"
                            }
                        },
                        apellido1: {
                            notEmpty: {
                                message: "El apellido paterno es requerido"
                            },
                            stringLength: {
                                max: 50,
                                message: "Máximo 50 caracteres"
                            }
                        },
                        fechaNacimiento: {
                            notEmpty: {
                                message: "La fecha de nacimiento es requerida"
                            },
                            callback: {
                                callback: (input) => {
                                    const fecha = getInputFecha("#fechaNacimiento")
                                    return fecha !== null ? true : false
                                },
                                message: "Fecha de nacimiento inválida"
                            }
                        },
                        sexo: {
                            notEmpty: {
                                message: "El sexo es requerido"
                            }
                        },
                        rfc: {
                            notEmpty: {
                                message: "El RFC es requerido"
                            },
                            stringLength: {
                                max: 13,
                                message: "Máximo 13 caracteres"
                            }
                        },
                        curp: {
                            notEmpty: {
                                message: "El CURP es requerido"
                            },
                            stringLength: {
                                max: 18,
                                message: "Máximo 18 caracteres"
                            }
                        },
                        fechaNacimiento: {
                            notEmpty: {
                                message: "La fecha de nacimiento es requerida"
                            },
                            callback: {
                                callback: (input) => {
                                    const fecha = getInputFecha("#fechaNacimiento")
                                    return fecha !== null ? true : false
                                },
                                message: "Fecha de nacimiento inválida"
                            }
                        }
                    }

                    valPersona = setValidacionModal(
                        "#modalPersona",
                        campos,
                        "#btnGuardarPersona",
                        guardarPersona,
                        "#cancelaSolicitud"
                    )
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

                // Variables para el modo edición
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
                        // Entrar en modo edición
                        modoEdicion = true
                        hayCambios = false
                        
                        // Guardar datos originales
                        datosOriginales = {
                            nombre: $("#detalleNombre").val(),
                            apellido1: $("#detalleApellido1").val(),
                            apellido2: $("#detalleApellido2").val(),
                            rfc: $("#detalleRfc").val(),
                            curp: $("#detalleCurp").val(),
                            fechaNacimiento: $("#detalleFechaNacimiento").val(),
                            sexo: $("#detalleSexo").val()
                        }

                        // Habilitar campos (excepto ID)
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", false)
                        $("#btnCambiarFotoDetalle").prop("disabled", false)
                        
                        // Cambiar botón
                        $("#btnHabilitarEdicion").removeClass("btn-warning").addClass("btn-warning")
                        $("#btnHabilitarEdicion").html('<i class="fa fa-times">&nbsp;</i>Cancelar')
                        
                        // Agregar listeners para detectar cambios
                        $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").on('input change', actualizarBotonEdicion)
                        
                    } else {
                        // Verificar si hay cambios para guardar
                        if (verificarCambios() || hayCambios) {
                            // Guardar cambios
                            guardarEdicion()
                        } else {
                            // Cancelar edición
                            cancelarEdicion()
                        }
                    }
                }

                const cancelarEdicion = () => {
                    modoEdicion = false
                    hayCambios = false
                    
                    // Restaurar datos originales
                    $("#detalleNombre").val(datosOriginales.nombre)
                    $("#detalleApellido1").val(datosOriginales.apellido1)
                    $("#detalleApellido2").val(datosOriginales.apellido2)
                    $("#detalleRfc").val(datosOriginales.rfc)
                    $("#detalleCurp").val(datosOriginales.curp)
                    $("#detalleFechaNacimiento").val(datosOriginales.fechaNacimiento)
                    $("#detalleSexo").val(datosOriginales.sexo)
                    
                    // Deshabilitar campos
                    $("#detalleNombre, #detalleApellido1, #detalleApellido2, #detalleRfc, #detalleCurp, #detalleFechaNacimiento, #detalleSexo").prop("disabled", true)
                    $("#btnCambiarFotoDetalle").prop("disabled", true)
                    
                    // Restaurar botón
                    $("#btnHabilitarEdicion").removeClass("btn-success btn-warning").addClass("btn-warning")
                    $("#btnHabilitarEdicion").html('<i class="fa fa-edit">&nbsp;</i>Editar')
                    
                    // Remover listeners
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

                    consultaServidor("/rh/guardarPersona", datos, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        showSuccess(respuesta.mensaje)
                        
                        // Salir del modo edición
                        cancelarEdicion()
                        
                        // Actualizar tabla
                        getPersonas(true)
                        
                        // Actualizar datos mostrados
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

                    consultaServidor("/rh/getPersonas", parametros, (respuesta) => {
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
                            
                            return [
                                null,
                                persona.ID,
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
                    $("#tituloModalPersona").text("Registrar nueva persona")
                    $("#personaId").val("")
                    $("#btnGuardarPersona").text("Registrar")
                }

                const verPersona = (id) => {
                    consultaServidor("/rh/getPersonaDetalle", {id: id}, (respuesta) => {
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
                            $("#tablaUsuariosDetalle tbody").append('<tr><td colspan="4" class="text-center">No hay usuarios asociados</td></tr>')
                        } else {
                            usuarios.forEach(usuario => {
                                const acciones = menuAcciones([{
                                                    texto: "Editar",
                                                    icono: "fa-edit",
                                                    funcion: "editarUsuario(" + usuario.ID + ")"
                                                },{
                                                    texto: "Desactivar",
                                                    icono: "fa-ban",
                                                    funcion: "inhabilitarUsuario(" + usuario.ID + ")"
                                                },{
                                                    texto: "Eliminar",
                                                    icono: "fa-trash",
                                                    funcion: "eliminarUsuario(" + usuario.ID + ")"
                                                }])
                                const estatusBadge = getEstatus(usuario.ESTATUS == 1 ? "Activo" : "Inactivo", usuario.ESTATUS == 1 ? "success" : "danger")
                                $("#tablaUsuariosDetalle tbody").append("<tr><td>" + usuario.ID + "</td><td>" + usuario.USUARIO + "</td><td>" + estatusBadge + "</td><td>" + acciones + "</td></tr>")
                            })
                        }

                        $("#modalDetallePersona").modal("show")
                    })
                }

                const eliminarPersona = (id) => {
                    confirmaEliminar("¿Está seguro de eliminar esta persona?", () => {
                        consultaServidor("/rh/eliminarPersona", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            getPersonas(true)
                        })
                    })
                }

                const guardarPersona = () => {
                    const datos = {
                        id: $("#personaId").val(),
                        nombre: $("#nombre").val(),
                        apellido1: $("#apellido1").val(),
                        apellido2: $("#apellido2").val(),
                        rfc: $("#rfc").val(),
                        curp: $("#curp").val(),
                        fechaNacimiento: $("#fechaNacimiento").val(),
                        sexo: $("#sexo").val(),
                        usuario: $("#usuario").val(),
                        pass: $("#pass").val()
                    }

                    consultaServidor("/rh/guardarPersona", datos, (respuesta) => {
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
                    $("#fotoInput").val("")
                    $("#fotoPreview").attr("src", "/assets/img/misc/user.svg")
                }

                const inhabilitarPersona = () => {
                    const id = $("#detallePersonaIdHidden").val()
                    if (!id) return showError("No se ha seleccionado una persona")
                    
                    confirmaEliminar("¿Está seguro de desactivar esta persona?", () => {
                        consultaServidor("/rh/eliminarPersona", {id: id}, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(respuesta.mensaje)
                            $("#modalDetallePersona").modal("hide")
                            getPersonas(true)
                        })
                    })
                }

                $(document).ready(() => {
                    // Inicializar tabla
                    configuraTabla(tabla)
                    
                    getPersonas()
                    validacionPersona()

                    // Event listeners
                    $("#btnBuscar").click(() => getPersonas())
                    $("#btnNuevaPersona").click(nuevaPersona)
                    $("#btnHabilitarEdicion").click(habilitarEdicion)
                    $("#btnInhabilitarPersona").click(inhabilitarPersona)

                    // Event listeners para cambio de foto
                    $("#btnCambiarFoto").click(() => $("#fotoInput").click())
                    $("#btnCambiarFotoDetalle").click(() => $("#detalleFotoInput").click())
                    
                    // Manejar cambios de foto
                    manejarCambiosFoto("#fotoInput", "#fotoPreview")
                    manejarCambiosFoto("#detalleFotoInput", "#detalleFotoPreview")

                    // Filtro de tabla
                    $("#filtroTabla").on("keyup", function() {
                        const valor = $(this).val().toLowerCase()
                        const tabla = $("#tablaPersonas").DataTable()
                        tabla.search(valor).draw()
                    })
                })
            </script>
        HTML;

        $catSucursales = RhDAO::getCatalogoSucursales();
        $sucursales = '';
        $regiones = '';
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

        $this->set('titulo', 'Gestión RH | ' . CONFIGURACION['EMPRESA']);
        $this->set('script', $script);
        $this->set("regiones", $regiones);
        self::set("sucursales", $sucursales);
        $this->render('rh_gestion');
    }

    public function getPersonas()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $filtro = $_POST['filtro'] ?? '';

        $personas = RhDAO::getPersonas(['filtro' => $filtro]);
        $this->respuestaJSON($personas);
    }

    public function getPersonaDetalle()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $detalle = RhDAO::getPersonaDetalle(['id' => $id]);
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
            'pass' => $_POST['pass'] ?? ''
        ];

        $resultado = RhDAO::guardarPersona($datos);
        $this->respuestaJSON($resultado);
    }

    public function eliminarPersona()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = $_POST['id'] ?? 0;

        $resultado = RhDAO::eliminarPersona(['id' => $id]);
        $this->respuestaJSON($resultado);
    }
}
