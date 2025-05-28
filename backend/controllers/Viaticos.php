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

                const getParametros = () => {
                    const tipo = $("#tipoSolicitud").val()
                    const proyecto = $("#proyecto").val()
                    const fechaI = $("#fechaI").val()
                    const fechaF = $("#fechaF").val()
                    const monto = numeral($("#montoVG").val()).value()

                    if (!proyecto) {
                        showError("El campo proyecto es obligatorio")
                        return false
                    }
                    if (parseFloat(monto) < 1) {
                        showError("Debe ingresar un monto mayor a 0")
                        return false
                    }

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

                            $("#tipoSolicitud").val("1").change()
                            $("#proyecto").val("")
                            $("#fechaI_Solicitud").val(hoy)
                            $("#fechaF_Solicitud").val(hoy)
                            $("#montoVG").val("")
                            $("#modalNuevaSolicitud").modal("hide")
                            getSolicitudes()

                            if (datos.tipo === "2") {
                                const formData = new FormData()
                                formData.append("usuario", datos.usuario)
                                formData.append("tipo", datos.tipo)
                                formData.append("proyecto", datos.proyecto)
                                formData.append("fechaI", datos.fechaI)
                                formData.append("fechaF", datos.fechaF)
                                formData.append("monto", datos.monto)

                                comprobantesGastos.forEach((comprobante, index) => {
                                    formData.append(comprobante[index], comprobante)
                                })

                                consultaServidor("/viaticos/registraComprobaciones", formData, (respuesta) => {
                                    if (!respuesta.success) return showError(respuesta.mensaje)
                                    
                                    $("#tablaComprobantes tbody").empty()
                                    comprobantesGastos.length = 0
                                })
                            }
                        }

                        btn.attr("disabled", false)
                    })                        
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
                        $("#tbodyComprobantes").empty()
                        comprobantesGastos.length = 0
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

                    configuraTabla(tabla)
                    configuraModales()

                    $("#btnBuscarSolicitudes").click(getSolicitudes)
                    $("#tipoSolicitud").change(changeTipoSolicitud)
                    $("#agregarComprobante").click(clickAgregarComprobante)
                    $("#registraSolicitud").click(registraSolicitud)
                    
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
