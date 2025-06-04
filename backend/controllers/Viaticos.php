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
                                texto: "Ver",
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
                                // numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
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
                        //"<span>Estatus</span>" +
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
                        .append("<p><b>Importante:</b> El registro de esta solicitud no garantiza su aprobación ni obliga a la empresa a realizar pagos o reembolsos.</p>")
                        .append("<p>¿Desea continuar?</p>")

                    confirmarMovimiento(mensaje).then((continuar) => {
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
                        $("#notificacionEntrega").text("De ser autorizada, esta solicitud sera pagada el " + fechaEntrega.format("D [de] MMMM."))
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
                        modalOrigen.modal("show")
                    })
                }

                const calcularFechaPago = () => {
                    const fecha = moment()
                    const dia = fecha.isoWeekday()
                    const hora = fecha.hour()
                    const minuto = fecha.minute()

                    if (dia >= 1 && dia <= 3) {
                        if (hora < 12 || (hora === 12 && minuto < 0)) {
                            return fecha.clone().isoWeekday(4)
                        }
                    }
                    
                    return fecha.clone().isoWeekday(8)
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
                    fila.append("<td>" + parametros.concepto + "</td>")
                    fila.append("<td>" + moment(parametros.fecha).format(MOMENT_FRONT) + "</td>")
                    fila.append("<td>" + numeral(parametros.total).format(NUMERAL_MONEDA) + "</td>")
                    fila.append(
                        '<td><button type="button" class="btn btn-danger btn-sm" onclick="eliminaComprobanteGastos(this)"><i class="fa fa-trash">&nbsp;</i>Eliminar</button></td>'
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
                                    parametros.fecha,
                                    parametros.conceptoNombre,
                                    parametros.total,
                                    true
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

                const iniciarCamara = () => {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices
                            .getUserMedia({ video: true })
                            .then((stream) => {
                                const video = document.getElementById("videoComprobante")
                                video.srcObject = stream
                                video.play()
                            })
                            .catch((error) => {
                                showError("Error al acceder a la cámara: " + error.message)
                            })
                    } else {
                        showError("La cámara no está disponible en este dispositivo.")
                    }
                }

                const detenerCamara = () => {
                    const video = document.getElementById("videoComprobante")
                    if (video.srcObject) {
                        const stream = video.srcObject
                        const tracks = stream.getTracks()
                        tracks.forEach((track) => track.stop())
                        video.srcObject = null
                    }
                }

                const tomarFoto = () => {
                    const video = document.getElementById("videoComprobante")
                    const canvas = document.getElementById("canvasComprobante")
                    const context = canvas.getContext("2d")

                    if (video.srcObject) {
                        context.drawImage(video, 0, 0, canvas.width, canvas.height)
                        const fotoData = canvas.toBlob((blob) => {
                            const tiempo = moment().format("YYYYMMDD_HHmmss")
                            const archivoFoto = new File([blob], "foto_" + tiempo + ".jpg", {
                                type: "image/jpeg"
                            })
                            const dataTransfer = new DataTransfer()
                            dataTransfer.items.add(archivoFoto)
                            $("#comprobante")[0].files = dataTransfer.files
                            $("#comprobante").trigger("change")
                        }, "image/jpeg")
                        $("#modalTomarFoto").modal("hide")
                    } else {
                        showError("No hay video disponible para tomar una foto.")
                    }
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

                        const cancelada_rechazada = [6, 7].includes(numeral(informacion.ESTATUS_ID).value())

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
                            const mostrarE = informacion.TIPO_ID == 1 && informacion.ESTATUS_ID == 3
                            const fila = getFilaComprobante(comprobante.ID, comprobante.FECHA_REGISTRO, comprobante.CONCEPTO, comprobante.TOTAL, mostrarE)
                            $("#tbodyVerComprobantesSolicitud").append(fila)
                        })

                        $("#modalVerSolicitud").modal("show")
                        $("#modalVerSolicitud").on("hidden.bs.modal", () => {
                            if (tiempoRestante === null) return
                            clearInterval(tiempoRestante)
                        })
                    })
                }

                const getFilaComprobante = (id, fecha, concepto, total, eliminar = false) => {
                    return "<tr>" +
                        "<td class='d-none'>" + id + "</td>" +
                        "<td>" + moment(fecha).format(MOMENT_FRONT) + "</td>" +
                        "<td>" + concepto + "</td>" +
                        "<td>" + numeral(total).format(NUMERAL_MONEDA) + "</td>" +
                        "<td>" + menuAcciones([
                                    {
                                        texto: "Comprobante",
                                        icono: "fa-eye",
                                        funcion: "verComprobante('" + id + "')"
                                    },
                                    eliminar ? "divisor" : null,
                                    eliminar ? {
                                        texto: "Eliminar",
                                        icono: "fa-trash",
                                        funcion: "eliminaComprobanteViaticos('" + id + "')",
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

                    $("#modalTomarFoto").on("shown.bs.modal", iniciarCamara)
                    $("#modalTomarFoto").on("hidden.bs.modal", detenerCamara)
                    $("#btnTomarFoto").on("click", () =>  $("#modalTomarFoto").modal("show"))
                    $("#btnCapturarFoto").on("click", tomarFoto)
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
        $resultado = self::respuesta(true, 'Datos de la solicitud', [
            'informacion' => $informacion['datos'],
            'comprobantes' => $comprobantes['datos']
        ]);

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

    public function eliminaComprobante_V()
    {
        self::respuestaJSON(ViaticosDAO::eliminaComprobante_V($_POST));
    }

    public function finalizarComprobacion()
    {
        self::respuestaJSON(ViaticosDAO::finalizaComprobacion_V($_POST));
    }

    public function Entrega()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"

                const getSolicitudes = () => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        usuario: $_SESSION[usuario_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesEntrega", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const datos = respuesta.datos.map(solicitud => {
                            const acciones = "<button type='button' class='btn dropdown-toggle hide-arrow' data-bs-toggle='dropdown' aria-expanded='false'><i class='fa fa-ellipsis-vertical'></i></button>" +
                                            "<div class='dropdown-menu'>" +
                                            "<a class='dropdown-item' href='javascript:;' onclick='verSolicitud(" + solicitud.ID + ")'><i class='fa fa-eye'>&nbsp;</i>Detalles</a>" +
                                            "</div>"
                            
                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.PROYECTO,
                                moment(solicitud.AUTORIZACION_FECHA).format(MOMENT_FRONT),
                                numeral(solicitud.AUTORIZACION_MONTO).format(NUMERAL_MONEDA),
                                acciones
                            ]
                        });

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango:true, iniD: -30 })
                    configuraTabla(tabla)
                    getSolicitudes()
                });
            </script>
        HTML;

        self::set("titulo", "Entrega y devolución");
        self::set("script", $script);
        self::render("viaticos_entrega");
    }

    public function getSolicitudesEntrega()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesEntrega($_POST));
    }
}
