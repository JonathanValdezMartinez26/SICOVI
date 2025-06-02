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
                            const editar = solicitud.ESTATUS_ID == 1 ? "<a class='dropdown-item' href='javascript:;' onclick='editarSolicitud(" + solicitud.ID + ")'><i class='fa-solid fa-pen-to-square'>&nbsp;</i>Editar</a>" : ""
                            const cancelar = solicitud.ESTATUS_ID == 1 || (solicitud.TIPO_ID == 2 && solicitud.ESTATUS_ID != 5) ? "<a class='dropdown-item text-danger delete-record' href='javascript:;' onclick='cancelarSolicitud(" + solicitud.ID + ")'><i class='fa-solid fa-trash'>&nbsp;</i>Cancelar</a>" : ""
                            const acciones = "<button type='button' class='btn dropdown-toggle hide-arrow' data-bs-toggle='dropdown' aria-expanded='false'><i class='fa fa-ellipsis-vertical'></i></button>" +
                                            "<div class='dropdown-menu'>" +
                                            "<a class='dropdown-item' href='javascript:;' onclick='verSolicitud(" + solicitud.ID + ")'><i class='fa-solid fa-eye'>&nbsp;</i>Ver detalles</a>" +
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

                const registraSolicitud = () => {
                    confirmarMovimiento("¿Está seguro de registrar la solicitud?").then((continuar) => {
                        if (continuar.isConfirmed) {
                            const datos = getParametros()
                            if (!datos) return
                            $("#registraSolicitud").attr("disabled", true)
        
                            if (datos.tipo === "1") {
                                registraViaticos(datos)
                            } else {
                                registraGastos(datos)
                            }
                        } else {
                            $("#registraSolicitud").attr("disabled", false)
                        }
                    })
                }

                const registraViaticos = (datos) => {
                    consultaServidor("/viaticos/registraSolicitudViaticos", datos, (respuesta) => {
                        $("#registraSolicitud").attr("disabled", false)
                        if (!respuesta.success) showError(respuesta.mensaje)
                        else {
                            const hoy = moment().format(MOMENT_BACK)
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
                        }
                    })  
                }

                const registraGastos = (datos) => {
                    const formData = new FormData()
                    formData.append("usuario", datos.usuario)
                    formData.append("tipo", datos.tipo)
                    formData.append("proyecto", datos.proyecto)
                    formData.append("fechaI", datos.fechaI)
                    formData.append("fechaF", datos.fechaF)
                    formData.append("monto", datos.monto)
                    
                    comprobantesGastos.forEach((comprobante) => {
                        formData.append("comprobante[]", comprobante.comprobante)
                        formData.append("conceptoComprobante[]", comprobante.concepto)
                        formData.append("fechaComprobante[]", comprobante.fecha)
                        formData.append("montoComprobante[]", comprobante.monto)
                        formData.append("observacionesComprobante[]", comprobante.observaciones)
                    })

                    consultaServidor("/viaticos/registraSolicitudGastos", formData, (respuesta) => {
                        $("#registraSolicitud").attr("disabled", false)
                        if (!respuesta.success) return showError(respuesta.mensaje)

                        const hoy = moment().format(MOMENT_BACK)
                        $("#modalNuevaSolicitud").modal("hide")
                        showSuccess("Solicitud registrada correctamente").then(() => {
                            $("#tipoSolicitud").val("1").change()
                            $("#proyecto").val("")
                            $("#fechaI_Solicitud").val(hoy)
                            $("#fechaF_Solicitud").val(hoy)
                            $("#montoVG").val("")
                            $("#modalNuevaSolicitud").modal("hide")
                            limpiaComprobantes()
                            getSolicitudes()
                        })
                    }, {
                        procesar: false,
                        tipoContenido: false
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
                        setInputFechas("#fechasNuevaSolicitud", { rango:true, max: 30, enModal: true })
                    } else {
                        $("#lblMontoVG").text("Monto Comprobado")
                        $("#montoVG").val("0.00")
                        $("#montoVG").prop("disabled", true)
                        $("#conceptoComprobante").val(null).trigger('change');
                        $("#comprobantesGastos").show()
                        setInputFechas("#fechasNuevaSolicitud", { rango:true, min: 30, enModal: true })
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
                    fila.append('<td><button type="button" class="btn btn-danger btn-sm" onclick="clickEliminaComprobante(this)"><i class="fa-solid fa-trash">&nbsp;</i>Eliminar</button></td>')
                    
                    $("#tablaComprobantes tbody").append(fila)
                    $("#modalAgregarComprobante").modal("hide")
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
                    $("#modalVerSolicitud").modal("show");
                    // consultaServidor("/viaticos/getResumenSolicitud", { solicitudId }, (respuesta) => {
                    //     if (!respuesta.success) return showError(respuesta.mensaje);
                    //     const resumen = respuesta
                    // })
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
                    setInputFechas("#fechasSolicitudes", { rango:true, inicio: -30 })
                    setInputFechas("#fechasNuevaSolicitud", { rango:true, min: 0, max: 30, enModal: true })
                    setInputFechas("#fechaComprobante", { min: 30, max: 0, enModal: true })
                    setInputMoneda("#montoVG, #montoComprobante")
                    
                    configuraTabla(tabla)
                    configuraModales()
                    validacionSolicitud()
                    validacionComprobante()

                    $("#btnBuscarSolicitudes").click(getSolicitudes)
                    $("#tipoSolicitud").change(changeTipoSolicitud)
                    $("#conceptoComprobante").select2({
                        dropdownParent: $('#modalAgregarComprobante'),
                        placeholder: "Seleccione un concepto",
                    })
                    $("#conceptoComprobante").change(() => {
                        const concepto = $("#conceptoComprobante").find("option:selected")
                        $("#descripcionComprobante").text(concepto.attr("lbl-desc") || "")
                    })

                    $("#btnTomarFoto").click(() => {
                        $("#modalTomarFoto").modal("show");
                    })

                    $("#modalTomarFoto").on("shown.bs.modal", iniciarCamara)
                    $("#modalTomarFoto").on("hidden.bs.modal", detenerCamara)
                    $("#btnCapturarFoto").click(tomarFoto)
                    
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

    public function registraSolicitudViaticos()
    {
        self::respuestaJSON(ViaticosDAO::registraSolicitud_V($_POST));
    }

    public function registraSolicitudGastos()
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

        $resultado = ViaticosDAO::registraSolicitud_G($_POST, $guardar);

        foreach ($guardar as $comprobante) {
            if (is_resource($comprobante['comprobante'])) {
                fclose($comprobante['comprobante']);
            }
        }

        self::respuestaJSON($resultado);
    }

    public function getResumenSolicitud()
    {
        // Implementar lógica para obtener el resumen de la solicitud
        // Aquí se puede consultar la base de datos y devolver los datos necesarios
        self::respuestaJSON(ViaticosDAO::getResumenSolicitud_VG($_POST));
    }

    public function eliminarSolicitud()
    {
        // Implementar lógica para eliminar una solicitud
        // Aquí se puede consultar la base de datos y realizar la eliminación
        self::respuestaJSON(ViaticosDAO::eliminaSolicitud_VG($_POST));
    }
}
