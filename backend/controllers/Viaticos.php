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

                const getParametros = () => {
                    const proyecto = $("#proyecto").val()
                    const fechaI = $("#fechaI").val()
                    const fechaF = $("#fechaF").val()
                    const monto = $("#monto").val()

                    if (!proyecto) {
                        showError("El campo proyecto es obligatorio")
                        return false
                    }
                    if (parseFloat(monto) < 1) {
                        showError("Debe ingresar un monto mayor a 0")
                        return false
                    }

                    return {
                        usuario: "$_SESSION[usuario]",
                        proyecto,
                        fechaI,
                        fechaF,
                        monto
                    }
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
                        $("#comprobantesGastos").hide()
                        $("#modalAgregarComprobante").reset()
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#montoVG").val("0.00")
                        $("#montoVG").prop("disabled", true)
                        $("#comprobantesGastos").show()
                    }
                }

                const clickAgregarComprobante = () => {
                    let comprobante = $("#comprobante").val()
                    const montoComprobante = getInputMonto("#montoComprobante")
                    const observaciones = $("#observacionesComprobante").val()

                    if (!comprobante) return showError("El campo comprobante es obligatorio")
                    if (montoComprobante < 1) return showError("Debe ingresar un monto mayor a 0")
                    comprobante = com
                    //commit test
                    $("#montoVG").val((getInputMonto("#montoVG") + montoComprobante))
                    const fila = $("#tablaComprobantes tbody tr").first().clone()
                    fila.find("td").eq(0).text("")
                    fila.find("td").eq(1).text(formatoMoneda(montoComprobante))
                    fila.find("td").eq(2).text(observaciones)
                    fila.find("td").eq(3).html('<button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest(\'tr\').remove();"><i class="fa-solid fa-trash"></i></button>')
                    $("#tablaComprobantes tbody").prepend(fila)
                    $("#modalAgregarComprobante").modal("hide")
                }

                $(document).ready(() => {
                    configuraModales()
                    configuraTabla(tabla)
                    configuraTabla("#tablaComprobantes", {
                        regXvista: false,
                        buscar: false,
                        footerInfo: false,
                        paginacion: false,
                        ordenar: false
                    })

                    setValidacionRangoFechas("#fechaI", "#fechaF")
                    setInputMonto("#montoVG, #montoComprobante")

                    $("#tipoSolicitud").change(changeTipoSolicitud)

                    $("#agregarComprobante").click(clickAgregarComprobante);

                    $("#registraSolicitud").click(() => {
                        const btn = $("#registraSolicitud")
                        btn.attr("disabled", true)
                        const datos = getParametros()
                        if (!datos) {
                            btn.attr("disabled", false)
                            return
                        }

                        consultaServidor("/viaticos/solicitud", datos, (respuesta) => {
                            if (!respuesta.success) showError(respuesta.mensaje)
                            else {
                                actualizaDatosTabla(tabla, respuesta.datos)
                                $("#modalSolicitud").modal("hide")
                            }

                            btn.attr("disabled", false)
                        })
                    })
                });
            </script>
        HTML;

        self::set("titulo", "Solicitud de Viáticos");
        self::set("script", $script);
        self::render("viaticos_solicitud");
    }

    public function registraSolicitudViaticos()
    {
        $r = ViaticosDAO::registraSolicitudViaticos($_POST);
        return self::respuestaJSON($r);
    }
}
