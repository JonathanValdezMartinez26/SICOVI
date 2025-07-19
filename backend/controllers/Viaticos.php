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
                const conceptosViaticos = []
                let valSolicitud = null,
                    valComprobante = null,
                    valConcepto = null,
                    modalConceptos = null

                const getSolicitudes = (persistirVista = false) => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        usuario: $_SESSION[usuario_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesUsuario", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        if (numeral($("#solActivas").val()).value() >= 3) {
                            $("#btnAgregar").attr("disabled", true)
                            showWarning("Tiene 3 o más solicitudes en proceso o no finalizadas. No podrá registrar una nueva solicitud hasta que finalice o cancele alguna de ellas.")
                        } else $("#btnAgregar").attr("disabled", false)
                        
                        const resumen = {}
                        const datos = respuesta.datos.map((solicitud) => {
                            const ver = {
                                texto: "Detalles",
                                icono: "fa-eye",
                                funcion: "verSolicitud(" + solicitud.ID + ")"
                            }

                            const c = {
                                texto: "Cancelar",
                                icono: "fa-trash",
                                funcion: "cancelarSolicitud(" + solicitud.ID + ")",
                                clase: "text-danger delete-record"
                            }

                            let cancelar = null

                            if (solicitud.TIPO_ID == 1) {
                                cancelar = solicitud.ESTATUS_NOMBRE == catEstatus_VG.solicitada || solicitud.ESTATUS_NOMBRE == catEstatus_VG.autorizada ? c : null
                            }

                            if (solicitud.TIPO_ID == '') {
                                cancelar = solicitud.ESTATUS_NOMBRE == catEstatus_VG.comprobada || solicitud.ESTATUS_NOMBRE == catEstatus_VG.autorizada ? c : null
                            }

                            if (!resumen[solicitud.ESTATUS_ID]) {
                                resumen[solicitud.ESTATUS_ID] = {}
                                resumen[solicitud.ESTATUS_ID].total = 1
                                resumen[solicitud.ESTATUS_ID].color = solicitud.ESTATUS_COLOR
                                resumen[solicitud.ESTATUS_ID].estatus = solicitud.ESTATUS_NOMBRE
                            } else resumen[solicitud.ESTATUS_ID].total += 1

                            const solicitado = numeral(solicitud.MONTO || 0).value()
                            const entregado = numeral(solicitud.ENTREGA_MONTO || 0).value()
                            const comprobado = numeral(solicitud.COMPROBACION_MONTO || 0).value()
                            const ajuste = numeral(solicitud.DIFERENCIA_MONTO || 0).value()
                            
                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.PROYECTO,
                                getFechas(solicitud.REGISTRO, solicitud.DESDE, solicitud.HASTA),
                                getMontos(solicitado, entregado, comprobado, ajuste),
                                getEstatus(solicitud.ESTATUS_NOMBRE, solicitud.ESTATUS_COLOR),
                                menuAcciones([ver, (cancelar && "divisor"), cancelar])
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

                const getMontos = (solicitado, entregado, comprobado, ajuste) => {
                    const getItem = (icono, color, label, valor, {clasesDiv = ""} = {}) => {
                        return "<div class='d-flex align-items-center justify-content-between " + clasesDiv + "'><span><i class='fa fa-" + icono + " " + color + "'></i> " + label + ": </span>" + numeral(valor).format(NUMERAL_MONEDA) + "</div>"
                    }
                    
                    const diferencia = numeral(comprobado).subtract(entregado).subtract(ajuste).value()
                    if (ajuste) {
                        let color = ajuste > 0 ? "text-success" : "text-danger"
                        let tipoAjuste = ajuste > 0 ? "(Cobro)" : "(Pago)"
                        ajuste = getItem("handshake-simple", color, "Ajuste " + tipoAjuste, numeral(ajuste).format(NUMERAL_MONEDA))

                    } else ajuste = ""

                    return getItem("hand-holding-dollar", "text-primary", "Solicitado", solicitado, {clasesDiv:"mb-3" }) +
                        getItem("wallet", "text-warning", "Entregado", entregado) +
                        getItem("file-invoice-dollar", "text-info", "Comprobado", comprobado) +
                        ajuste +
                        getItem("scale-balanced", diferencia < 0 ? "text-danger" : diferencia > 0 ? "text-success" : "", "Diferencia", diferencia, {clasesDiv: "border-top"})
                }
                
                const getFechas = (registro, inicio, fin) => {
                    const getItem = (icono, label, valor, borde = false) => {
                        return "<div class='" + (borde && "border-top") + "'>" +
                            "<i class='fa fa-" + icono + "'></i><span class='fw-bold'> " + label + ": </span>" +
                            "<span class='text-nowrap'>" + valor + "</span>" +
                            "</div>"
                    }
                    return getItem("calendar", "Registro", moment(registro).format(MOMENT_FRONT_HORA)) +
                        getItem("calendar", "Desde", moment(inicio).format(MOMENT_FRONT), true) +
                        getItem("calendar", "Hasta", moment(fin).format(MOMENT_FRONT))
                }

                const getEstatus = (estatus, color) => {
                    if (estatus == catEstatus_VG.solicitada) estatus = "SOLICITUD<br>(PENDIENTE DE AUTORIZACIÓN)"
                    if (estatus == catEstatus_VG.autorizada) estatus = "SOLICITUD AUTORIZADA<br>(PENDIENTE DE ENTREGA<br>POR TESORERÍA)"
                    if (estatus == catEstatus_VG.entregada) estatus = "ENTREGADA<br>(POR TESORERÍA)<br>(PENDIENTE DE COMPROBACIÓN)"
                    if (estatus == catEstatus_VG.comprobada) estatus = "COMPROBANTES REGISTRADOS<br>(PENDIENTE DE AUTORIZACIÓN)"
                    if (estatus == catEstatus_VG.aceptada) estatus = "ACEPTADA<br>(COMPROBANTES AUTORIZADOS<br>POR EL JEFE)<br>(PENDIENTE DE VALIDACIÓN POR TESORERÍA)"
                    if (estatus == catEstatus_VG.validada) estatus = "COMPROBANTES VALIDADOS<br>(POR TESORERÍA)"

                    return "<div class='d-flex flex-column align-items-center justify-content-center'>" +
                        "<span class='badge rounded-pill " + color + "'>" + estatus + "</span>" +
                        "</div>"
                }

                const getTarjetaSolicitud = (color, titulo, total) => {
                    return (
                        "<div class='col-auto'>" +
                            "<div class='card'>" +
                                "<div class='card-body'>" +
                                    "<div class='card-info text-center'>" +
                                        "<div class='d-flex flex-column align-items-center justify-content-center'>" +
                                            "<span class='badge rounded-pill " + color + "'>" + titulo + "</span>" +
                                        "</div>" +
                                        "<h4 class='card-title mb-0 me-2'>" + total + "</h4>" +
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
                                message: "Debe añadir un concepto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "Debe añadir un concepto"
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
                            accionCancel: () => {
                                limpiaComprobantes()
                                limpiaConceptos()
                            }
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

                const validacionConcepto = () => {
                    const campos = {
                        conceptoViaticos: {
                            callback: {
                                callback: (input) => {
                                    const concepto = $("#conceptoViaticos").val()
                                    return concepto === null || concepto === "" ? false : true
                                },
                                message: "Debe seleccionar un concepto"
                            }
                        },
                        montoConcepto: {
                            notEmpty: {
                                message: "Debe ingresar un monto"
                            },
                            greaterThan: {
                                min: 1,
                                message: "El monto debe ser mayor a 0"
                            }
                        },
                        conceptoObservaciones: {
                            stringLength: {
                                max: 500,
                                message: "Las observaciones no deben exceder los 500 caracteres"
                            }
                        }
                    }

                    valConcepto = setValidacionModal(
                        "#modalAgregarConcepto",
                        campos,
                        "#agregarConcepto",
                        agregarConcepto,
                        "#cancelaConcepto"
                    )
                }

                const getParametros = () => {
                    const fechas = getInputFechas("#fechasNuevaSolicitud", true)
                    const parametros = {
                        tipo: $("#tipoSolicitud").val(),
                        proyecto: $("#proyecto").val(),
                        desde: fechas.inicio,
                        hasta: fechas.fin,
                        monto: numeral($("#montoVG").val()).value(),
                        sucursal: $("#sucursalEntrega").val(),
                    }

                    parametros.fechaLimite = parametros.tipo == 1
                        ? getFechaLimite(fechas.fin).format(MOMENT_BACK)
                        : moment().format(MOMENT_BACK)
                    parametros.comprobado = parametros.tipo == 2 ? parametros.monto : 0
                    return parametros
                }

                const getFechaLimite = (fecha) => {
                    let dias = 3
                    fecha = moment(fecha)

                    while (dias > 0) {
                        fecha.add(1, 'days')
                        const dia = fecha.day()
                        if (dia !== 0 && dia !== 6) dias--
                    }

                    return fecha
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
                            Object.keys(datos).forEach((key) => {
                                formData.append(key, datos[key])
                            })

                            if (datos.tipo == 1) {
                                conceptosViaticos.forEach((concepto) => {
                                    formData.append("concepto[]", concepto.concepto)
                                    formData.append("montoConcepto[]", concepto.total)
                                    formData.append("observacionesConcepto[]", concepto.observaciones)
                                })
                            } else {
                                comprobantesGastos.forEach((comprobante) => {
                                    formData.append("comprobante[]", comprobante.comprobante)
                                    formData.append("conceptoComprobante[]", comprobante.concepto)
                                    formData.append("fechaComprobante[]", comprobante.fecha)
                                    formData.append("subtotalComprobante[]", comprobante.subtotal)
                                    formData.append("totalComprobante[]", comprobante.total)
                                    formData.append("observacionesComprobante[]", comprobante.observaciones)
                                })
                            }

                            consultaServidor(
                                "/viaticos/registraSolicitud_VG",
                                formData,
                                (respuesta) => {
                                    $("#registraSolicitud").attr("disabled", false)
                                    if (!respuesta.success) return showError(respuesta.mensaje)

                                    showSuccess("Solicitud registrada correctamente").then(() => {
                                        $("#solActivas").val(numeral($("#solActivas").val()).add(1).value())
                                        $("#modalNuevaSolicitud").modal("hide")
                                        resetValidacion(valSolicitud, true)
                                        resetValidacion(valConcepto, true)
                                        resetValidacion(valComprobante, true)
                                        limpiaConceptos()
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

                const limpiaConceptos = () => {
                    $("#tbodyConceptos").empty()
                    conceptosViaticos.length = 0
                    $("#conceptoViaticos option").each((_, option) => $(option).show())
                    $("#tablaConceptos tfoot").show()
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

                const configuraModalConcepto = () => {
                    $(".btnAgregarConcepto").on("click", () => {
                        modalConceptos = $('.modal.show')
                        modalConceptos.modal("hide")
                        $("#agregarConcepto").show()
                        $("#actualizarConcepto").hide()
                        $("#modalAgregarConcepto").modal("show")
                    })

                    $("#modalAgregarConcepto").on("hidden.bs.modal", () => {
                        if ($('.modal.show').length > 0) return
                        modalConceptos.modal("show")
                    })
                }

                const calcularFechaPago = () => {
                    const fecha = moment()
                    const dia = fecha.isoWeekday()
                    const hora = fecha.hour()
                    let diaPago = 8 

                    if (dia >= 1 && dia <= 2) diaPago = 4
                    if (dia == 3 && hora < 12) diaPago = 4
                    if (dia == 5 && hora < 17) diaPago = 10
                    
                    return fecha.clone().isoWeekday(diaPago)
                }

                const changeTipoSolicitud = () => {
                    const tipo = $("#tipoSolicitud").val()
                    limpiaComprobantes()
                    $("#montoVG").val("0.00")

                    if (tipo === "1") {
                        $("#lblMontoVG").text("Monto Solicitado")
                        $("#conceptosViaticos").show()
                        $("#comprobantesGastos").hide()
                        updateInputFechas("#fechasNuevaSolicitud", { minD: 0, maxD: 30 })
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#conceptosViaticos").hide()
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
                        $("#verFechaI").val(moment(informacion.DESDE).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(informacion.HASTA).format(MOMENT_FRONT))
                        $("#verEstatus").val(informacion.ESTATUS_NOMBRE)

                        const cancelada_rechazada = [catEstatus_VG.cancelada, catEstatus_VG.rechazada].includes(numeral(informacion.ESTATUS_ID).value())
                        $("#tbodyVerConceptos").empty()
                        if (informacion.TIPO_ID == 1) {
                            $("#verTablaConceptosViaticos").removeClass("d-none")
                            const conceptos = respuesta.datos.conceptos || []
                            conceptos.map((concepto) => {
                                const fila = getFilaConcepto(informacion, concepto)
                                $("#conceptoViaticos option").each((_, option) => {
                                    if ($(option).val() == concepto.CONCEPTO_ID) $(option).hide()
                                })
                                $("#tbodyVerConceptos").append(fila)
                            })
                            const visibles = $("#conceptoViaticos option").filter(function () {
                                return $(this).css("display") !== "none"
                            })
                            if (visibles.length === 1 || informacion.ESTATUS_NOMBRE !== catEstatus_VG.solicitada) $("#tablaVerConceptos tfoot").hide()
                            else $("#tablaVerConceptos tfoot").show()
                        } else {
                            $("#verTablaConceptosViaticos").addClass("d-none")
                        }

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
                            $("#verObsAutorizado").val(informacion.AUTORIZACION_OBSERVACION || "")
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
                            $("#verSucursalEntrega").val(informacion.ENTREGA_SUCURSAL)
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

                const getFilaConcepto = (informacion, concepto) => {
                    let acciones = ""
                    if (informacion.ESTATUS_NOMBRE == catEstatus_VG.solicitada) {
                        acciones = menuAcciones([
                            {
                                texto: "Editar",
                                icono: "fa-pen-to-square",
                                funcion: "editarConceptoSolicitud('" + concepto.ID + "')"
                            },
                            {
                                texto: "Eliminar",
                                icono: "fa-trash",
                                funcion: "eliminaConceptoSolicitud('" + concepto.ID + "')",
                                clase: "text-danger"
                            }
                        ])
                    }

                    return "<tr id='" + concepto.ID + "'>" +
                        "<td class='d-none'>" + concepto.CONCEPTO_ID + "</td>" +
                        "<td>" + concepto.CONCEPTO_NOMBRE + "</td>" +
                        "<td>" + (concepto.OBSERVACIONES ?? "") + "</td>" +
                        "<td>" + numeral(concepto.MONTO).format(NUMERAL_MONEDA) + "</td>" +
                        "<td>" + acciones + "</td>" +
                        "</tr>"
                }

                const getFilaComprobante = (informacion, comprobante) => {
                    const editar = (informacion.TIPO_ID == 1 && informacion.ESTATUS_NOMBRE == catEstatus_VG.entregada) || (informacion.TIPO_ID == 1 && informacion.ESTATUS_NOMBRE == catEstatus_VG.aceptada && comprobante.ESTATUS_ID != 1)
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
                                    comprobante.MOTIVO_RECHAZO && {
                                        texto: "Motivo",
                                        icono: "fa-info-circle",
                                        funcion: "showInfo('" + comprobante.MOTIVO_RECHAZO + "')"
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
                                    $("#solActivas").val(numeral($("#solActivas").val()).subtract(1).value())
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
                
                const agregarConcepto = () => {
                    const parametros = {
                        concepto: numeral($("#conceptoViaticos").val()).value(),
                        conceptoNombre: $("#conceptoViaticos option:selected").text(),
                        total: numeral($("#montoConcepto").val()).value(),
                        observaciones: $("#conceptoObservaciones").val()
                    }

                    if (modalConceptos.attr("id") === "modalVerSolicitud") {
                        return agregaConceptoSolcitud(parametros)
                    }
                    
                    conceptosViaticos.push(parametros)
                    let monto = $("#montoVG")
                    let tabla = $("#tablaConceptos tbody")
                    monto.val(numeral(monto.val()).add(parametros.total).format(NUMERAL_DECIMAL))

                    const fila = $("<tr></tr>")
                    fila.append("<td>" + parametros.conceptoNombre + "</td>")
                    fila.append("<td>" + parametros.observaciones + "</td>")
                    fila.append("<td>" + numeral(parametros.total).format(NUMERAL_MONEDA) + "</td>")
                    fila.append(
                        "<td>" +
                        "<button type='button' class='btn btn-danger btn-sm' onclick='eliminaConcepto(this)'><i class='fa fa-trash'>&nbsp;</i>Eliminar</button>" +
                        "</td>"
                    )

                    tabla.append(fila)
                    $("#conceptoViaticos option:selected").hide()
                    const visibles = $("#conceptoViaticos option").filter(function () {
                        return $(this).css("display") !== "none"
                    })
                    if (visibles.length === 1) tabla.hide()

                    $("#modalAgregarConcepto").modal("hide")
                    resetValidacion(valConcepto, true)
                }

                const eliminaConcepto = (btn) => {
                    const fila = $(btn).closest("tr")
                    const indice = fila.index()
                    const monto = conceptosViaticos[indice].total
                    $("#conceptoViaticos option[value='" + conceptosViaticos[indice].concepto + "']").show()
                    $("#tablaConceptos tfoot").show()

                    fila.remove()
                    conceptosViaticos.splice(indice,1)
                    $("#montoVG").val(numeral($("#montoVG").val()).subtract(monto).format(NUMERAL_DECIMAL))
                }

                const agregaConceptoSolcitud = (parametros) => {
                    confirmarMovimiento("¿Desea agregar este concepto a la solicitud?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            parametros.solicitudId = $("#verSolicitudId").val()
                            consultaServidor("/viaticos/agregaConceptoSolicitud_V", parametros, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                const fila = getFilaConcepto({ ESTATUS_NOMBRE: catEstatus_VG.solicitada }, {
                                    ID: respuesta.datos.CONCEPTO_ID,
                                    CONCEPTO_ID: parametros.concepto,
                                    CONCEPTO_NOMBRE: parametros.conceptoNombre,
                                    OBSERVACIONES: parametros.observaciones,
                                    MONTO: parametros.total
                                })

                                $("#tbodyVerConceptos").append(fila)
                                $("#conceptoViaticos option[value='" + parametros.concepto + "']").hide()
                                const visibles = $("#conceptoViaticos option").filter(function () {
                                    return $(this).css("display") !== "none"
                                })
                                if (visibles.length === 1) $("#tablaVerConceptos tfoot").hide()
                                else $("#tablaVerConceptos tfoot").show()

                                $("#verMontoSol").val(
                                    numeral($("#verMontoSol").val()).add(parametros.total).format(NUMERAL_MONEDA)
                                )
                                $("#modalAgregarConcepto").modal("hide")
                                resetValidacion(valConcepto, true)
                            })
                        } else {
                            $("#modalAgregarConcepto").modal("hide")
                        }
                    })
                }

                const editarConceptoSolicitud = (conceptoId) => {
                    const fila = $("#tbodyVerConceptos #" + conceptoId)
                    const concepto = fila.find("td").eq(0).text().trim()
                    const observaciones = fila.find("td").eq(2).text().trim()
                    const monto = numeral(fila.find("td").eq(3).text().trim()).value()

                    $("#conceptoViaticos").val(concepto).trigger("change")
                    $("#montoConcepto").val(numeral(monto).format(NUMERAL_DECIMAL))
                    $("#conceptoObservaciones").val(observaciones)

                    modalConceptos = $('.modal.show')
                    modalConceptos.modal("hide")
                    $("#modalAgregarConcepto").modal("show")
                    $("#actualizarConcepto").prop("concepto-id", conceptoId)
                    $("#agregarConcepto").hide()
                    $("#actualizarConcepto").show()
                }

                const actualizaConceptoSolicitud = () => {
                    const conceptoId = $("#actualizarConcepto").prop("concepto-id")
                    const concepto = numeral($("#conceptoViaticos").val()).value()
                    const conceptoNombre = $("#conceptoViaticos option:selected").text()
                    const monto = numeral($("#montoConcepto").val()).value()
                    const observaciones = $("#conceptoObservaciones").val()

                    consultaServidor("/viaticos/actualizaConceptoSolicitud_V", {
                        conceptoId,
                        concepto,
                        conceptoNombre,
                        monto,
                        observaciones
                    }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const fila = $("#tbodyVerConceptos #" + conceptoId)
                        const conceptoAnterior = fila.find("td").eq(0).text().trim()
                        const montoAnterior = numeral(fila.find("td").eq(3).text().trim()).value()

                        fila.find("td").eq(0).text(concepto)
                        fila.find("td").eq(1).text(conceptoNombre)
                        fila.find("td").eq(2).text(observaciones)
                        fila.find("td").eq(3).text(numeral(monto).format(NUMERAL_MONEDA))
                        $("#verMontoSol").val(numeral($("#verMontoSol").val()).subtract(montoAnterior).add(monto).format(NUMERAL_MONEDA))
                        $("#modalAgregarConcepto").modal("hide")
                        $("#conceptoViaticos option[value='" + concepto + "']").hide()
                        $("#conceptoViaticos option[value='" + conceptoAnterior + "']").show()
                        resetValidacion(valConcepto, true)
                        showSuccess("Concepto actualizado correctamente")
                    })
                }

                const eliminaConceptoSolicitud = (conceptoId) => {
                    if ($("#tbodyVerConceptos").find("tr").length <= 1) {
                        const div = $("<div></div>")
                        div.append("<p>La solicitud debe tener al menos un concepto.</p>")
                        div.append("<p>Si desea cancelar la solicitud, puede hacerlo desde el botón de cancelar o edite el concepto.</p>")
                        return showError(div)
                    }

                    confirmarMovimiento("¿Desea eliminar el concepto de la solicitud?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            consultaServidor("/viaticos/eliminaConceptoSolicitud_V", { conceptoId }, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje)
                                const fila = $("#tbodyVerConceptos #" + conceptoId)
                                const concepto = fila.find("td").eq(0).text().trim()
                                const monto = numeral(fila.find("td").eq(3).text().trim()).value()
                                fila.remove()
                                $("#verMontoSol").val(
                                    numeral($("#verMontoSol").val()).subtract(monto).format(NUMERAL_MONEDA)
                                )
                                $("#conceptoViaticos option[value='" + concepto + "']").show()
                                const visibles = $("#conceptoViaticos option").filter(function () {
                                    return $(this).css("display") !== "none"
                                })
                                if (visibles.length === 1) $("#tablaVerConceptos tfoot").hide()
                                else $("#tablaVerConceptos tfoot").show()
                                showSuccess("Concepto eliminado correctamente")
                            })
                        }
                    })
                }

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango: true, iniD: -30 })
                    setInputFechas("#fechasNuevaSolicitud", { rango: true, minD: 0, maxD: 30, enModal: true })
                    setInputFechas("#fechaComprobante", { enModal: true })
                    setInputMoneda("#montoVG, #montoConcepto, #montoComprobante")

                    configuraTabla(tabla)
                    configuraModalComprobante()
                    configuraModalConcepto()
                    validacionSolicitud()
                    validacionComprobante()
                    validacionConcepto()

                    $("#btnAgregar").on("click", () => {
                        $("#modalNuevaSolicitud").modal("show")
                        $("#montoVG").val("0.00")
                    })
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
                        $("#conceptoViaticos option").each((_, option) => {
                            $(option).show()
                        })
                        getSolicitudes(true)
                        $('#acordionVer .accordion-collapse').removeClass('show')
                        $('#acordionVer .accordion-collapse').first().addClass('show')
                        $('#acordionVer .accordion-button').addClass('collapsed')
                        $('#acordionVer .accordion-button').first().removeClass('collapsed')
                    })
                    $("#btnFinalizarComprobacion").on("click", finalizarComprobacion)
                    $("#actualizarConcepto").on("click", actualizaConceptoSolicitud)
                    
                    getSolicitudes()
                })
            </script>
        HTML;

        $catSucursales = ViaticosDAO::getCatalogoSucursales();
        $sucursales = '';
        if ($catSucursales['success']) {
            foreach ($catSucursales['datos'] as $sucursal) {
                $seleccion = $_SESSION['sucursal_id'] == $sucursal['ID'] ? 'selected' : '';
                $sucursales .= "<option value='{$sucursal['ID']}' $seleccion>{$sucursal['NOMBRE']}</option>";
            }
        }

        $catConceptos = ViaticosDAO::getCatalogoConceptosViaticos();
        $conceptos = '<option></option>';
        if ($catConceptos['success']) {
            foreach ($catConceptos['datos'] as $concepto) {
                $conceptos .= "<option value='{$concepto['ID']}' lbl-desc='{$concepto['DESCRIPCION']}'>{$concepto['NOMBRE']}</option>";
            }
        }

        $activas = ViaticosDAO::getSolicitudesActivas_VG(['usuario' => $_SESSION['usuario_id']]);

        self::set("titulo", "Solicitud de Viáticos y Gastos");
        self::set("script", $script);
        self::set("sucursales", $sucursales);
        self::set("conceptos", $conceptos);
        self::set("activas", $activas['datos']['ACTIVAS'] ?? 0);
        self::render("viaticos_solicitud");
    }

    public function getSolicitudesUsuario()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesUsuario_VG($_POST));
    }

    public function getResumenSolicitud_VG()
    {
        $informacion = ViaticosDAO::getResumenSolicitud_VG($_POST);
        $conceptos = ViaticosDAO::getConceptosSolicitud_V($_POST);
        $comprobantes = ViaticosDAO::getComprobantesSolicitud_VG($_POST);

        $success = $informacion['success'] && $conceptos['success'] && $comprobantes['success'];
        $mensaje = 'Resumen de la solicitud obtenido correctamente.';
        if (!$success) {
            $mensaje = $informacion['mensaje'] ?? $comprobantes['mensaje'] ?? 'Error al obtener la información de la solicitud.';
            $errores = [
                'informacion' => $informacion['error'] ?? 'No se pudo obtener la información de la solicitud.',
                'conceptos' => $conceptos['error'] ?? 'No se pudieron obtener los conceptos de la solicitud.',
                'comprobantes' => $comprobantes['error'] ?? 'No se pudieron obtener los comprobantes de la solicitud.'
            ];
        }

        $resultado = self::respuesta($success, $mensaje, [
            'informacion' => $informacion['datos'],
            'conceptos' => $conceptos['datos'],
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
                $errores[] = "El archivo '{$_FILES['comprobante']['name'][$key]}' tiene un error y no se puede guardar. ({$_FILES['comprobante']['error'][$key]})";
                continue;
            }

            if ($_FILES['comprobante']['size'][$key] > 5 * 1024 * 1024) {
                $errores[] = "El archivo '{$_FILES['comprobante']['name'][$key]}' excede el tamaño máximo permitido de 5 MB.";
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
                $errores[] = "Error al procesar el archivo '{$_FILES['comprobante']['name'][$key]}': " . $e->getMessage();
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

    public function agregaConceptoSolicitud_V()
    {
        self::respuestaJSON(ViaticosDAO::agregaConceptoSolicitud_V($_POST));
    }

    public function actualizaConceptoSolicitud_V()
    {
        self::respuestaJSON(ViaticosDAO::actualizaConceptoSolicitud_V($_POST));
    }

    public function eliminaConceptoSolicitud_V()
    {
        self::respuestaJSON(ViaticosDAO::eliminaConceptoSolicitud_V($_POST));
    }

    public function Autorizacion()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"
                const tipos = {
                    solicitud: "solicitud",
                    comprobacion: "comprobacion"
                }
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
                            if (solicitud.ESTATUS_NOMBRE == catEstatus_VG.autorizada) color = "success"
                            if (solicitud.ESTATUS_NOMBRE == catEstatus_VG.rechazada) color = "danger"

                            let etiqueta = "SOLICITUD<br>(PENDIENTE DE AUTORIZAR)"
                            if (solicitud.ESTATUS_NOMBRE == catEstatus_VG.comprobada) etiqueta = "COMPROBACIÓN<br>(PENDIENTE DE AUTORIZAR)"
                            if (solicitud.ESTATUS_NOMBRE == catEstatus_VG.autorizada) etiqueta = "SOLICITUD AUTORIZADA"
                            if (solicitud.ESTATUS_NOMBRE == catEstatus_VG.rechazada) etiqueta = "SOLICITUD RECHAZADA"

                            const estatus = "<span class='badge rounded-pill bg-label-" + color + "'>" + etiqueta + "</span>"
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
                                getGeneral(solicitud.TIPO_NOMBRE, solicitud.USUARIO_NOMBRE, solicitud.PROYECTO),
                                getFechas(solicitud.FECHA_REGISTRO, solicitud.DESDE, solicitud.HASTA),
                                numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
                                estatus,
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const getItemTabla = (icono, label, valor, borde = true) => {
                    return "<div class='" + (borde && "border-top") + "'>" +
                        "<i class='fa fa-" + icono + "'></i><span class='fw-bold'> " + label + ": </span>" +
                        "<span class='text-nowrap'>" + valor + "</span>" +
                        "</div>"
                }

                const getGeneral = (tipo, usuario, proyecto) => {
                    return getItemTabla("bookmark", "Tipo", tipo, false) +
                        getItemTabla("user", "Solicitante", usuario) +
                        getItemTabla("briefcase", "Proyecto", proyecto)
                }

                const getFechas = (registro, inicio, fin) => {
                    return getItemTabla("calendar", "Registro", moment(registro).format(MOMENT_FRONT_HORA), false) +
                        getItemTabla("calendar", "Desde", moment(inicio).format(MOMENT_FRONT)) +
                        getItemTabla("calendar", "Hasta", moment(fin).format(MOMENT_FRONT), false)
                }

                const verSolicitud = (solicitudId) => {
                    consultaServidor("/viaticos/getResumenSolicitud_VG", { solicitudId }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const informacion = respuesta.datos.informacion

                        $("#rechazar").attr("disabled", false)
                        $("#autorizar").attr("disabled", false)

                        if (informacion.ESTATUS_NOMBRE == catEstatus_VG.autorizada) {
                            color = "success"
                            $("#autorizar").attr("disabled", true)
                        }
                        
                        if (informacion.ESTATUS_NOMBRE == catEstatus_VG.rechazada) {
                            color = "danger"
                            $("#rechazar").attr("disabled", true)
                        }

                        if (informacion.ESTATUS_NOMBRE == catEstatus_VG.comprobada) {
                            $("#verTitulo").text("Autorización de comprobación de gastos")
                            $("#btnVerListado").attr("data-bs-target", "#modalVerComprobantes")
                            $("#btnVerListado").html("<i class='far fa-eye'>&nbsp;</i>Comprobantes")
                            $("#montoAutorizado").attr("disabled", true)
                            $("#montoAutorizado").val(numeral(informacion.COMPROBACION_MONTO).format(NUMERAL_DECIMAL))
                            $("#autorizar").attr("tipo", tipos.comprobacion)
                            $("#rechazar").hide()
                            $("#tbodyComprobantes").empty()
                            const comprobantes = respuesta.datos.comprobantes || []
                            comprobantes.forEach((comprobante) => {
                                const fila = getFilaComprobante(informacion, comprobante)
                                $("#tbodyComprobantes").append(fila)
                            })
                        } else {
                            $("#verTitulo").text("Autorización de solicitud de viáticos")
                            $("#btnVerListado").attr("data-bs-target", "#modalVerConceptos")
                            $("#btnVerListado").html("<i class='far fa-eye'>&nbsp;</i>Conceptos")
                            $("#montoAutorizado").attr("disabled", false)
                            $("#montoAutorizado").val("")
                            $("#autorizar").attr("tipo", tipos.solicitud)
                            $("#rechazar").show()
                            $("#tbodyConceptos").empty()
                            const conceptos = respuesta.datos.conceptos || []
                            conceptos.map((concepto) => {
                                const fila = getFilaConcepto(informacion, concepto)
                                $("#tbodyConceptos").append(fila)
                            })
                        }
                        
                        $("#verSolicitante").val(informacion.USUARIO_NOMBRE)
                        $("#verSucursal").val(informacion.ENTREGA_SUCURSAL)
                        $("#verTipoSol").val(informacion.TIPO_NOMBRE)
                        $("#verSolicitudId").val(informacion.ID)
                        $("#verFechaSol").val(moment(informacion.REGISTRO).format(MOMENT_FRONT_HORA))
                        $("#verMontoSolicitado").val(numeral(informacion.MONTO).format(NUMERAL_MONEDA))
                        $("#verProyecto").val(informacion.PROYECTO)
                        $("#verFechaI").val(moment(informacion.DESDE).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(informacion.HASTA).format(MOMENT_FRONT))
                        //$("#observacionesAutorizacion").val(informacion.AUTORIZACION_OBSERVACION)
                        $("#modalVerAutorizacion").modal("show")
                    })
                }

                const getFilaConcepto = (informacion, concepto) => {
                    return "<tr id='" + concepto.ID + "'>" +
                        "<td>" + concepto.CONCEPTO_NOMBRE + "</td>" +
                        "<td>" + (concepto.OBSERVACIONES ?? "") + "</td>" +
                        "<td>" + numeral(concepto.MONTO).format(NUMERAL_MONEDA) + "</td>" +
                        "</tr>"
                }

                const getFilaComprobante = (informacion, comprobante) => {
                    const editar = (informacion.TIPO_ID == 1 && informacion.ESTATUS_NOMBRE == catEstatus_VG.entregada) || (informacion.TIPO_ID == 1 && informacion.ESTATUS_NOMBRE == catEstatus_VG.comprobada && comprobante.ESTATUS_ID != 1)
                    const eliminar = editar
                    const colores = [
                        "danger", "success"
                    ]
                    return "<tr>" +"<td>" + moment(comprobante.FECHA_REGISTRO).format(MOMENT_FRONT) + "</td>" +
                        "<td>" + comprobante.CONCEPTO_NOMBRE + "</td>" +
                        "<td>" + (comprobante.OBSERVACIONES ?? "") + "</td>" +
                        "<td>" + numeral(comprobante.TOTAL).format(NUMERAL_MONEDA) + "</td>" +
                        "</tr>"
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
                                    if ($("#montoAutorizado").attr("readonly")) return true
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
                        autorizar,
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

                const autorizar = () => {
                    const tipo = $("#autorizar").attr("tipo")
                    if (tipo === tipos.solicitud) autorizarSolicitud()
                    else autorizarComprobantes()
                }

                const autorizarSolicitud = () => {
                    const parametros = {
                        solicitudId: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: catEstatus_VG.autorizada,
                        monto: $("#montoAutorizado").val(),
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    actualizaSolicitud("¿Desea autorizar esta solicitud?", parametros, "La solicitud ha sido autorizada.")
                }

                const rechazarSolicitud = () => {
                    const parametros = {
                        solicitudId: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: catEstatus_VG.rechazada,
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    actualizaSolicitud("¿Desea rechazar esta solicitud?", parametros, "La solicitud ha sido rechazada.")
                }

                const autorizarComprobantes = () => {
                    const parametros = {
                        solicitudId: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: catEstatus_VG.aceptada,
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    confirmarMovimiento("¿Desea autorizar los comprobantes para esta solicitud?").then((continuar) => {
                        if (!continuar.isConfirmed) return
                        consultaServidor("/viaticos/autorizaSolicitud_VG", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess("Los comprobantes han sido autorizados.").then(() => {
                                $("#modalVerAutorizacion").modal("hide")
                                getSolicitudes()
                            })
                        })
                    })
                }

                const rechazarComprobantes = () => {
                    const parametros = {
                        solicitudId: $("#verSolicitudId").val(),
                        usuario: $_SESSION[usuario_id],
                        autorizado: catEstatus_VG.rechazada,
                        observaciones: $("#observacionesAutorizacion").val()
                    }

                    confirmarMovimiento("¿Desea rechazar los comprobantes para esta solicitud?").then((continuar) => {
                        if (!continuar.isConfirmed) return
                        consultaServidor("/viaticos/autorizaSolicitud_VG", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)
                            showSuccess("Los comprobantes han sido rechazados.").then(() => {
                                $("#modalVerAutorizacion").modal("hide")
                                getSolicitudes()
                            })
                        })
                    })
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
                    $("#modalVerConceptos, #modalVerComprobantes").on("hide.bs.modal", () => {
                        $("#modalVerAutorizacion").modal("show")
                    })

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
                            },
                            callback: {
                                message: "El monto no puede ser mayor al monto autorizado.",
                                callback: () => {
                                    return numeral($("#montoEntrega").val()).value() <= numeral($("#verMontoAutorizado").val()).value()
                                }
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
                        $("#verSucursal").val(informacion.ENTREGA_SUCURSAL)
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
                            solicitudId: $("#verSolicitudId").val(),
                            usuario: $_SESSION[usuario_id],
                            metodo: $("#metodoEntrega").val(),
                            monto: numeral($("#montoEntrega").val()).value(),
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
                                parametro.append("solicitudId", parametros.solicitudId)
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

        $duplicado = <<<HTML
            <div style="border-top: 1px dashed #000; margin-top: 10px;">
                {$plantilla['cuerpo']}
            </div>
        HTML;
        $mpdf->WriteHTML($duplicado);

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
                                getInfoComprobantes(comprobacion.REGISTRADOS, comprobacion.RECHAZADOS, comprobacion.ACEPTADOS),
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const getInfoComprobantes = (registrados, rechazados, aceptados) => {
                    const getItem = (icono, color, label, valor, {clasesDiv = ""} = {}) => {
                        return "<div class='d-flex align-items-center justify-content-between " + clasesDiv + "'><span><i class='fa fa-" + icono + " " + color + "'></i> " + label + ": </span>" + valor + "</div>"
                    }

                    return getItem("file-arrow-up", "text-info", "Registrados", registrados) +
                        getItem("ban", "text-danger", "Rechazados", rechazados) +
                        getItem("check", "text-success", "Aceptados", aceptados)
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
                        if (tipo.startsWith("image/")) await verImagen(url)
                        if (!tipo) throw new Error("Tipo de archivo no soportado")
                    } catch (error) {
                        $("#errorArchivo").removeClass("d-none")
                    } finally {
                        $("#cargandoArchivo").addClass("d-none")
                    }
                }

                const verImagen = async (url) => {
                    const img = document.createElement("img")
                    img.className = "file-content no-select mw-100 mh-100"
                    img.alt = "Archivo de imagen"

                    await new Promise((resolve, reject) => {
                        img.onload = () => resolve()
                        img.onerror = () => reject(new Error("Error al cargar la imagen"))
                        img.src = url
                    });

                    $("#visor").append(img)
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
                    Swal.fire({
                        title: "Confirmación",
                        html: "<div class='text-center'>¿Desea rechazar este comprobante?</div>" +
                            "<div class='text-center'>Indique el motivo del rechazo</div>" +
                            "<input type='text' id='motivoRechazo' class='form-control mayusculas' placeholder='Motivo del rechazo' required>",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Si, continuar",
                        cancelButtonText: "No",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        reverseButtons: true,
                        keydownListenerCapture: true,
                        customClass: {
                            confirmButton: "btn btn-success",
                            cancelButton: "btn btn-danger"
                        },
                        preConfirm: () => {
                            const observaciones = $("#motivoRechazo").val().trim()
                            if (!observaciones) {
                                Swal.showValidationMessage("Ingrese el motivo del rechazo.")
                                return false
                            }
                            return observaciones
                        }
                    }).then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            usuario: $_SESSION[usuario_id],
                            solicitudId: comprobante.SOLICITUD_ID,
                            comprobanteId: comprobante.ID,
                            observaciones: continuar.value,
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
                        usuario: $_SESSION[usuario_id],
                        solicitudId: comprobante.SOLICITUD_ID,
                        comprobanteId: comprobante.ID,
                        estatus: 1,
                    }

                    if (comprobantes.length !== 1) aceptar("¿Desea aceptar este comprobante?", parametros)
                    else {
                        const msg = comprobacion.TIPO_ID == 1 
                            ? "se dará por finalizada la comprobación de viáticos y se aplicaran los ajustes de saldo a favor o en contra."
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

    public function Ajustes()
    {
        $script = <<<HTML
            <script>
                const tabla = "#historialSolicitudes"

                const getSolicitudes = () => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)
                    const parametros = {
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesAjustes", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const datos = respuesta.datos.map((solicitud) => {
                            const acciones = menuAcciones([
                                {
                                    texto: "Detalles",
                                    icono: "fa-eye",
                                    funcion: "verDetalles(" + solicitud.ID + ")"
                                }
                            ])
                            
                            const diferencia = numeral(solicitud.DIFERENCIA)
                            const dif = diferencia.value() < 0 ? "down text-danger" : "up text-success"

                            return [
                                null,
                                solicitud.ID,
                                solicitud.USUARIO_NOMBRE,
                                "<i class='fa-solid fa-arrow-trend-" + dif + "'>&nbsp;</i>" + diferencia.format(NUMERAL_MONEDA),
                                acciones
                            ]
                        })

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const verDetalles = (id) => {
                    consultaServidor("/viaticos/getResumenSolicitud_VG", { solicitudId: id }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const informacion = respuesta.datos.informacion

                        $("#verSolicitudId").val(informacion.ID)
                        $("#verSolicitante").val(informacion.USUARIO_NOMBRE)
                        $("#verTipoSol").val(informacion.TIPO_NOMBRE)
                        $("#verFechaFinalizado").val(moment(informacion.ACTUALIZADO).format(MOMENT_FRONT_HORA))
                        $("#verMontoEntregado").val(numeral(informacion.ENTREGA_MONTO).format(NUMERAL_MONEDA))
                        $("#verMontoComprobado").val(numeral(informacion.COMPROBACION_MONTO).format(NUMERAL_MONEDA))
                        
                        const diferencia = numeral(informacion.COMPROBACION_MONTO)
                        .subtract(informacion.ENTREGA_MONTO || 0)
                        
                        $("#verMontoDiferencia").val(diferencia.format(NUMERAL_MONEDA))
                        if (diferencia.value() < 0) {
                            $("#verTipoDiferencia").val("Cobro de saldo en contra")
                            $("#verMontoDiferencia").removeClass("text-success").addClass("text-danger")
                            $("#ajustar").text("Cobrar")
                        } else {
                            $("#verTipoDiferencia").val("Pago de saldo a favor")
                            $("#verMontoDiferencia").removeClass("text-danger").addClass("text-success")
                            $("#ajustar").text("Pagar")
                        }
                        
                        $("#modalVerAjuste").modal("show")
                    })
                }

                const registrarAjuste = () => {
                    const solicitudId = $("#verSolicitudId").val()
                    const monto = Math.abs(numeral($("#verMontoDiferencia").val()).value())
                    const tipo = $("#verTipoDiferencia").val()

                    confirmarMovimiento("¿Desea registrar el " + tipo.toLowerCase() + " por un monto de " + numeral(monto).format(NUMERAL_MONEDA) + "?")
                    .then((continuar) => {
                        if (!continuar.isConfirmed) return

                        const parametros = {
                            usuario: $_SESSION[usuario_id],
                            solicitudId,
                            observaciones: $("#observacionesAjuste").val().trim(),
                            sucursal: $("#verSucursal").val(),
                        }

                        consultaServidor("/viaticos/registraAjuste_VG", parametros, (respuesta) => {
                            if (!respuesta.success) return showError(respuesta.mensaje)

                            showSuccess("Ajuste registrado correctamente.").then(() => {
                                $("#modalVerAjuste").modal("hide")
                                const formData = new FormData()
                                formData.append("solicitudId", solicitudId)
                                mostrarArchivoDescargado(
                                    "/viaticos/getComprobanteAjuste",
                                    formData,
                                    {
                                        titulo: "Comprobante de ajuste",
                                        fncClose: getSolicitudes
                                    }
                                )
                            })
                        })
                    })
                }
                
                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango: true, iniD: -30 })
                    $("#btnBuscarSolicitudes").on("click", getSolicitudes)
                    $("#ajustar").on("click", registrarAjuste)
                    configuraTabla(tabla)
                    getSolicitudes()
                })
            </script>
        HTML;

        $catSucursales = ViaticosDAO::getCatalogoSucursales();
        $sucursales = '';
        if ($catSucursales['success']) {
            foreach ($catSucursales['datos'] as $sucursal) {
                $seleccion = $_SESSION['sucursal_id'] == $sucursal['ID'] ? 'selected' : '';
                $sucursales .= "<option value='{$sucursal['ID']}' $seleccion>{$sucursal['NOMBRE']}</option>";
            }
        }

        self::set("titulo", "Ajustes de viáticos");
        self::set("sucursales", $sucursales);
        self::set("script", $script);
        self::render("viaticos_ajustes");
    }

    public function getSolicitudesAjustes()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesAjustes_VG($_POST));
    }

    public function registraAjuste_VG()
    {
        self::respuestaJSON(ViaticosDAO::registraAjuste_VG($_POST));
    }

    public function getComprobanteAjuste()
    {
        $datos = $_SERVER['REQUEST_METHOD'] !== 'POST' ? $_GET : $_POST;
        $datos = ViaticosDAO::getDatosComprobanteAjuste($datos);
        if (!$datos['success']) return self::respuestaJSON($datos);

        $plantilla = self::getPlantillaAjuste_VG($datos['datos']);

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

        $duplicado = <<<HTML
            <div style="border-top: 1px dashed #000; margin-top: 10px;">
                {$plantilla['cuerpo']}
            </div>
        HTML;
        $mpdf->WriteHTML($duplicado);

        $mpdf->Output('comprobante_entrega.pdf', 'I');
        exit;
    }

    private function getPlantillaAjuste_VG($datos)
    {
        $diferencia = abs($datos['DIFERENCIA_MONTO'] ?? 0);
        $tipo = $datos['DIFERENCIA_MONTO'] < 0 ? "COBRO DE SALDO EN CONTRA" : "PAGO DE SALDO A FAVOR";
        $entero = floor($diferencia);
        $centavos = round(($diferencia - $entero) * 100);
        $fmt = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
        $texto_entero = $fmt->format($entero);
        $texto_centavos = str_pad($centavos, 2, '0', STR_PAD_LEFT);
        $diferencia_letra = mb_strtoupper("$texto_entero pesos $texto_centavos/100 M.N.", 'UTF-8');

        $fmt = new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY);
        $diferencia = $fmt->formatCurrency($diferencia, 'MXN');
        $entregado = $fmt->formatCurrency($datos['ENTREGA_MONTO'], 'MXN');
        $comprobado = $fmt->formatCurrency($datos['COMPROBACION_MONTO'], 'MXN');

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
                <p>Por medio del presente documento se hace constar el <strong>$tipo</strong> al epleado <strong>[EMPLEADO]</strong>
                por concepto de una diferencia entre el monto de gastos entregados ($entregado) contra lo comprobado ($comprobado), correspondiente al proyecto <strong>[PROYECTO]</strong>.</p>
            </div>

            <!-- Monto -->
            <div class="amount-section">
                <div class="amount-box">
                    <div class="amount-label">MONTO</div>
                    <div class="amount-value">$diferencia</div>
                    <div class="amount-text">($diferencia_letra)</div>
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
}
