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
                                        message: "Debe ser mayor a 0"
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

                const validacionComprobante = () => {
                    const modal = $("#modalAgregarComprobante").find(".modal-body")[0]
                    const validacion = FormValidation.formValidation(modal, {
                        fields: {
                            comprobante: {
                                validators: {
                                    notEmpty: {
                                        message: "Debe seleccionar un comprobante o tomar una foto"
                                    },
                                    file: {
                                        maxSize: 5 * 1024 * 1024, // 5 MB
                                        message: "El archivo no debe exceder 5MB"
                                    }
                                }
                            },
                            montoComprobante: {
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
                            conceptoComprobante: {
                                validators: {
                                    callback: {
                                        message: "Debe seleccionar un concepto",
                                        callback: (input) => {
                                            const concepto = $(modal).find("#conceptoComprobante").select2("val")
                                            return concepto === null || concepto === "" ? false : true
                                        }
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
                                container: (field, element) => {
                                    return $(element).closest(".form-group").find(".fv-message")[0];
                                }
                            })
                        }
                    })

                    $("#agregarComprobante").on("click", (e) => {
                        validacion.validate().then((validacion) => {
                            if (validacion === "Valid") clickAgregarComprobante()
                            else showError("Debe corregir los errores marcados antes de continuar.")
                        })
                    })

                    $("#cancelarComprobante").on("click", () => {
                        validacion.resetForm(true)
                    })
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
                    $("#registraSolicitud").attr("disabled", true)

                    if (datos.tipo === "1") {
                        registraViaticos(datos)
                    } else {
                        registraGastos(datos)
                    }
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

                const clickAgregarComprobante = () => {
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
                    $("#montoVG").val(numeral($("#montoVG").val()).add(montoComprobante).value())

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
                    const monto = numeral(fila.find("td").eq(1).text().trim()).value()
                    fila.remove()

                    comprobantesGastos.splice(comprobantesGastos.findIndex(c => c.name === archivo), 1)
                    $("#montoVG").val(numeral($("#montoVG").val()).subtract(monto).value())
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

                $(document).ready(() => {
                    setInputFechas("#fechasSolicitudes", { rango:true, diasAntes: 30 })
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
}
