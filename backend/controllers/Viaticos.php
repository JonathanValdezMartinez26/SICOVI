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

                const validacionSolicitud = () => {
                    const modal = $("#modalNuevaSolicitud").find(".modal-body")[0]
                    const validacion = FormValidation.formValidation(modal, {
                        fields: {
                            montoVG: {
                                validators: {
                                    notEmpty: {
                                        message: "Debe ingresar un monto"
                                    },
                                    greaterThan: {
                                        min: 1,
                                        message: "El monto debe ser mayor a 0"
                                    }
                                }
                            },
                            proyecto: {
                                validators: {
                                    notEmpty: {
                                        message: "Debe ingresar el nombre del proyecto"
                                    }
                                }
                            }
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger(),
                            autoFocus: new FormValidation.plugins.AutoFocus(),
                            bootstrap5: new FormValidation.plugins.Bootstrap5({
                                defaultMessageContainer: false,
                            }),
                            message: new FormValidation.plugins.Message({
                                container: function (field, element) {
                                    return $(element).closest(".form-group").find(".fv-message")[0];
                                }
                            })
                        }
                    })

                    $("#registraSolicitud").on("click", (e) => {
                        validacion.validate().then((validacion) => {
                            if (validacion === "Valid") registraSolicitud()
                            else showError("Debe corregir los errores marcados antes de continuar.")
                        })
                    })

                    $("#cancelaSolicitud").on("click", () => {
                        validacion.resetForm(true)
                        limpiaComprobantes()
                    })
                }

                const getParametros = () => {
                    const fechas = getRangoFechas("#fechasNuevaSolicitud")

                    const tipo = $("#tipoSolicitud").val()
                    const proyecto = $("#proyecto").val()
                    const fechaI = fechas.inicio
                    const fechaF = fechas.fin
                    const monto = numeral($("#montoVG").val()).value()

                    return {
                        usuario: $_SESSION[usuario_id],
                        tipo,
                        proyecto,
                        fechaI,
                        fechaF,
                        monto
                    }
                }

                const getSolicitudes = () => {
                    const fechas = getRangoFechas("#fechasSolicitudes")

                    const parametros = {
                        usuario: $_SESSION[usuario_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesUsuario", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const estatus = {
                            "1": "warning",
                            "2": "success",
                            "3": "danger",
                            "4": "secondary"
                        }

                        const datos = respuesta.datos.map(solicitud => {
                            return [
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.PROYECTO,
                                moment(solicitud.REGISTRO).format(MOMENT_FRONT),
                                numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
                                "<span class='badge rounded-pill bg-label-" + estatus[solicitud.ESTATUS_ID] + "'>" + solicitud.ESTATUS + "</span>",
                                "<button type='button' class='btn btn-primary btn-sm' onclick='verDetalles(" + solicitud.ID + ")'><i class='fa-solid fa-eye'>&nbsp;</i>Ver Detalles</button>"
                            ]

                        });

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                const registraSolicitud = () => {
                    const datos = getParametros()
                    if (!datos) return

                    const btn = $("#registraSolicitud")
                    btn.attr("disabled", true)

                    consultaServidor("/viaticos/registraSolicitudViaticos", datos, (respuesta) => {
                        if (!respuesta.success) showError(respuesta.mensaje)
                        else {
                            const hoy = moment().format(MOMENT_BACK)

                            if (datos.tipo === "1") {
                                $("#modalNuevaSolicitud").modal("hide")
                                showSuccess("Solicitud registrada correctamente").then(() => {
                                    $("#tipoSolicitud").val("1").change()
                                    $("#proyecto").val("")
                                    $("#fechaI_Solicitud").val(hoy)
                                    $("#fechaF_Solicitud").val(hoy)
                                    $("#montoVG").val("")
                                    $("#modalNuevaSolicitud").modal("hide")
                                    getSolicitudes()
                                }) 
                            } else {
                                const formData = new FormData()
                                const tblComprobantes = $("#tablaComprobantes tbody tr").toArray()

                                tblComprobantes.forEach((comprobante) => {
                                    const columnas = $(comprobante).find("td")
                                    const monto = numeral(columnas[1].innerText).value()

                                    formData.append("viaticos[]", respuesta.datos.id)
                                    formData.append("fecha[]", hoy)
                                    formData.append("concepto[]", 'in progress')
                                    formData.append("observaciones[]", columnas[2].innerText)
                                    formData.append("subtotal[]", monto)
                                    formData.append("total[]", monto)
                                })

                                consultaServidor("/viaticos/registraComprobaciones", formData, (respuesta) => {
                                    if (!respuesta.success) return showError(respuesta.mensaje)
                                    // limpiaComprobantes()
                                }, {
                                    processData: false,
                                    contentType: false
                                });
                            }
                        }

                        btn.attr("disabled", false)
                    })                        
                }

                const limpiaComprobantes = () => {
                    $("#tbodyComprobantes").empty()
                    comprobantesGastos.length = 0
                }

                const configuraModales = () => {
                    $("#btnAgregarComprobante").click(() => {
                        $("#modalNuevaSolicitud").modal("hide")
                        $("#modalAgregarComprobante").modal("show")
                    })

                    $("#modalAgregarComprobante").on("shown.bs.modal", () => {
                        $("#comprobante").focus()
                    })

                    $("#modalAgregarComprobante").on("hidden.bs.modal", () => {
                        $("#comprobante").val("")
                        $("#montoComprobante").val("")
                        $("#observacionesComprobante").val("")
                        $("#modalNuevaSolicitud").modal("show")
                    })
                }

                const changeTipoSolicitud = () => {
                    const tipo = $("#tipoSolicitud").val()
                    if (tipo === "1") {
                        $("#lblMontoVG").text("Monto Solicitado")
                        $("#montoVG").val("")
                        $("#montoVG").prop("disabled", false)
                        limpiaComprobantes()
                        $("#comprobantesGastos").hide()
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#montoVG").val("0.00")
                        $("#montoVG").prop("disabled", true)
                        $("#comprobantesGastos").show()
                    }
                }

                const clickAgregarComprobante = () => {
                    const comprobante = $("#comprobante")[0].files[0]
                    const montoComprobante = numeral($("#montoComprobante").val()).value()
                    const observaciones = $("#observacionesComprobante").val()
                    
                    if (!comprobante) return showError("El campo comprobante es obligatorio")
                    if (montoComprobante < 1) return showError("Debe ingresar un monto mayor a 0")
                    
                    comprobantesGastos.push(comprobante)
                    $("#montoVG").val(numeral($("#montoVG").val()).add(montoComprobante).value())

                    const fila = $("<tr></tr>")
                    fila.append("<td>" + comprobante.name + "</td>")
                    fila.append("<td>" + numeral(montoComprobante).format(NUMERAL_MONEDA) + "</td>")
                    fila.append("<td>" + observaciones + "</td>")
                    fila.append('<td><button type="button" class="btn btn-danger btn-sm" onclick="clickEliminaComprobante(this)"><i class="fa-solid fa-trash">&nbsp;</i>Eliminar</button></td>')
                    
                    $("#tablaComprobantes tbody").append(fila)
                    $("#modalAgregarComprobante").modal("hide")
                }

                const clickEliminaComprobante = (btn) => {
                    const fila = $(btn).closest("tr")
                    const archivo = fila.find("td").eq(0).text().trim()
                    const monto = numeral(fila.find("td").eq(1).text().trim()).value()
                    fila.remove()

                    comprobantesGastos.splice(comprobantesGastos.findIndex(c => c.name === archivo), 1)
                    $("#montoVG").val(numeral($("#montoVG").val()).subtract(monto).value())
                }

                $(document).ready(() => {
                    setRangoFechas("#fechasSolicitudes", {diasAntes: 30})
                    setRangoFechas("#fechasNuevaSolicitud", {max: 30, enModal: true})
                    setInputMoneda("#montoVG, #montoComprobante")
                    
                    validacionSolicitud()
                    configuraTabla(tabla)
                    configuraModales()

                    $("#btnBuscarSolicitudes").click(getSolicitudes)
                    $("#tipoSolicitud").change(changeTipoSolicitud)
                    $("#agregarComprobante").click(clickAgregarComprobante)
                    
                    getSolicitudes()
                });
            </script>
        HTML;

        self::set("titulo", "Solicitud de Viáticos");
        self::set("script", $script);
        self::render("viaticos_solicitud");
    }

    public function getSolicitudesUsuario()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesUsuario_VG($_POST));
    }

    public function registraSolicitudViaticos()
    {
        self::respuestaJSON(ViaticosDAO::registraSolicitud_VG($_POST));
    }

    public function registraComprobacionesGastos()
    {
        if (!isset($_FILES['comprobante']) || count($_FILES['comprobante']['name']) < 1) {
            self::respuestaJSON(self::respuesta(false, 'Debe agregar al menos un comprobante de gastos.'));
            return;
        }

        $datos = $_POST;
        $datos['comprobantes'] = $_FILES['comprobante'];

        self::respuestaJSON(ViaticosDAO::registraComprobaciones($datos));
    }
}
