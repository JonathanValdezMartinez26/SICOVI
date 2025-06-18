<?php

namespace Controllers;

use Core\Controller;
use Models\Viaticos as ViaticosDAO;

class Viaticos extends Controller
{
    public function Solicitud()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"
                const comprobantesGastos = []
                let valSolicitud = null,
                    valComprobante = null

                const getSolicitudes = (persistirVista = false) => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        usuario: $_SESSION[usuario_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesUsuario", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const resumen = {}
                        const datos = respuesta.datos.map((solicitud) => {
                            const ver = {
                                texto: "Detalles",
                                icono: "fa-eye",
                                funcion: "verSolicitud(" + solicitud.ID + ")"
                            }

                            const e = {
                                texto: "Editar",
                                icono: "fa-pen-to-square",
                                funcion: "editarSolicitud(" + solicitud.ID + ")",
                            }

                            const c = {
                                texto: "Cancelar",
                                icono: "fa-trash",
                                funcion: "cancelarSolicitud(" + solicitud.ID + ")",
                                clase: "text-danger delete-record"
                            }

                            let editar = null, cancelar = null

                            if (solicitud.TIPO_ID == 1) {
                                editar = solicitud.ESTATUS_ID == 1 ? e : null
                                cancelar = solicitud.ESTATUS_ID == 1 || solicitud.ESTATUS_ID == 2 ? c : null
                            }

                            if (solicitud.TIPO_ID == 2) {
                                editar = solicitud.ESTATUS_ID == 4 ? e : null
                                cancelar = solicitud.ESTATUS_ID == 4 || solicitud.ESTATUS_ID == 2 ? c : null
                            }

                            const estatusBadge = "<span class='badge rounded-pill " + solicitud.ESTATUS_COLOR + "'>" + solicitud.ESTATUS_NOMBRE + "</span>"
                            if (!resumen[solicitud.ESTATUS_ID]) {
                                resumen[solicitud.ESTATUS_ID] = {}
                                resumen[solicitud.ESTATUS_ID].total = 1
                                resumen[solicitud.ESTATUS_ID].color = solicitud.ESTATUS_COLOR
                                resumen[solicitud.ESTATUS_ID].estatus = solicitud.ESTATUS_NOMBRE
                            } else resumen[solicitud.ESTATUS_ID].total += 1

                            const entregado = numeral(solicitud.ENTREGA_MONTO || 0).value()
                            const comprobado = numeral(solicitud.COMPROBACION_MONTO || 0).value()
                            let diferencia = numeral(comprobado).subtract(entregado).value()
                            diferencia = "<span class='" + (diferencia < 0 ? "text-danger" : diferencia > 0 ? "text-success" : "") + "'>" + numeral(diferencia).format(NUMERAL_MONEDA) + "</span>"

                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                moment(solicitud.REGISTRO).format(MOMENT_FRONT),
                                solicitud.PROYECTO,
                                numeral(entregado).format(NUMERAL_MONEDA),
                                numeral(comprobado).format(NUMERAL_MONEDA),
                                diferencia,
                                estatusBadge,
                                menuAcciones([ver, editar || cancelar ? "divisor" : null, editar, cancelar])
                            ]
                        })

                        actualizaDatosTabla(tabla, datos, persistirVista)
                        $("#resumenSolicitudes").empty()
                        if (datos.length === 0) {
                            $("#resumenSolicitudes").append(
                                getTarjetaSolicitud("text-bg-dark", "Sin solicitudes", 0)
                            )
                        } else {
                            Object.keys(resumen)
                                .sort((a, b) => {
                                    return a - b
                                })
                                .forEach((estatusId) => {
                                    const tarjeta = getTarjetaSolicitud(
                                        resumen[estatusId].color,
                                        resumen[estatusId].estatus,
                                        resumen[estatusId].total
                                    )
                                    $("#resumenSolicitudes").append(tarjeta)
                                })
                        }
                    })
                }

                const getTarjetaSolicitud = (color, titulo, total) => {
                    return (
                        "<div class='col-auto'>" +
                        "<div class='card'>" +
                        "<div class='card-body'>" +
                        "<div class='card-info text-center'>" +
                        "<div class='d-flex flex-column align-items-center justify-content-center'>" +
                        "<span class='badge rounded-pill " +
                        color +
                        "'>" +
                        titulo +
                        "</span>" +
                        "</div>" +
                        "<h4 class='card-title mb-0 me-2'>" +
                        total +
                        "</h4>" +
                        "</div>" +
                        "</div>" +
                        "</div>" +
                        "</div>"
                    )
                }

                const validacionSolicitud = () => {
                    const campos = {
                        tipoSolicitud: {
                            notEmpty: {
                                message: "Debe seleccionar un tipo de solicitud"
                            }
                        },
                        fechasNuevaSolicitud: {
                            callback: {
                                callback: (input) => {
                                    const fechas = getInputFechas("#fechasNuevaSolicitud", true)
                                    return fechas.inicio !== null && fechas.fin !== null ? true : false
                                },
                                message: "Debe seleccionar un rango de fechas"
                            }
                        },
                        montoVG: {
                            notEmpty: {
                                message: "Debe ingresar un monto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "Debe ser mayor a 0"
                            }
                        },
                        proyecto: {
                            notEmpty: {
                                message: "Debe ingresar el nombre del proyecto"
                            }
                        }
                    }

                    valSolicitud = setValidacionModal(
                        "#modalNuevaSolicitud",
                        campos,
                        "#registraSolicitud",
                        registraSolicitud,
                        "#cancelaSolicitud",
                        {
                            accionCancel: limpiaComprobantes
                        }
                    )
                }

                const validacionComprobante = () => {
                    const campos = {
                        comprobante: {
                            notEmpty: {
                                message: "Debe seleccionar un comprobante o tomar una foto"
                            },
                            file: {
                                maxSize: 5 * 1024 * 1024, // 5 MB
                                message: "El archivo no debe exceder 5MB"
                            }
                        },
                        fechaComprobante: {
                            notEmpty: {
                                message: "Debe ingresar una fecha"
                            }
                        },
                        montoComprobante: {
                            notEmpty: {
                                message: "Debe ingresar un monto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "El monto debe ser mayor a 0"
                            }
                        },
                        conceptoComprobante: {
                            callback: {
                                callback: (input) => {
                                    const concepto = $("#conceptoComprobante").select2("val")
                                    return concepto === null || concepto === "" ? false : true
                                },
                                message: "Debe seleccionar un concepto"
                            }
                        },
                        observacionesComprobante: {
                            stringLength: {
                                max: 500,
                                message: "Las observaciones no deben exceder los 500 caracteres"
                            }
                        }
                    }

                    valComprobante = setValidacionModal(
                        "#modalAgregarComprobante",
                        campos,
                        "#agregarComprobante",
                        agregarComprobante,
                        "#cancelaComprobante"
                    )
                }

                const getParametros = () => {
                    const fechas = getInputFechas("#fechasNuevaSolicitud", true)

                    const tipo = $("#tipoSolicitud").val()
                    const proyecto = $("#proyecto").val()
                    const fechaI = fechas.inicio
                    const fechaF = fechas.fin
                    const monto = numeral($("#montoVG").val()).value()
                    const limite =
                        tipo === "1"
                            ? moment(fechaF, MOMENT_BACK).add(3, "days").format(MOMENT_BACK)
                            : moment().format(MOMENT_BACK)

                    return {
                        usuario: $_SESSION[usuario_id],
                        tipo,
                        proyecto,
                        fechaI,
                        fechaF,
                        monto,
                        limite
                    }
                }

                const registraSolicitud = () => {
                    const mensaje = $("<div class='text-center'></div>")
                        .append("<p>El registro de esta solicitud no garantiza su aprobación ni obliga a la empresa a realizar pagos o reembolsos.</p>")
                        .append("<p>¿Desea continuar?</p>")

                    confirmarMovimiento(mensaje, "Importante").then((continuar) => {
                        if (continuar.isConfirmed) {
                            const datos = getParametros()
                            if (!datos) return
                            $("#registraSolicitud").attr("disabled", true)

                            const formData = new FormData()
                            formData.append("usuario", datos.usuario)
                            formData.append("tipo", datos.tipo)
                            formData.append("proyecto", datos.proyecto)
                            formData.append("fechaI", datos.fechaI)
                            formData.append("fechaF", datos.fechaF)
                            formData.append("monto", datos.monto)
                            formData.append("limite", datos.limite)
                            formData.append("comprobado", datos.tipo == 2 ? datos.monto: 0)

                            comprobantesGastos.forEach((comprobante) => {
                                formData.append("comprobante[]", comprobante.comprobante)
                                formData.append("conceptoComprobante[]", comprobante.concepto)
                                formData.append("fechaComprobante[]", comprobante.fecha)
                                formData.append("subtotalComprobante[]", comprobante.subtotal)
                                formData.append("totalComprobante[]", comprobante.total)
                                formData.append("observacionesComprobante[]", comprobante.observaciones)
                            })

                            consultaServidor(
                                "/viaticos/registraSolicitud_VG",
                                formData,
                                (respuesta) => {
                                    $("#registraSolicitud").attr("disabled", false)
                                    if (!respuesta.success) return showError(respuesta.mensaje)

                                    showSuccess("Solicitud registrada correctamente").then(() => {
                                        $("#modalNuevaSolicitud").modal("hide")
                                        resetValidacion(valSolicitud, true)
                                        resetValidacion(valComprobante, true)
                                        limpiaComprobantes()
                                        getSolicitudes()
                                    })
                                },
                                {
                                    procesar: false,
                                    tipoContenido: false
                                }
                            )
                        } else {
                            $("#registraSolicitud").attr("disabled", false)
                        }
                    })
                }

                const limpiaComprobantes = () => {
                    $("#tbodyComprobantes").empty()
                    comprobantesGastos.length = 0
                }

                const configuraModalComprobante = () => {
                    let modalOrigen = null

                    $("#modalNuevaSolicitud").on("show.bs.modal", () => {
                        const fechaEntrega = calcularFechaPago()
                        $("#notificacionEntrega").text("El monto autorizado se podrá cobrar a partir del " + fechaEntrega.format("D [de] MMMM."))
                        if (moment().isoWeekday() === 3 && moment().hour() < 12)
                            $("#notificacionHoraEntrega").text("Esta fecha es valida hasta las 12 hrs, según la hora de registro en el sistema no la de su equipo.")
                    })

                    $("#btnAgregarComprobanteGastos").click(() => {
                        const fechas = getInputFechas("#fechasNuevaSolicitud", true, false)
                        updateInputFechas("#fechaComprobante", {
                            iniF: fechas.inicio,
                            minF: fechas.inicio,
                            maxF: fechas.fin
                        })
                        modalOrigen = $("#modalNuevaSolicitud")
                        modalOrigen.modal("hide")
                        $("#modalAgregarComprobante").modal("show")
                    })

                    $("#btnCapturaComprobanteViaticos").click(() => {
                        const inicio = $("#verFechaI").val()
                        const fin = $("#verFechaF").val()
                        updateInputFechas("#fechaComprobante", {
                            iniF: inicio,
                            minF: inicio,
                            maxF: fin
                        })
                        
                        modalOrigen = $("#modalVerSolicitud")
                        modalOrigen.modal("hide")
                        $("#modalAgregarComprobante").modal("show")
                    })

                    $("#modalAgregarComprobante").on("hidden.bs.modal", () => {
                        if ($('.modal.show').length > 0) return
                        modalOrigen.modal("show")
                    })
                }

                const calcularFechaPago = () => {
                    const fecha = moment()
                    const dia = fecha.isoWeekday()
                    const hora = fecha.hour()
                    let diaPago = 8 

                    if (dia >= 1 && dia <= 2) diaPago = 4
                    if (dia == 3 && hora < 12) diaPago = 4
                    
                    return fecha.clone().isoWeekday(diaPago)
                }

                const changeTipoSolicitud = () => {
                    const tipo = $("#tipoSolicitud").val()
                    if (tipo === "1") {
                        $("#lblMontoVG").text("Monto Solicitado")
                        $("#montoVG").val("")
                        $("#montoVG").prop("disabled", false)
                        limpiaComprobantes()
                        $("#comprobantesGastos").hide()
                        updateInputFechas("#fechasNuevaSolicitud", { minD: 0, maxD: 30 })
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#montoVG").val("0.00")
                        $("#montoVG").prop("disabled", true)
                        $("#conceptoComprobante").val(null).trigger("change")
                        $("#comprobantesGastos").show()
                        updateInputFechas("#fechasNuevaSolicitud", { minD: -30, maxD: 0 })
                    }
                }

                const agregarComprobante = () => {
                    const comprobante = $("#comprobante")[0].files[0]
                    const concepto_id = $("#conceptoComprobante").val()
                    const concepto = $("#conceptoComprobante option:selected").text()
                    const fechaComprobante = getInputFechas("#fechaComprobante")
                    const montoComprobante = numeral($("#montoComprobante").val()).value()
                    const observaciones = $("#observacionesComprobante").val()
                    const parametros = {
                        comprobante,
                        concepto: concepto_id,
                        conceptoNombre: concepto,
                        fecha: fechaComprobante,
                        subtotal: montoComprobante,
                        total: montoComprobante,
                        observaciones
                    }

                    if ($("#verSolicitudId").val() !== "") {
                        parametros.solicitudId = $("#verSolicitudId").val()
                        addComprobanteViaticos(parametros)
                    } else {
                        addComprobanteGastos(parametros)
                    }
                }

                const addComprobanteGastos = (parametros) => {
                    comprobantesGastos.push(parametros)
                    $("#montoVG").val(numeral($("#montoVG").val()).add(parametros.total).format(NUMERAL_DECIMAL))

                    const fila = $("<tr></tr>")
                    fila.append("<td>" + parametros.conceptoNombre + "</td>")
                    fila.append("<td>" + moment(parametros.fecha).format(MOMENT_FRONT) + "</td>")
                    fila.append("<td>" + numeral(parametros.total).format(NUMERAL_MONEDA) + "</td>")
                    fila.append(
                        "<td>" +
                        menuAcciones([
                            {
                                texto: "Ver Comprobante",
                                icono: "fa-eye",
                                funcion: "verComprobanteGastos(" + (comprobantesGastos.length - 1) + ")"
                            },
                            {
                                texto: "Eliminar",
                                icono: "fa-trash",
                                funcion: "eliminaComprobanteGastos(this)",
                                clase: "text-danger"
                            }
                        ]) +
                        "</td>"
                    )

                    $("#tablaComprobantes tbody").append(fila)
                    $("#modalAgregarComprobante").modal("hide")
                    resetValidacion(valComprobante, true)
                }

                const eliminaComprobanteGastos = (btn) => {
                    const fila = $(btn).closest("tr")
                    const indice = fila.index()
                    const monto = numeral(fila.find("td").eq(2).text().trim()).value()
                    fila.remove()

                    comprobantesGastos.splice(indice,1)
                    $("#montoVG").val(numeral($("#montoVG").val()).subtract(monto).format(NUMERAL_DECIMAL))
                }

                const addComprobanteViaticos = (parametros) => {
                    confirmarMovimiento("¿Desea agregar este comprobante?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            const formData = new FormData()
                            formData.append("solicitudId", parametros.solicitudId)
                            formData.append("comprobante", parametros.comprobante)
                            formData.append("concepto", parametros.concepto)
                            formData.append("fecha", parametros.fecha)
                            formData.append("subtotal", parametros.total)
                            formData.append("total", parametros.total)
                            formData.append("observaciones", parametros.observaciones)

                            consultaServidor("/viaticos/registraComporbante_V", formData, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                const fila = getFilaComprobante(
                                    {
                                        TIPO_ID: 1,
                                        ESTATUS_ID: 3
                                    },
                                    {
                                        ID: respuesta.datos.comprobanteId,
                                        ESTATUS_ID: null,
                                        FECHA_REGISTRO: moment().format(MOMENT_BACK),
                                        CONCEPTO_NOMBRE: parametros.conceptoNombre,
                                        TOTAL: parametros.total,
                                    }
                                )

                                $("#tbodyVerComprobantesSolicitud").append(fila)
                                $("#verMontoComprobado").val(
                                    numeral($("#verMontoComprobado").val()).add(parametros.total).format(NUMERAL_MONEDA)
                                )
                                getSolicitudes(true)
                                $("#modalAgregarComprobante").modal("hide")
                                resetValidacion(valComprobante, true)
                            },
                            {
                                procesar: false,
                                tipoContenido: false
                            })
                        } else {
                            $("#modalAgregarComprobante").modal("hide")
                        }
                    })
                }
                
                const editarComprobanteViaticos = (comprobanteId) => {
                    Swal.fire({
                        title: "Editar Comprobante",
                        html: "<div class='text-center'>Seleccione el nuevo archivo para esta comprobación.</div>" +
                            "<input type='file' id='nuevoComprobante' class='form-control' accept='image/*,application/pdf'>" +
                            "<p>O</p>" +
                            "<button type='button' id='btnNuevoComprobante' class='btn btn-outline-primary'><i class='fa fa-camera'>&nbsp;</i>Tomar foto</button>",
                        showCancelButton: true,
                        confirmButtonText: "Guardar",
                        cancelButtonText: "Cancelar",
                        reverseButtons: true,
                        preConfirm: () => {
                            const nuevoComprobante = $("#nuevoComprobante")[0].files[0]
                            if (!nuevoComprobante) {
                                Swal.showValidationMessage("Debe seleccionar un nuevo archivo.")
                                return false
                            }
                            return nuevoComprobante
                        }
                    }).then((resultado) => {
                        if (resultado.isConfirmed) {
                            const nuevoComprobante = resultado.value
                            const parametros = new FormData()
                            parametros.append("comprobanteId", comprobanteId)
                            parametros.append("nuevoComprobante", nuevoComprobante)

                            consultaServidor("/viaticos/editarComprobante_V", parametros, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                showSuccess("Comprobante editado correctamente").then(() => {
                                    $("#modalVerSolicitud").modal("hide")
                                    getSolicitudes(true)
                                })
                            }, {
                                procesar: false,
                                tipoContenido: false
                            })
                        }
                    })
                }

                const eliminaComprobanteViaticos = (comprobanteId) => {
                    confirmarMovimiento("¿Desea eliminar el comprobante?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            const parametros = {
                                comprobanteId,
                                solicitudId: $("#verSolicitudId").val()
                            }

                            consultaServidor("/viaticos/eliminaComprobante_V", parametros, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                const fila = $("#tbodyVerComprobantesSolicitud").find("tr").filter((_, tr) => {
                                    return $(tr).find("td").eq(0).text().trim() === comprobanteId
                                })

                                const monto = numeral(fila.find("td").eq(3).text().trim()).value()
                                $("#verMontoComprobado").val(
                                    numeral($("#verMontoComprobado").val()).subtract(monto).format(NUMERAL_MONEDA)
                                )
                                fila.remove()
                                getSolicitudes(true)
                                showSuccess("Comprobante eliminado correctamente")
                            })
                        }
                    })
                }

                const verSolicitud = (solicitudId) => {
                    consultaServidor("/viaticos/getResumenSolicitud_VG", { solicitudId }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const informacion = respuesta.datos.informacion
                        $("#verSolicitudId").val(informacion.ID)
                        $("#verTipoSol").val(informacion.TIPO_NOMBRE)
                        $("#verFechaReg").val(moment(informacion.REGISTRO).format(MOMENT_FRONT_HORA))
                        $("#verMontoSol").val(numeral(informacion.MONTO).format(NUMERAL_MONEDA))
                        $("#verProyecto").val(informacion.PROYECTO)
                        $("#verFechaI").val(moment(informacion.FECHA_I).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(informacion.FECHA_F).format(MOMENT_FRONT))
                        $("#verEstatus").val(informacion.ESTATUS_NOMBRE)

                        const cancelada_rechazada = [7, 8].includes(numeral(informacion.ESTATUS_ID).value())

                        if (cancelada_rechazada) {
                            if (!informacion.AUTORIZACION_USUARIO) $("#acordionVer #verAutorizacion").addClass("d-none")
                            else $("#acordionVer #verAutorizacion").removeClass("d-none")
                            $("#acordionVer #verEntregado").addClass("d-none")
                            $("#acordionVer #verComprobantes").addClass("d-none")
                        } else {
                            $("#acordionVer #verAutorizacion").removeClass("d-none")
                            $("#acordionVer #verEntregado").removeClass("d-none")
                            $("#acordionVer #verComprobantes").removeClass("d-none")
                        }

                        $("#verAutorizacionIcono").removeClass()
                        if (informacion.AUTORIZACION_USUARIO) {
                            $("#verAutorizacionIcono").addClass(cancelada_rechazada ? "fa fa-circle-xmark text-danger" : "fa fa-circle-check text-success")
                            $("#verAutorizadoPor").val(informacion.AUTORIZACION_NOMBRE)
                            $("#verFechaAutorizacion").val(moment(informacion.AUTORIZACION_FECHA).format(MOMENT_FRONT_HORA))
                            $("#verMontoAutorizado").val(numeral(informacion.AUTORIZACION_MONTO).format(NUMERAL_MONEDA))
                        } else {
                            $("#verAutorizacionIcono").addClass("fa fa-hourglass-start text-warning")
                            $("#verAutorizadoPor").val("Pendiente de autorización")
                            $("#verFechaAutorizacion").val("")
                            $("#verMontoAutorizado").val("")
                        }
                        
                        $("#verEntregadoIcono").removeClass()
                        if (informacion.ENTREGA_USUARIO) {
                            $("#verEntregadoIcono").addClass("fa fa-sack-dollar text-success")
                            $("#verEntregadoPor").val(informacion.ENTREGA_NOMBRE)
                            $("#verFechaEntrega").val(moment(informacion.ENTREGA_FECHA).format(MOMENT_FRONT_HORA))
                            $("#verMontoEntregado").val(numeral(informacion.ENTREGA_MONTO).format(NUMERAL_MONEDA))
                            $("#verMetodoEntrega").val(informacion.METODO_ENTREGA)
                        } else {
                            $("#verEntregadoIcono").addClass("fa fa-hourglass-start text-warning")
                            $("#verEntregadoPor").val("Pendiente de entrega")
                            $("#verFechaEntrega").val("")
                            $("#verMontoEntregado").val("")
                            $("#verMetodoEntrega").val("")
                        }

                        $("#verFechaLimite").val(moment(informacion.COMPROBACION_LIMITE).format(MOMENT_FRONT))
                        let tiempoRestante = iniciarContador("#verTiempoRestante", (informacion.TIPO_ID == 1 && informacion.ESTATUS_ID == 3) ? informacion.COMPROBACION_LIMITE : moment().subtract(1, "days").format(MOMENT_BACK))
                        $("#verMontoComprobado").val(
                            numeral(informacion.COMPROBACION_MONTO).format(NUMERAL_MONEDA)
                        )

                        const comprobantes = respuesta.datos.comprobantes || []
                        $("#tbodyVerComprobantesSolicitud").empty()
                        comprobantes.forEach((comprobante) => {
                            const fila = getFilaComprobante(informacion, comprobante)
                            $("#tbodyVerComprobantesSolicitud").append(fila)
                        })

                        $("#modalVerSolicitud").modal("show")
                        $("#modalVerSolicitud").on("hidden.bs.modal", () => {
                            if (tiempoRestante === null) return
                            clearInterval(tiempoRestante)
                        })
                    })
                }

                const getFilaComprobante = (informacion, comprobante) => {
                    const editar = (informacion.TIPO_ID == 1 && informacion.ESTATUS_ID == 3) || (informacion.TIPO_ID == 1 && informacion.ESTATUS_ID == 4 && comprobante.ESTATUS_ID != 1)
                    const eliminar = editar
                    const colores = [
                        "danger", "success"
                    ]
                    return "<tr>" +
                        "<td class='d-none'>" + comprobante.ID + "</td>" +
                        "<td><span class='badge badge-dot bg-" + (colores[comprobante.ESTATUS_ID] || "warning") + "'></span></td>" +
                        "<td>" + moment(comprobante.FECHA_REGISTRO).format(MOMENT_FRONT) + "</td>" +
                        "<td>" + comprobante.CONCEPTO_NOMBRE + "</td>" +
                        "<td>" + numeral(comprobante.TOTAL).format(NUMERAL_MONEDA) + "</td>" +
                        "<td>" + menuAcciones([
                                    {
                                        texto: "Comprobante",
                                        icono: "fa-eye",
                                        funcion: "verComprobanteViaticos('" + comprobante.ID + "')"
                                    },
                                    (editar || eliminar) ? "divisor" : null,
                                    editar ? {
                                        texto: "Editar",
                                        icono: "fa-pen-to-square",
                                        funcion: "editarComprobanteViaticos('" + comprobante.ID + "')"
                                    } : null,
                                    eliminar ? {
                                        texto: "Eliminar",
                                        icono: "fa-trash",
                                        funcion: "eliminaComprobanteViaticos('" + comprobante.ID + "')",
                                        clase: "text-danger"
                                    } : null
                                ]) + "</td>" +
                        "</tr>"
                }

                const iniciarContador = (selector, fechaObjetivo) => {
                    const destino = moment(fechaObjetivo).hour(22)

                    const actualizarConteo = () => {
                        const duracion = moment.duration(destino.diff(moment()))

                        if (duracion.asMilliseconds() <= 0) {
                            $(selector).val("0 días 00:00:00")
                            $(selector).removeClass("text-success").addClass("text-danger")
                            $("#tfootVerComprobantesSolicitud").addClass("d-none")
                            clearInterval(intervalo)
                            return
                        }

                        const dias = Math.floor(duracion.asDays())
                        const horas = numeral(duracion.hours()).format("00")
                        const minutos = numeral(duracion.minutes()).format("00")
                        const segundos = numeral(duracion.seconds()).format("00")

                        $(selector).removeClass("text-danger").addClass("text-success")
                        $(selector).val(dias + " días " + horas + ":" + minutos + ":" + segundos)
                        $("#tfootVerComprobantesSolicitud").removeClass("d-none")
                    }

                    const intervalo = setInterval(actualizarConteo, 1000)
                    return intervalo
                }

                const finalizarComprobacion = () => {
                    const mensaje = $("<div class='text-center'></div>")
                        .append("<p class='fw-bold'>Al finalizar la comprobación, no podrá agregar más comprobantes a esta solicitud.</p>")
                        .append("¿Desea continuar?")

                    confirmarMovimiento(mensaje).then((continuar) => {
                        if (continuar.isConfirmed) {
                            const solicitudId = $("#verSolicitudId").val()
                            consultaServidor("/viaticos/finalizarComprobacion", { solicitudId }, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                showSuccess("Comprobación finalizada correctamente").then(() => {
                                    $("#modalVerSolicitud").modal("hide")
                                    getSolicitudes(true)
                                })
                            })
                        }
                    })
                }

                const cancelarSolicitud = (idSolicitud) => {
                    confirmarMovimiento("¿Desea cancelar la solicitud?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            consultaServidor("/viaticos/cancelarSolicitud", { idSolicitud }, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                showSuccess("Solicitud cancelada correctamente").then(() => {
                                    getSolicitudes()
                                })
                            })
                        }
                    })
                }

                const verComprobanteGastos = (index) => {
                    const comprobante = comprobantesGastos[index].comprobante
                    if (comprobante.type.startsWith("image/") || comprobante.type === "application/pdf") {
                        const url = URL.createObjectURL(comprobante)
                        $("#modalNuevaSolicitud").modal("hide")
                        mostrarArchivo(url, {
                            titulo: "Comprobante de Gastos",
                            fncClose: () => {
                                $("#modalNuevaSolicitud").modal("show")
                                URL.revokeObjectURL(url)
                            }
                        })
                    } else {
                        showError("El tipo de archivo no es compatible para vista previa.")
                        return
                    }
                }

                const verComprobanteViaticos = (comprobanteId) => {
                    showWait("Cargando comprobante...")
                    const parametro = new FormData()
                    parametro.append("comprobanteId", comprobanteId)
                    mostrarArchivoDescargado("/viaticos/getComprobante_VG", parametro)
                }

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango: true, iniD: -30 })
                    setInputFechas("#fechasNuevaSolicitud", { rango: true, minD: 0, maxD: 30, enModal: true })
                    setInputFechas("#fechaComprobante", { enModal: true })
                    setInputMoneda("#montoVG, #montoComprobante")

                    configuraTabla(tabla)
                    configuraModalComprobante()
                    validacionSolicitud()
                    validacionComprobante()

                    $("#btnBuscarSolicitudes").on("click", getSolicitudes)
                    $("#tipoSolicitud").on("change", changeTipoSolicitud)
                    $("#conceptoComprobante").select2({
                        dropdownParent: $("#modalAgregarComprobante"),
                        placeholder: "Seleccione un concepto"
                    })
                    $("#conceptoComprobante").on("change", () => {
                        const concepto = $("#conceptoComprobante").find("option:selected")
                        $("#descripcionComprobante").text(concepto.attr("lbl-desc") || "")
                    })

                    $("#btnTomarFoto").on("click", () => {
                        $("#modalAgregarComprobante").modal("hide")
                        tomarFoto("Captura de Comprobante", (foto) => {
                            $("#comprobante")[0].files = foto
                            $("#comprobante").trigger("change")
                            $("#modalAgregarComprobante").modal("show")
                        })
                    })
                    
                    $(document).on("click", "#btnNuevoComprobante", () => {
                        tomarFoto("Captura de Comprobante", (foto) => {
                            $("#nuevoComprobante")[0].files = foto
                            $("#nuevoComprobante").trigger("change")
                        })
                    })
                    
                    $(".btnCerrarVer").on("click", () => {
                        $("#verSolicitudId").val("")
                        $('#acordionVer .accordion-collapse').removeClass('show')
                        $('#acordionVer .accordion-collapse').first().addClass('show')
                        $('#acordionVer .accordion-button').addClass('collapsed')
                        $('#acordionVer .accordion-button').first().removeClass('collapsed')
                    })
                    $("#btnFinalizarComprobacion").on("click", finalizarComprobacion)
                    getSolicitudes()
                })
            </script>
        HTML;

        $catConceptos = ViaticosDAO::getCatalogoConceptosViaticos();
        $conceptos = '<option></option>';
        if ($catConceptos['success']) {
            foreach ($catConceptos['datos'] as $concepto) {
                $conceptos .= "<option value='{$concepto['ID']}' lbl-desc='{$concepto['DESCRIPCION']}'>{$concepto['NOMBRE']}</option>";
            }
        }

        self::set("titulo", "Solicitud de Viáticos y Gastos");
        self::set("script", $script);
        self::set("conceptos", $conceptos);
        self::render("viaticos_solicitud");
    }

    public function getSolicitudesUsuario()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesUsuario_VG($_POST));
    }

    public function getResumenSolicitud_VG()
    {
        $informacion = ViaticosDAO::getResumenSolicitud_VG($_POST);
        $comprobantes = ViaticosDAO::getComprobantesSolicitud_VG($_POST);
        $success = $informacion['success'] && $comprobantes['success'];
        $mensaje = 'Resumen de la solicitud obtenido correctamente.';
        if (!$success) {
            $mensaje = $informacion['mensaje'] ?? $comprobantes['mensaje'] ?? 'Error al obtener la información de la solicitud.';
            $errores = [
                'informacion' => $informacion['error'] ?? 'No se pudo obtener la información de la solicitud.',
                'comprobantes' => $comprobantes['error'] ?? 'No se pudieron obtener los comprobantes de la solicitud.'
            ];
        }

        $resultado = self::respuesta($success, $mensaje, [
            'informacion' => $informacion['datos'],
            'comprobantes' => $comprobantes['datos']
        ], $errores);

        self::respuestaJSON($resultado);
    }

    public function cancelarSolicitud()
    {
        self::respuestaJSON(ViaticosDAO::cancelarSolicitud_VG($_POST));
    }

    public function registraSolicitud_VG()
    {
        $errores = [];
        $comprobantes = [];

        foreach ($_FILES['comprobante']['tmp_name'] as $key => $archivo) {
            if ($_FILES['comprobante']['error'][$key] !== UPLOAD_ERR_OK) {
                $errores[] = "El archivo {$_FILES['archivos']['name'][$key]} tiene un error y no se puede guardar.";
                continue;
            }

            if ($_FILES['comprobante']['size'][$key] > 5 * 1024 * 1024) {
                $errores[] = "El archivo {$_FILES['archivos']['name'][$key]} excede el tamaño máximo permitido de 5 MB.";
                continue;
            }

            try {
                $comprobantes[] = [
                    'comprobante' => fopen($archivo, 'rb'),
                    'nombre' => $_FILES['comprobante']['name'][$key],
                    'tipo' => $_FILES['comprobante']['type'][$key],
                    'tamano' => $_FILES['comprobante']['size'][$key],
                    'concepto' => $_POST['conceptoComprobante'][$key],
                    'fecha' => $_POST['fechaComprobante'][$key],
                    'subtotal' => $_POST['subtotalComprobante'][$key],
                    'total' => $_POST['totalComprobante'][$key],
                    'observaciones' => $_POST['observacionesComprobante'][$key]
                ];
            } catch (\Exception $e) {
                $errores[] = "Error al procesar el archivo {$_FILES['archivos']['name'][$key]}: " . $e->getMessage();
            }
        }

        if (count($errores) > 0) {
            return self::respuestaJSON([
                'success' => false,
                'mensaje' => 'Se encontraron errores al procesar los archivos de los comprobantes.',
                'errores' => $errores
            ]);
        }

        $resultado = ViaticosDAO::registraSolicitud_VG($_POST, $comprobantes);

        foreach ($comprobantes as $comprobante) {
            if (is_resource($comprobante['comprobante'])) {
                fclose($comprobante['comprobante']);
            }
        }

        self::respuestaJSON($resultado);
    }

    public function registraComporbante_V()
    {
        if (!isset($_POST['solicitudId']) || !isset($_FILES['comprobante'])) {
            return self::respuestaJSON([
                'success' => false,
                'mensaje' => 'El ID de la solicitud y el archivo del comprobante son requeridos.'
            ]);
        }

        if ($_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            return self::respuestaJSON([
                'success' => false,
                'mensaje' => "El archivo {$_FILES['comprobante']['name']} tiene un error y no se puede guardar."
            ]);
        }

        if ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
            return self::respuestaJSON([
                'success' => false,
                'mensaje' => "El archivo {$_FILES['comprobante']['name']} excede el tamaño máximo permitido de 5 MB."
            ]);
        }

        $comprobante = [
            'solicitudId' => $_POST['solicitudId'],
            'comprobante' => fopen($_FILES['comprobante']['tmp_name'], 'rb'),
            'nombre' => $_FILES['comprobante']['name'],
            'tipo' => $_FILES['comprobante']['type'],
            'tamano' => $_FILES['comprobante']['size'],
            'concepto' => $_POST['concepto'],
            'fecha' => $_POST['fecha'],
            'subtotal' => $_POST['subtotal'],
            'total' => $_POST['total'],
            'observaciones' => $_POST['observaciones']
        ];

        $res = ViaticosDAO::registraComporbante_V($comprobante);
        if (is_resource($comprobante['comprobante'])) {
            fclose($comprobante['comprobante']);
        }

        self::respuestaJSON($res);
    }

    public function editarComprobante_V()
    {
        $comprobante = [];

        try {
            if ($_FILES['nuevoComprobante']['error'] !== UPLOAD_ERR_OK) {
                return self::respuestaJSON([
                    'success' => false,
                    'mensaje' => "El archivo {$_FILES['nuevoComprobante']['name']} tiene un error y no se puede guardar."
                ]);
            }

            if ($_FILES['nuevoComprobante']['size'] > 5 * 1024 * 1024) {
                return self::respuestaJSON([
                    'success' => false,
                    'mensaje' => "El archivo {$_FILES['nuevoComprobante']['name']} excede el tamaño máximo permitido de 5 MB."
                ]);
            }

            $comprobante = [
                'comprobanteId' => $_POST['comprobanteId'],
                'nuevoComprobante' => fopen($_FILES['nuevoComprobante']['tmp_name'], 'rb'),
                'nombre' => $_FILES['nuevoComprobante']['name'],
                'tipo' => $_FILES['nuevoComprobante']['type'],
                'tamano' => $_FILES['nuevoComprobante']['size']
            ];

            $res = ViaticosDAO::editarComprobante_V($comprobante);
            self::respuestaJSON($res);
        } catch (\Exception $e) {
            return self::respuestaJSON(self::respuesta(false, 'Error al editar el comprobante', null, $e->getMessage()));
        } finally {
            if (isset($comprobante['nuevoComprobante']) && is_resource($comprobante['nuevoComprobante'])) fclose($comprobante['nuevoComprobante']);
        }
    }

    public function eliminaComprobante_V()
    {
        self::respuestaJSON(ViaticosDAO::eliminaComprobante_V($_POST));
    }

    public function getComprobante_VG()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;

        $comprobante = ViaticosDAO::getComprobante_VG($datos);
        if (!$comprobante['success']) return self::respuestaJSON($comprobante);

        $archivo = $comprobante['datos']['ARCHIVO'];
        $archivo = is_resource($archivo) ? stream_get_contents($archivo) : $archivo;
        if ($archivo === false) {
            return self::respuestaJSON(self::respuesta(false, 'Error al leer el archivo del comprobante.'));
        }

        header('Content-Transfer-Encoding: binary');
        header("Content-Type: {$comprobante['datos']['TIPO']}");
        header("Content-Disposition: inline; filename={$comprobante['datos']['NOMBRE']}");
        header("Content-Length: {$comprobante['datos']['TAMANO']}");
        echo $archivo;
        if (is_resource($archivo)) {
            fclose($archivo);
        }
    }

    public function finalizarComprobacion()
    {
        self::respuestaJSON(ViaticosDAO::finalizaComprobacion_V($_POST));
    }

    public function Autorizacion()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"
                let validacionAutorizacion = null
                let validacionReachazo = null

                const getSolicitudes = () => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesAutorizacion", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const datos = respuesta.datos.map(solicitud => {
                            let color = "warning"
                            if (solicitud.ESTATUS_ID == 2) color = "success"
                            if (solicitud.ESTATUS_ID == 7) color = "danger"

                            const estatus = "<span class='badge rounded-pill bg-label-" + color + "'>" + solicitud.ESTATUS_NOMBRE + "</span>"
                            const acciones = menuAcciones([
                                {
                                    texto: "Detalles",
                                    icono: "fa-eye",
                                    funcion: "verSolicitud(" + solicitud.ID + ")"
                                }
                            ])
                            
                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.USUARIO_NOMBRE,
                                moment(solicitud.FECHA_REGISTRO).format(MOMENT_FRONT),
                                numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
                                estatus,
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const verSolicitud = (solicitudId) => {
                    consultaServidor("/viaticos/getResumenSolicitud_VG", { solicitudId }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const informacion = respuesta.datos.informacion

                        $("#rechazar").attr("disabled", false)
                        $("#autorizar").attr("disabled", false)

                        if (informacion.ESTATUS_ID == 2) {
                            color = "success"
                            $("#autorizar").attr("disabled", true)
                        }
                        
                        if (informacion.ESTATUS_ID == 7) {
                            color = "danger"
                            $("#rechazar").attr("disabled", true)
                        }
                        
                        $("#verSolicitante").val(informacion.USUARIO_NOMBRE)
                        $("#verSucursal").val(informacion.SUCURSAL_NOMBRE)
                        $("#verTipoSol").val(informacion.TIPO_NOMBRE)
                        $("#verSolicitudId").val(informacion.ID)
                        $("#verFechaSol").val(moment(informacion.REGISTRO).format(MOMENT_FRONT_HORA))
                        $("#verMontoSolicitado").val(numeral(informacion.MONTO).format(NUMERAL_MONEDA))
                        $("#verProyecto").val(informacion.PROYECTO)
                        $("#verFechaI").val(moment(informacion.DESDE).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(informacion.HASTA).format(MOMENT_FRONT))
                        $("#montoAutorizado").val(numeral(informacion.AUTORIZACION_MONTO).format(NUMERAL_DECIMAL))
                        $("#modalVerAutorizacion").modal("show")
                    })
                }

                const setValidacionAutorizacion = () => {
                    const campos = {
                        montoAutorizado: {
                            notEmpty: {
                                message: "Debe ingresar un monto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "Debe ser mayor a 0"
                            }
                        },
                        observacionesAutorizacion: {
                            callback: {
                                message: "Debe indicar porque esta autorizando un monto diferente al solicitado.",
                                callback: () => {
                                    if (numeral($("#verMontoSolicitado").val()).difference(numeral($("#montoAutorizado").val()).value()) === 0) return true
                                    return $("#observacionesAutorizacion").val().trim() !== ""
                                }
                            }
                        }
                    }
    
                    validacionAutorizacion = setValidacionModal(
                        "#modalVerAutorizacion",
                        campos,
                        "#autorizar",
                        autorizarSolicitud,
                        "#cancelar"
                    )
                }

                const setValidacionReachazo = () => {
                    const campos = {
                        observacionesAutorizacion: {
                            notEmpty: {
                                message: "Debe ingresar sus observaciones para el rechazo"
                            }
                        }
                    }
    
                    validacionAutorizacion = setValidacionModal(
                        "#modalVerAutorizacion",
                        campos,
                        "#rechazar",
                        rechazarSolicitud,
                        "#cancelar"
                    )
                }

                const autorizarSolicitud = () => {
                    const parametros = {
                        solicitud: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: 2,
                        monto: $("#montoAutorizado").val(),
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    actualizaSolicitud("¿Desea autorizar esta solicitud?", parametros, "La solicitud ha sido autorizada.")
                }

                const rechazarSolicitud = () => {
                    const parametros = {
                        solicitud: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: 7,
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    actualizaSolicitud("¿Desea rechazar esta solicitud?", parametros, "La solicitud ha sido rechazada.")
                }

                const actualizaSolicitud = (confirmacion, parametros, exito) => {
                    confirmarMovimiento(confirmacion).then((continuar) => {
                        if (!continuar.isConfirmed) return
                        consultaServidor("/viaticos/autorizaSolicitud_VG", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess(exito).then(() => {
                                $("#modalVerAutorizacion").modal("hide")
                                getSolicitudes()
                            })
                        })
                    })
                }

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango: true, iniD: -30 })
                    $("#btnBuscarSolicitudes").on("click", getSolicitudes)
                    configuraTabla("#historialSolicitudes")

                    setValidacionAutorizacion()
                    setValidacionReachazo()

                    getSolicitudes()
                });
            </script>
        HTML;

        self::set("titulo", "Autorización de Viáticos y Gastos");
        self::set("script", $script);
        self::render("viaticos_autorizacion");
    }

    public function getSolicitudesAutorizacion()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesAutorizacion($_POST));
    }

    public function autorizaSolicitud_VG()
    {
        self::respuestaJSON(ViaticosDAO::autorizaSolicitud_VG($_POST));
    }

    public function Entrega()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"
                let validacionEntrega = null

                const getSolicitudes = () => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        sucursal: $_SESSION[sucursal_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesEntrega", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const datos = respuesta.datos.map(solicitud => {
                            const acciones = menuAcciones([
                                {
                                    texto: "Detalles",
                                    icono: "fa-eye",
                                    funcion: "verSolicitud(" + solicitud.ID + ")"
                                }
                            ])
                            
                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.USUARIO_NOMBRE,
                                moment(solicitud.AUTORIZACION_FECHA).format(MOMENT_FRONT),
                                numeral(solicitud.AUTORIZACION_MONTO).format(NUMERAL_MONEDA),
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const setValidacionEntrega = () => {
                    const campos = {
                        montoEntrega: {
                            notEmpty: {
                                message: "Debe ingresar un monto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "Debe ser mayor a 0"
                            }
                        },
                        observacionesEntrega: {
                            callback: {
                                message: "Debe indicar porque esta entregando un monto diferente al autorizado.",
                                callback: () => {
                                    if (numeral($("#verMontoAutorizado").val()).difference(numeral($("#montoEntrega").val()).value()) === 0) return true
                                    return $("#observacionesEntrega").val().trim() !== ""
                                }
                            }
                        }
                    }
    
                    validacionEntrega = setValidacionModal(
                        "#modalVerEntrega",
                        campos,
                        "#entregar",
                        entrega_VG,
                        "#cancelar"
                    )
                }

                const verSolicitud = (solicitudId) => {
                    consultaServidor("/viaticos/getResumenSolicitud_VG", { solicitudId }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const informacion = respuesta.datos.informacion
                        $("#verSolicitante").val(informacion.USUARIO_NOMBRE)
                        $("#verSucursal").val(informacion.SUCURSAL_NOMBRE)
                        $("#verFechaReg").val(moment(informacion.REGISTRO).format(MOMENT_FRONT_HORA))
                        $("#verSolicitudId").val(informacion.ID)
                        $("#verTipoSolId").val(informacion.TIPO_ID)
                        $("#verTipoSol").val(informacion.TIPO_NOMBRE)
                        $("#verFechaI").val(moment(informacion.FECHA_I).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(informacion.FECHA_F).format(MOMENT_FRONT))
                        $("#verProyecto").val(informacion.PROYECTO)
                        $("#verAutorizado").val(informacion.AUTORIZACION_NOMBRE)
                        $("#verFechaAutorizado").val(moment(informacion.AUTORIZACION_FECHA).format(MOMENT_FRONT_HORA))
                        $("#verMontoAutorizado").val(numeral(informacion.AUTORIZACION_MONTO).format(NUMERAL_MONEDA))
                        $("#montoEntrega").val(numeral(informacion.AUTORIZACION_MONTO).format(NUMERAL_DECIMAL))
                        $("#modalVerEntrega").modal("show")
                    })
                }

                const entrega_VG = () => {
                    confirmarMovimiento("¿Desea registrar la entrega?").then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            solicitud: $("#verSolicitudId").val(),
                            usuario: $_SESSION[usuario_id],
                            metodo: $("#metodoEntrega").val(),
                            monto: $("#montoEntrega").val(),
                            observaciones: $("#observacionesEntrega").val(),
                            estatus: $("#verTipoSolId").val() == 2 ? 6 : 3
                        }

                        consultaServidor("/viaticos/entrega_VG", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            const mensaje = $("<div>")
                                .append("<p>La entrega se ha registrado correctamente.</p>")
                                .append("<p>Debe imprimir el comprobante y ser firmado por quien recibe y por quien entrega.</p>")

                            showSuccess(mensaje).then(() => {
                                const parametro = new FormData()
                                parametro.append("solicitud", parametros.solicitud)
                                $("#modalVerEntrega").modal("hide")
                                mostrarArchivoDescargado(
                                    "/viaticos/getComprobanteEntrega",
                                    parametro,
                                    {
                                        titulo: "Comprobante de entrega",
                                        fncClose: getSolicitudes
                                    }
                                )
                            })
                        })
                    })
                }

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango:true, iniD: -30 })
                    setInputMoneda("#montoEntrega")
                    $("#btnBuscarSolicitudes").on("click", getSolicitudes)
                    setValidacionEntrega()
                    configuraTabla(tabla)
                    getSolicitudes()
                });
            </script>
        HTML;

        $metodosDisponibles = ViaticosDAO::getCatalogoMetodosEntrega();
        $metodosEntrega = '';
        if ($metodosDisponibles['success']) {
            foreach ($metodosDisponibles['datos'] as $metodo) {
                $metodosEntrega .= "<option value='{$metodo['ID']}'>{$metodo['NOMBRE']}</option>";
            }
        }

        self::set("titulo", "Entrega y devolución");
        self::set("script", $script);
        self::set("metodosEntrega", $metodosEntrega);
        self::render("viaticos_entrega");
    }

    public function getSolicitudesEntrega()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesEntrega($_POST));
    }

    public function entrega_VG()
    {
        self::respuestaJSON(ViaticosDAO::entrega_VG($_POST));
    }

    public function getComprobanteEntrega()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;
        $datos = ViaticosDAO::getDatosComprobanteEntrega($datos);
        if (!$datos['success']) return self::respuestaJSON($datos);

        $plantilla = self::getPlantillaEntrega_VG($datos['datos']);

        $mpdf = new \mPDF([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'default_font_size' => 10,
            'default_font' => 'Arial',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->WriteHTML($plantilla['estilo'], 1);
        $mpdf->WriteHTML($plantilla['cuerpo'], 2);


        $contenidoInvertido = <<<HTML
            <div style="border-top: 1px dashed #000; margin-top: 10px;">
                {$plantilla['cuerpo']}
            </div>
        HTML;
        $mpdf->WriteHTML($contenidoInvertido);

        $mpdf->Output('comprobante_entrega.pdf', 'I');
        exit;
    }

    public function getPlantillaEntrega_VG($datos)
    {
        $monto = $datos['ENTREGA_MONTO'] ?? 0;

        $entero = floor($monto);
        $centavos = round(($monto - $entero) * 100);
        $fmt = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
        $texto_entero = $fmt->format($entero);
        $texto_centavos = str_pad($centavos, 2, '0', STR_PAD_LEFT);
        $monto_letra = mb_strtoupper("$texto_entero pesos $texto_centavos/100 M.N.", 'UTF-8');

        $fmt = new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY);
        $monto = $fmt->formatCurrency($monto, 'MXN');

        $estilo = <<<HTML
            <style>
                body {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }

                .header {
                    text-align: center;
                    margin-bottom: 5px;
                    border-bottom: 2px solid #333;
                }
                
                .header h2 {
                    font-weight: bold;
                    text-transform: uppercase;
                }
                
                .document-info {
                    width: 100%;
                }
                
                .document-info .left {
                    width: 60%;
                    vertical-align: top;
                }
                
                .document-info .right {
                    width: 40%;
                    vertical-align: top;
                    text-align: right;
                }
                
                .field-row {
                    margin-bottom: 5px;
                }
                
                .field-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 100px;
                }
                
                .field-value {
                    display: inline-block;
                    border-bottom: 1px solid #333;
                    min-width: 200px;
                    padding-bottom: 1px;
                }
                
                .content-section {
                    text-align: justify;
                    line-height: 1.6;
                }
                
                .amount-section {
                    text-align: center;
                    margin: 0;
                }
                
                .amount-box {
                    display: inline-block;
                    border: 2px solid #333;
                    padding: 15px;
                    background-color: #f9f9f9;
                }
                
                .amount-label {
                    font-weight: bold;
                }
                
                .amount-value {
                    font-weight: bold;
                    color: #333;
                }
                
                .signatures {
                    width: 100%;
                    text-align: center;
                }

                .signatures-signature {
                    height: 100px;
                }

                .signatures-space {
                    width: 10%;
                }

                .signatures-data {
                    width: 35%;
                    border-top: 1px solid #333;
                }
                
                .signature-title {
                    font-weight: bold;
                }
            </style>
        HTML;

        $cuerpo = <<<HTML
            <!-- Encabezado -->
            <div class="header">
                <h2>Comprobante de Entrega y Recepción de Viáticos</h2>
            </div>

            <!-- Información del documento -->
            <table class="document-info">
                <tr>
                    <td class="left">
                        <div class="field-row">
                            <span class="field-label">Empresa:</span>
                            <span class="field-value">[NOMBRE DE LA EMPRESA]</span>
                        </div>
                    </td>
                    <td class="right">
                        <div class="field-row">
                            <span class="field-label">Folio:</span>
                            <span class="field-value">[000001]</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="left">
                        <div class="field-row">
                            <span class="field-label">Sucursal:</span>
                            <span class="field-value">[SUCURSAL]</span>
                        </div>
                    </td>
                    <td class="right">
                        <div class="field-row">
                            <span class="field-label">Fecha:</span>
                            <span class="field-value">[DD/MM/AAAA]</span>
                        </div>
                    </td>
                </tr>
                
            </table>

            <!-- Contenido principal -->
            <div class="content-section">
                <p>Por medio del presente documento se hace constar la <strong>ENTREGA DE VIÁTICOS</strong> por parte de <strong>[NOMBRE_ENTREGADOR]</strong> en representación de <strong>[NOMBRE_EMPRESA]</strong>, al empleado <strong>[NOMBRE_RECEPTOR]</strong>, quien desempeña el cargo de <strong>[PUESTO_EMPLEADO]</strong>.</p>
                
                <p>El beneficiario se compromete a utilizar los recursos otorgados de manera responsable y conforme a los lineamientos establecidos en las políticas internas de viáticos y gastos de representación de la empresa, así como a presentar la documentación comprobatoria correspondiente en los plazos establecidos.</p>
                
            </div>

            <!-- Monto -->
            <div class="amount-section">
                <div class="amount-box">
                    <div class="amount-label">MONTO ENTREGADO</div>
                    <div class="amount-value">$monto</div>
                    <div class="amount-text">($monto_letra)</div>
                </div>
            </div>

            <!-- Firmas -->
            <table class="signatures">
                <tr>
                    <td></td>
                    <td class="signatures-signature">
                        <span class="signature-title">ENTREGA</span>
                    </td>
                    <td></td>
                    <td class="signatures-signature">
                        <span class="signature-title">RECIBE</span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td class="signatures-space"></td>
                    <td class="signatures-data">
                        <span>Nombre y Firma</span>
                    </td>
                    <td class="signatures-space"></td>
                    <td class="signatures-data">
                        <span>Nombre y Firma</span>
                    </td>
                    <td class="signatures-space"></td>
                </tr>
            </table>
        HTML;

        return [
            'estilo' => $estilo,
            'cuerpo' => $cuerpo
        ];
    }

    public function Validacion()
    {
        $css = <<<HTML
            <link rel="stylesheet" href="/assets/css/viaticos_validacion.css" />
        HTML;

        $script = <<<HTML
            <script>
                const tabla = "#historialComprobaciones"
                const leyendaNoComprobante = {
                    t1: "Comprobante", 
                    actual: 0,
                    t2: "de",
                    total: 0
                }
                let comprobaciones = null
                let comprobacion = null
                let comprobantes = null
                let comprobante = null
                let visorPDF = null
                let pdfActual = null
                let paginaActual = 1
                let paginasTotales = 1
                let zoomActual = 1.0
                let fullScreen = false

                const getComprobaciones = () => {
                    const parametros = getParametros()
                    consultaServidor("/viaticos/getComprobaciones", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        comprobaciones = respuesta.datos
                        const datos = comprobaciones.map((comprobacion, index) => {
                            const acciones = menuAcciones([
                                {
                                    texto: "Comprobantes",
                                    icono: "fa-eye",
                                    funcion: "verComprobantes(" + index + ")"
                                }
                            ])
                            return [
                                null,
                                comprobacion.ID,
                                comprobacion.TIPO_NOMBRE,
                                moment(comprobacion.REGISTRO).format(MOMENT_FRONT),
                                comprobacion.PROYECTO,
                                numeral(comprobacion.ENTREGA_MONTO).format(NUMERAL_MONEDA),
                                comprobacion.REGISTRADOS,
                                comprobacion.RECHAZADOS,
                                comprobacion.ACEPTADOS,
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const getParametros = () => {
                    const fechas = getInputFechas("#fechasComprobaciones", true)

                    return {
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }
                }

                const verComprobantes = (indice) => {
                    comprobacion = comprobaciones[indice]
                    $("#solicitante").val(comprobacion.SOLICITANTE_NOMBRE)
                    $("#fechaSolicitud").val(moment(comprobacion.REGISTRO).format(MOMENT_FRONT_HORA))
                    $("#fechaLimite").val(moment(comprobacion.COMPROBACION_LIMITE).format(MOMENT_FRONT))
                    $("#proyecto").val(comprobacion.PROYECTO)
                    $("#tipo").val(comprobacion.TIPO_NOMBRE)
                    $("#montoSolicitud").val(numeral(comprobacion.ENTREGA_MONTO).format(NUMERAL_MONEDA))
                    $("#montoComprobado").val(numeral(comprobacion.COMPROBACION_MONTO).format(NUMERAL_MONEDA))
                    $("#solicitudDetalles").addClass("show")
                    getComprobantes(comprobacion.ID)
                }

                const getComprobantes = (solicitudId) => {
                    $(".controlesPDF").addClass("d-none")
                    $("#cargandoArchivo").removeClass("d-none")
                    $("#sinArchivo").addClass("d-none")
                    $("#errorArchivo").addClass("d-none")
                    $("#visor").children().last().filter("canvas, img").remove()
                    $("#btnCompAnt").prop("disabled", true)
                    $("#btnCompSig").prop("disabled", true)
                    $("#btnRechazarComprobante").prop("disabled", true)
                    $("#btnAceptarComprobante").prop("disabled", true)
                    
                    consultaServidor("/viaticos/getComprobantesSolicitud_VG", { solicitudId, comprobacion: true }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        comprobantes = respuesta.datos
                        if (comprobantes.length === 0) {
                            comprobante = null
                            leyendaNoComprobante.actual = 0
                            leyendaNoComprobante.total = 0
                            $("#noComprobante").text(Object.values(leyendaNoComprobante).join(" "))
                            $("#cargandoArchivo").addClass("d-none")
                            $("#sinArchivo").removeClass("d-none")
                            actualizaDatosComprobante(-1)
                            return
                        }

                        $("#btnRechazarComprobante").prop("disabled", false)
                        $("#btnAceptarComprobante").prop("disabled", false)
                        leyendaNoComprobante.actual = 0
                        leyendaNoComprobante.total = comprobantes.length
                        comprobanteSiguiente()
                    })
                }
                
                const comprobanteSiguiente = () => {
                    leyendaNoComprobante.actual++
                    if (leyendaNoComprobante.actual > leyendaNoComprobante.total)
                        leyendaNoComprobante.actual = leyendaNoComprobante.total
                    actualizaDatosComprobante(leyendaNoComprobante.actual - 1)
                }

                const comprobanteAnterior = () => {
                    leyendaNoComprobante.actual--
                    if (leyendaNoComprobante.actual < 1) leyendaNoComprobante.actual = 1
                    actualizaDatosComprobante(leyendaNoComprobante.actual - 1)
                }

                const actualizaDatosComprobante = (index = 0) => {
                    comprobante = comprobantes[index]
                    $("#visor").children().last().filter("canvas, img").remove()
                    $("#fechaCaptura").val(comprobante && moment(comprobante.FECHA_REGISTRO).format(MOMENT_FRONT_HORA))
                    $("#concepto").val(comprobante?.CONCEPTO_NOMBRE)
                    $("#fechaComprobante").val(comprobante && moment(comprobante.FECHA_COMPROBANTE).format(MOMENT_FRONT))
                    $("#montoComprobante").val(comprobante && numeral(comprobante.TOTAL).format(NUMERAL_MONEDA))
                    $("#observaciones").val(comprobante?.OBSERVACIONES)
                    $("#btnRechazarComprobante").prop("disabled", comprobante?.ESTATUS == 0)

                    $("#noComprobante").text(Object.values(leyendaNoComprobante).join(" "))
                    $("#btnCompAnt").prop("disabled", leyendaNoComprobante.actual === 1)
                    $("#btnCompSig").prop("disabled", leyendaNoComprobante.actual === leyendaNoComprobante.total)

                    const url = "/viaticos/getComprobante_VG?comprobanteId=" + comprobante.ID
                    const tipo = comprobante.ARCHIVO_TIPO
                    $(".controlesPDF").addClass("d-none")
                    $("#cargandoArchivo").removeClass("d-none")

                    setTimeout(() => {
                        verArchivo(url, tipo)
                    }, 1500)
                }

                const verArchivo = async (url, tipo) => {
                    try {
                        if (tipo === "application/pdf") await verPDF(url)
                        if (tipo.startsWith("image/")) verImagen(url)
                        if (!tipo) throw new Error("Tipo de archivo no soportado")
                    } catch (error) {
                        $("#errorArchivo").removeClass("d-none")
                    } finally {
                        $("#cargandoArchivo").addClass("d-none")
                    }
                }

                const verImagen = (url) => {
                    const img = document.createElement("img")
                    img.className = "file-content no-select mw-100 mh-100"
                    img.src = url
                    img.alt = "Archivo de imagen"
                    img.onload = () => {
                        const visor = $("#visor")
                        visor.append(img)
                    }
                }

                const verPDF = async (url) => {
                    const archivo = visorPDF.getDocument(url)
                    pdfActual = await archivo.promise
                    paginasTotales = pdfActual.numPages
                    await verPagina(1)
                    actualizaInfoVisor()
                    $(".controlesPDF").removeClass("d-none")
                }

                const verPagina = async (noPagina) => {
                    const pagina = await pdfActual.getPage(noPagina)
                    const canvas = document.createElement("canvas")
                    const contextoCanvas = canvas.getContext("2d")

                    const viewport = pagina.getViewport({ scale: zoomActual })
                    canvas.height = viewport.height
                    canvas.width = viewport.width
                    canvas.className = "pdf-canvas no-select no-context-menu"

                    const contexto = {
                        canvasContext: contextoCanvas,
                        viewport: viewport
                    }

                    await pagina.render(contexto).promise

                    const visor = $("#visor")
                    visor.find("canvas").remove()
                    visor.append(canvas)
                }

                const actualizaInfoVisor = () => {
                    $("#paginaActual").text(paginaActual + " / " + paginasTotales)
                    $("#btnPagAnt").prop("disabled", paginaActual === 1)
                    $("#btnPagSig").prop("disabled", paginaActual === paginasTotales)
                }

                const verPaginaAnterior = async () => {
                    if (paginaActual > 1) {
                        paginaActual--
                        await verPagina(paginaActual)
                        actualizaInfoVisor()
                    }
                }

                const verPaginaSiguiente = async () => {
                    if (paginaActual < paginasTotales) {
                        paginaActual++
                        await verPagina(paginaActual)
                        actualizaInfoVisor()
                    }
                }

                const aumentarZoom = async () => {
                    if (zoomActual < 3.0) {
                        zoomActual += 0.25
                        if (pdfActual) {
                            await verPagina(paginaActual)
                        }
                    }
                }

                const disminuirZoom = async () => {
                    if (zoomActual > 0.5) {
                        zoomActual -= 0.25
                        if (pdfActual) {
                            await verPagina(paginaActual)
                        }
                    }
                }

                const cambiarFullscreen = () => {
                    if (fullScreen) {
                        fullScreen = false
                        $("#visorArchivos").removeClass("fullscreen")
                        $("#btnFullscreen i").removeClass("fa-compress").addClass("fa-expand")
                    } else {
                        fullScreen = true
                        $("#visorArchivos").addClass("fullscreen")
                        $("#btnFullscreen i").removeClass("fa-expand").addClass("fa-compress")
                    }
                }

                const rechazarComprobante = () => {
                    confirmarMovimiento("¿Desea rechazar este comprobante?").then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            solicitudId: comprobante.SOLICITUD_ID,
                            comprobanteId: comprobante.ID,
                            estatus: 0
                        }

                        consultaServidor("/viaticos/actualizaEstatusComprobante", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess("Comprobante rechazado correctamente.").then(() => {
                                comprobante.ESTATUS = 0
                                $("#btnRechazarComprobante").prop("disabled", true)
                                getComprobaciones()
                            })
                        })
                    })
                }

                const aceptarComprobante = () => {
                    const parametros = {
                        solicitudId: comprobante.SOLICITUD_ID,
                        comprobanteId: comprobante.ID,
                        estatus: 1,
                    }

                    if (comprobantes.length !== 1) aceptar("¿Desea aceptar este comprobante?", parametros)
                    else {
                        const msg = comprobacion.TIPO_ID == 1 
                            ? "se dará por finalizada la solicitud de viáticos y se aplicaran los ajustes de saldo a favor o en contra."
                            : "se le otorgara al colaborador el monto comprobado."
                        
                        const mensaje = $("<div>")
                            .append("<p>Si acepta el comprobante, " + msg + "</p>")
                            .append("<p>¿Desea continuar?</p>")
                        parametros.finalizar = true
                        aceptar(mensaje, parametros)
                    }  
                }

                const aceptar = (mensaje, parametros) => {
                    confirmarMovimiento(mensaje).then((continuar) => {
                        if (!continuar.isConfirmed) return

                        consultaServidor("/viaticos/actualizaEstatusComprobante", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess("Comprobante aceptado correctamente.").then(() => {
                                getComprobaciones()
                                comprobantes.splice(leyendaNoComprobante.actual - 1, 1)
                                if (comprobantes.length === 0) {
                                    $("#visor").children().last().filter("canvas, img").remove()
                                    $("#sinArchivo").removeClass("d-none")
                                    setTimeout(() => {
                                        $("#solicitudDetalles").removeClass("show")
                                    }, 1500)
                                    return
                                }
                                leyendaNoComprobante.total = comprobantes.length
                                if (leyendaNoComprobante.actual > leyendaNoComprobante.total) leyendaNoComprobante.actual = leyendaNoComprobante.total
                                actualizaDatosComprobante(leyendaNoComprobante.actual - 1)
                            })
                        })
                    })
                }

                $(document).ready(() => {
                    setInputFechas("#fechasComprobaciones", { rango: true, iniD: -30 })
                    $("#btnBuscarComprobaciones").on("click", getComprobaciones)
                    configuraTabla(tabla)
                    $("#btnCompAnt").on("click", comprobanteAnterior)
                    $("#btnCompSig").on("click", comprobanteSiguiente)
                    $("#btnRechazarComprobante").on("click", rechazarComprobante)
                    $("#btnAceptarComprobante").on("click", aceptarComprobante)

                    $("#btnFullscreen").on("click", cambiarFullscreen)
                    $(document).on("keydown", (e) => {
                        if (e.key === "Escape" && fullScreen) cambiarFullscreen()
                    })
                    $("#btnMasZoom").on("click", aumentarZoom)
                    $("#btnMenosZoom").on("click", disminuirZoom)
                    $("#btnPagAnt").on("click", verPaginaAnterior)
                    $("#btnPagSig").on("click", verPaginaSiguiente)

                    visorPDF = window.pdfjsLib
                    visorPDF.GlobalWorkerOptions.workerSrc = "/assets/vendor/libs/pdf-viewer/pdf.worker.mjs"
                    getComprobaciones()
                })
            </script>
        HTML;

        self::set("titulo", "Validación de comprobantes");
        self::set("css", $css);
        self::set("script", $script);
        self::render("viaticos_validacion");
    }

    public function getComprobaciones()
    {
        self::respuestaJSON(ViaticosDAO::getComprobaciones($_POST));
    }

    public function getComprobantesSolicitud_VG()
    {
        self::respuestaJSON(ViaticosDAO::getComprobantesSolicitud_VG($_POST));
    }

    public function actualizaEstatusComprobante()
    {
        self::respuestaJSON(ViaticosDAO::actualizaEstatusComprobante($_POST));
    }
}
