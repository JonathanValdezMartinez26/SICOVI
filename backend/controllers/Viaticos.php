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
                let valSolicitud = null, valComprobante = null

                const getSolicitudes = () => {
                    const fechas = getInputFechas("#fechasSolicitudes", true)

                    const parametros = {
                        usuario: $_SESSION[usuario_id],
                        fechaI: fechas.inicio,
                        fechaF: fechas.fin
                    }

                    consultaServidor("/viaticos/getSolicitudesUsuario", parametros, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)                        
                        const resumen = {}
                        const datos = respuesta.datos.map(solicitud => {
                            const e = "<a class='dropdown-item' href='javascript:;' onclick='editarSolicitud(" + solicitud.ID + ")'><i class='fa fa-pen-to-square'>&nbsp;</i>Editar</a>"
                            const c = "<a class='dropdown-item text-danger delete-record' href='javascript:;' onclick='cancelarSolicitud(" + solicitud.ID + ")'><i class='fa fa-trash'>&nbsp;</i>Cancelar</a>"
                            let editar = "", cancelar = ""

                            if (solicitud.TIPO_ID == 1) {
                                editar = solicitud.ESTATUS_ID == 1 ? e : ""
                                cancelar = solicitud.ESTATUS_ID == 1 || solicitud.ESTATUS_ID == 2 ? c : ""
                            }

                            if (solicitud.TIPO_ID == 2) {
                                editar = solicitud.ESTATUS_ID == 4 ? e : ""
                                cancelar = (solicitud.ESTATUS_ID == 4 || solicitud.ESTATUS_ID == 2) ? c : ""
                            }

                            const acciones = "<button type='button' class='btn dropdown-toggle hide-arrow' data-bs-toggle='dropdown' aria-expanded='false'><i class='fa fa-ellipsis-vertical'></i></button>" +
                                            "<div class='dropdown-menu'>" +
                                            "<a class='dropdown-item' href='javascript:;' onclick='verSolicitud(" + solicitud.ID + ")'><i class='fa fa-eye'>&nbsp;</i>Detalles</a>" +
                                            (editar !== "" || cancelar !== "" ? "<hr class='dropdown-divider' />" : "") +
                                            editar +
                                            cancelar +
                                            "</div>"

                            const estatusBadge = "<span class='badge rounded-pill " + solicitud.ESTATUS_COLOR + "'>" + solicitud.ESTATUS_NOMBRE + "</span>"
                            if (!resumen[solicitud.ESTATUS_ID]) {
                                resumen[solicitud.ESTATUS_ID] = {}
                                resumen[solicitud.ESTATUS_ID].total = 1
                                resumen[solicitud.ESTATUS_ID].color = solicitud.ESTATUS_COLOR
                                resumen[solicitud.ESTATUS_ID].estatus = solicitud.ESTATUS_NOMBRE
                            }
                            else resumen[solicitud.ESTATUS_ID].total += 1

                            return [
                                null,
                                solicitud.ID,
                                solicitud.TIPO_NOMBRE,
                                solicitud.PROYECTO,
                                moment(solicitud.REGISTRO).format(MOMENT_FRONT),
                                numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
                                estatusBadge,
                                acciones
                            ]
                        });

                        actualizaDatosTabla(tabla, datos)
                        $("#resumenSolicitudes").empty()
                        if (datos.length === 0) {
                            $("#resumenSolicitudes").append(getTarjetaSolicitud("text-bg-dark", "Sin solicitudes", 0))
                        } else {
                            Object.keys(resumen).sort((a, b) => {
                                return a - b;
                            }).forEach(estatusId => {
                                const tarjeta = getTarjetaSolicitud(resumen[estatusId].color, resumen[estatusId].estatus, resumen[estatusId].total)
                                $("#resumenSolicitudes").append(tarjeta)
                            })
                        }
                    })
                }

                const getTarjetaSolicitud = (color, titulo, total) => {
                    return "<div class='col-2'>" +
                                    "<div class='card'>" +
                                    "<div class='card-body'>" +
                                    "<div class='card-info text-center'>" +
                                    "<div class='d-flex align-items-center justify-content-between'>" +
                                    "<span>Estatus:&nbsp;</span>" +
                                    "<span class='badge rounded-pill " + color + "'>" + titulo + "</span>" +
                                    "</div>" +
                                    "<h4 class='card-title mb-0 me-2'>" + total + "</h4>" +
                                    "</div>" +
                                    "</div>" +
                                    "</div>" +
                                    "</div>"
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

                    valSolicitud = setValidacionModal("#modalNuevaSolicitud", campos, "#registraSolicitud", registraSolicitud, "#cancelaSolicitud", {
                        accionCancel: limpiaComprobantes
                    })
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
                            },
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

                    valComprobante = setValidacionModal("#modalAgregarComprobante", campos, "#agregarComprobante", agregarComprobante,"#cancelaComprobante")
                }

                const getParametros = () => {
                    const fechas = getInputFechas("#fechasNuevaSolicitud", true)

                    const tipo = $("#tipoSolicitud").val()
                    const proyecto = $("#proyecto").val()
                    const fechaI = fechas.inicio
                    const fechaF = fechas.fin
                    const monto = numeral($("#montoVG").val()).value()
                    const limite = tipo === "1" ? moment(fechaF, MOMENT_BACK).add(3, 'days').format(MOMENT_BACK) : moment().format(MOMENT_BACK)
                            
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
                    confirmarMovimiento("¿Está seguro de registrar la solicitud?").then((continuar) => {
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
                            
                            comprobantesGastos.forEach((comprobante) => {
                                formData.append("comprobante[]", comprobante.comprobante)
                                formData.append("conceptoComprobante[]", comprobante.concepto)
                                formData.append("fechaComprobante[]", comprobante.fecha)
                                formData.append("montoComprobante[]", comprobante.monto)
                                formData.append("observacionesComprobante[]", comprobante.observaciones)
                            })

                            consultaServidor("/viaticos/registraSolicitud_VG", formData, (respuesta) => {
                                $("#registraSolicitud").attr("disabled", false)
                                if (!respuesta.success) return showError(respuesta.mensaje)

                                showSuccess("Solicitud registrada correctamente").then(() => {
                                    const hoy = moment().format(MOMENT_BACK)
                                    $("#modalNuevaSolicitud").modal("hide")
                                    resetValidacion(valSolicitud, true)
                                    resetValidacion(valComprobante, true)
                                    limpiaComprobantes()
                                    getSolicitudes()
                                })
                            }, {
                                procesar: false,
                                tipoContenido: false
                            })
                        } else {
                            $("#registraSolicitud").attr("disabled", false)
                        }
                    })
                }

                const limpiaComprobantes = () => {
                    $("#tbodyComprobantes").empty()
                    comprobantesGastos.length = 0
                }

                const configuraModales = () => {
                    $("#btnAgregarComprobanteGastos").click(() => {
                        $("#modalNuevaSolicitud").modal("hide")
                        const fechas = getInputFechas("#fechasNuevaSolicitud", true, false)
                        updateInputFechas("#fechaComprobante", { iniF: fechas.inicio, minF: fechas.inicio, maxF: fechas.fin })
                        $("#modalAgregarComprobante").modal("show")
                    })

                    $("#modalAgregarComprobante").on("shown.bs.modal", () => {
                        $("#comprobante").focus()
                    })

                    $("#modalAgregarComprobante").on("hidden.bs.modal", () => {
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
                        updateInputFechas("#fechasNuevaSolicitud", { minD: 0, maxD: 30 })
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#montoVG").val("0.00")
                        $("#montoVG").prop("disabled", true)
                        $("#conceptoComprobante").val(null).trigger('change');
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
                                        
                    comprobantesGastos.push({
                        comprobante,
                        concepto: concepto_id,
                        fecha: fechaComprobante,
                        monto: montoComprobante,
                        observaciones
                    })
                    $("#montoVG").val(numeral($("#montoVG").val()).add(montoComprobante).format(NUMERAL_DECIMAL))

                    const fila = $("<tr></tr>")
                    fila.append("<td>" + concepto + "</td>")
                    fila.append("<td>" + moment(fechaComprobante).format(MOMENT_FRONT) + "</td>")
                    fila.append("<td>" + numeral(montoComprobante).format(NUMERAL_MONEDA) + "</td>")
                    fila.append('<td><button type="button" class="btn btn-danger btn-sm" onclick="clickEliminaComprobante(this)"><i class="fa fa-trash">&nbsp;</i>Eliminar</button></td>')
                    
                    $("#tablaComprobantes tbody").append(fila)
                    $("#modalAgregarComprobante").modal("hide")
                    resetValidacion(valComprobante, true)
                }

                const clickEliminaComprobante = (btn) => {
                    const fila = $(btn).closest("tr")
                    const archivo = fila.find("td").eq(0).text().trim()
                    const monto = numeral(fila.find("td").eq(2).text().trim()).value()
                    fila.remove()

                    comprobantesGastos.splice(comprobantesGastos.findIndex(c => c.name === archivo), 1)
                    $("#montoVG").val(numeral($("#montoVG").val()).subtract(monto).format(NUMERAL_DECIMAL))
                }

                const iniciarCamara = () => {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: true })
                            .then((stream) => {
                                const video = document.getElementById("videoComprobante");
                                video.srcObject = stream;
                                video.play();
                            })
                            .catch((error) => {
                                showError("Error al acceder a la cámara: " + error.message);
                            });
                    } else {
                        showError("La cámara no está disponible en este dispositivo.");
                    }
                }

                const detenerCamara = () => {
                    const video = document.getElementById("videoComprobante");
                    if (video.srcObject) {
                        const stream = video.srcObject;
                        const tracks = stream.getTracks();
                        tracks.forEach(track => track.stop());
                        video.srcObject = null;
                    }
                }

                const tomarFoto = () => {
                    const video = document.getElementById("videoComprobante");
                    const canvas = document.getElementById("canvasComprobante");
                    const context = canvas.getContext("2d");

                    if (video.srcObject) {
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const fotoData = canvas.toBlob((blob) => {
                            const tiempo = moment().format("YYYYMMDD_HHmmss");
                            const archivoFoto = new File([blob], "foto_comprobante_" + tiempo + ".jpg", { type: "image/jpeg" });
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(archivoFoto);
                            $("#comprobante")[0].files = dataTransfer.files;
                            $("#comprobante").trigger("change");
                        }, "image/jpeg");
                        $("#modalTomarFoto").modal("hide");
                    } else {
                        showError("No hay video disponible para tomar una foto.");
                    }
                }

                const verSolicitud = (solicitudId) => {
                    consultaServidor("/viaticos/getResumenSolicitud", { solicitudId }, (respuesta) => {
                        if (!respuesta.success) return showError(respuesta.mensaje)
                        const datos = respuesta.datos;
                        $("#verTipoSol").val(datos.TIPO_NOMBRE)
                        $("#verFechaReg").val(moment(datos.REGISTRO).format(MOMENT_FRONT_HORA))
                        $("#verMontoSol").val(numeral(datos.MONTO).format(NUMERAL_MONEDA))
                        $("#verProyecto").val(datos.PROYECTO)
                        $("#verFechaI").val(moment(datos.FECHA_I).format(MOMENT_FRONT))
                        $("#verFechaF").val(moment(datos.FECHA_F).format(MOMENT_FRONT))
                        $("#verEstatus").val(datos.ESTATUS_NOMBRE)

                        if (datos.AUTORIZACION_USUARIO) {
                            $(".verAutorizacionIcono.text-warning").addClass("d-none")
                            $(".verAutorizacionIcono.text-success").removeClass("d-none")
                            $("#verAutorizadoPor").val(datos.AUTORIZACION_NOMBRE)
                            $("#verFechaAutorizacion").val(moment(datos.AUTORIZACION_FECHA).format(MOMENT_FRONT_HORA))
                            $("#verMontoAutorizado").val(numeral(datos.COMPROBACION_MONTO).format(NUMERAL_MONEDA))
                        } else {
                            $(".verAutorizacionIcono.text-warning").removeClass("d-none")
                            $(".verAutorizacionIcono.text-success").addClass("d-none")
                            $("#verAutorizadoPor").val("Pendiente de autorización")
                            $("#verFechaAutorizacion").val("")
                            $("#verMontoAutorizado").val("")
                        }

                        if (datos.ENTREGA_USUARIO) {
                            $(".verEntregaIcono.text-warning").addClass("d-none")
                            $(".verEntregaIcono.text-success").removeClass("d-none")
                            $("#verEntregadoPor").val(datos.ENTREGA_NOMBRE)
                            $("#verMetodoEntrega").val(datos.METODO_ENTREGA)
                            $("#verFechaEntrega").val(moment(datos.ENTREGA_FECHA).format(MOMENT_FRONT_HORA))
                            $("#verMontoEntrega").val(numeral(datos.ENTREGA_MONTO).format(NUMERAL_MONEDA))
                        } else {
                            $(".verEntregaIcono.text-warning").removeClass("d-none")
                            $(".verEntregaIcono.text-success").addClass("d-none")
                            $("#verEntregadoPor").val("Pendiente de entrega")
                            $("#verMetodoEntrega").val("")
                            $("#verFechaEntrega").val("")
                            $("#verMontoEntrega").val("")
                        }

                        $("#verFechaLimite").val(moment(datos.FECHA_LIMITE).format(MOMENT_FRONT))
                        let tiempoRestante = iniciarContador("#verTiempoRestante", datos.FECHA_LIMITE);

                        if (tiempoRestante === 1) {
                            // Si el tiempo restante es menor a 1 día, mostrar contador de horas
                            const horasRestantes = moment(datos.FECHA_LIMITE, MOMENT_FRONT).diff(moment(), 'hours');
                            $("#verTiempoRestante").val(horasRestantes + " horas")
                            $("#verTiempoRestante").removeClass("text-success").addClass("text-danger")
                            $("#btnCapturaComprobanteViaticos").addClass("d-none")
                        } else {
                            $("#verTiempoRestante").val("0 días")
                            $("#verTiempoRestante").removeClass("text-danger").addClass("text-success")
                            $("#btnCapturaComprobanteViaticos").removeClass("d-none")
                        }

                        

                        $("#modalVerSolicitud").modal("show")
                    })
                }

                const iniciarContador = (selector, fechaObjetivo) => {
                    const destino = moment(fechaObjetivo);

                    const actualizarConteo = () => {
                        const ahora = moment();
                        const duracion = moment.duration(destino.diff(ahora));

                        if (duracion.asMilliseconds() <= 0) {
                            $(selector).val("00 días 00:00:00");
                            clearInterval(intervalo);
                            return;
                        }

                        const dias = Math.floor(duracion.asDays());
                        const horas = String(duracion.hours()).padStart(2, '0');
                        const minutos = String(duracion.minutes()).padStart(2, '0');
                        const segundos = String(duracion.seconds()).padStart(2, '0');

                        $(selector).val(dias + " días " + horas + ":" + minutos + ":" + segundos);
                    }

                    const intervalo = setInterval(actualizarConteo, 1000);
                    return intervalo
                }

                const cancelarSolicitud = (idSolicitud) => {
                    confirmarMovimiento("¿Desea eliminar la solicitud?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            consultaServidor("/viaticos/eliminarSolicitud", { idSolicitud }, (respuesta) => {
                                if (!respuesta.success) return showError(respuesta.mensaje);
                                showSuccess("Solicitud eliminada correctamente").then(() => {
                                    getSolicitudes();
                                });
                            });
                        }
                    });
                }

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango: true, iniD: -30 })
                    setInputFechas("#fechasNuevaSolicitud", { rango: true, minD: 0, maxD: 30, enModal: true })
                    setInputFechas("#fechaComprobante", { enModal: true })
                    setInputMoneda("#montoVG, #montoComprobante")
                    
                    configuraTabla(tabla)
                    configuraModales()
                    validacionSolicitud()
                    validacionComprobante()

                    $("#btnBuscarSolicitudes").on("click", getSolicitudes)
                    $("#tipoSolicitud").on("change", changeTipoSolicitud)
                    $("#conceptoComprobante").select2({
                        dropdownParent: $('#modalAgregarComprobante'),
                        placeholder: "Seleccione un concepto",
                    })
                    $("#conceptoComprobante").on("change", () => {
                        const concepto = $("#conceptoComprobante").find("option:selected")
                        $("#descripcionComprobante").text(concepto.attr("lbl-desc") || "")
                    })

                    $("#btnTomarFoto").on("click", () => {
                        $("#modalTomarFoto").modal("show");
                    })

                    $("#modalTomarFoto").on("shown.bs.modal", iniciarCamara)
                    $("#modalTomarFoto").on("hidden.bs.modal", detenerCamara)
                    $("#btnCapturarFoto").on("click", tomarFoto)
                    
                    getSolicitudes()
                });
            </script>
        HTML;

        $catConceptos = ViaticosDAO::getCatalogoConceptosViaticos();
        $conceptos = '<option></option>';
        if ($catConceptos['success']) {
            foreach ($catConceptos['datos'] as $concepto) {
                $conceptos .= "<option value='{$concepto['ID']}' lbl-desc='{$concepto['DESCRIPCION']}'>{$concepto['NOMBRE']}</option>";
            }
        }

        self::set("titulo", "Solicitud de Viáticos");
        self::set("script", $script);
        self::set("conceptos", $conceptos);
        self::render("viaticos_solicitud");
    }

    public function getSolicitudesUsuario()
    {
        self::respuestaJSON(ViaticosDAO::getSolicitudesUsuario_VG($_POST));
    }

    public function registraSolicitud_VG()
    {
        $errores = [];
        $guardar = [];

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
                $guardar[] = [
                    'comprobante' => fopen($archivo, 'rb'),
                    'concepto' => $_POST['conceptoComprobante'][$key] ?? null,
                    'fecha' => $_POST['fechaComprobante'][$key] ?? null,
                    'monto' => $_POST['montoComprobante'][$key] ?? null,
                    'observaciones' => $_POST['observacionesComprobante'][$key] ?? ''
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

        $resultado = ViaticosDAO::registraSolicitud_VG($_POST, $guardar);

        foreach ($guardar as $comprobante) {
            if (is_resource($comprobante['comprobante'])) {
                fclose($comprobante['comprobante']);
            }
        }

        self::respuestaJSON($resultado);
    }

    public function getResumenSolicitud()
    {
        self::respuestaJSON(ViaticosDAO::getResumenSolicitud_VG($_POST));
    }

    public function eliminarSolicitud()
    {
        self::respuestaJSON(ViaticosDAO::eliminaSolicitud_VG($_POST));
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
                                moment(solicitud.REGISTRO).format(MOMENT_FRONT),
                                numeral(solicitud.MONTO).format(NUMERAL_MONEDA),
                                acciones
                            ]
                        });

                        actualizaDatosTabla(tabla, datos)
                    })
                }

                

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango:true, inicio: -30 })
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
