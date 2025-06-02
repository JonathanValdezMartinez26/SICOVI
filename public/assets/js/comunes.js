/*
 * Configuraciones globales
 * Librerias:
 * Moment.js -> https://github.com/moment/moment/
 * Numeral.js -> https://github.com/adamwdraper/Numeral-js
 */
moment.locale("es-MX")
const MOMENT_FRONT = "DD/MM/YYYY"
const MOMENT_BACK = "YYYY-MM-DD"

numeral.zeroFormat("")
const NUMERAL_MONEDA = "$ 0,0.00"
const NUMERAL_DECIMAL = "0,0.00"

const inputFechasRestart = {}

/*
 * Templates de mensajes de alerta
 * Librerias:
 * SweetAlert2 -> https://sweetalert2.github.io/
 */
const tipoMensaje = (mensaje, icono, config = null) => {
    let configMensaje = typeof mensaje === "object" ? { html: mensaje } : { text: mensaje }
    configMensaje.icon = icono
    if (config) Object.assign(configMensaje, config)
    return Swal.fire(configMensaje)
}

const showError = (mensaje) => tipoMensaje(mensaje, "error")
const showSuccess = (mensaje) => tipoMensaje(mensaje, "success")
const showInfo = (mensaje) => tipoMensaje(mensaje, "info")
const showWarning = (mensaje) => tipoMensaje(mensaje, "warning")
const showWait = (mensaje = null) => {
    const config = {
        title: "Procesando su solicitud",
        text: mensaje || "Espere un momento...",
        imageUrl: "/assets/img/wait.svg",
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    }
    return tipoMensaje(mensaje, null, config)
}
const confirmarMovimiento = async (mensaje, titulo = "Confirmación") => {
    const config = {
        title: titulo,
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
        }
    }

    return await tipoMensaje(mensaje, "warning", config)
}

/*
 * Funcion para manejar peticiones con AJAX
 * Librerias:
 * jQuery -> https://jquery.com/
 */
const consultaServidor = (
    url,
    datos,
    fncOK,
    { metodo = "POST", tipoEsperado = "JSON", procesar = null, tipoContenido = null } = {}
) => {
    showWait()

    const configuracion = {
        url,
        data: datos,
        type: metodo,
        headers: { "Front-Request": "true" },
        success: (respuesta) => {
            if (typeof respuesta === "string") {
                try {
                    switch (tipoEsperado) {
                        case "JSON":
                            respuesta = JSON.parse(respuesta)
                            break
                        case "blob":
                            respuesta = new Blob([respuesta], { type: "application/pdf" })
                            break
                    }
                } catch (e) {
                    Swal.close()
                    return {
                        success: false,
                        mensaje: "Error al procesar la respuesta del servidor."
                    }
                }
            }

            Swal.close()
            fncOK(respuesta)
        },
        error: () => {
            showError("El servidor responde con un error.\nIntente más tarde.")
        }
    }

    if (tipoContenido != null) configuracion.contentType = tipoContenido
    if (procesar != null) configuracion.processData = procesar

    $.ajax(configuracion)
}

/*
 * Funciones para configruación y uso de tablas
 * Librerias:
 * DataTables -> https://datatables.net/
 */
const configuraTabla = (
    selector,
    { regXvista = true, buscar = true, footerInfo = true, paginacion = true, ordenar = true } = {}
) => {
    const configuracion = {
        lengthMenu: [
            [10, 40, -1],
            [10, 40, "Todos"]
        ],
        order: [],
        autoWidth: false,
        language: {
            emptyTable: "No hay datos disponibles",
            info: "Mostrando de _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Sin registros para mostrar",
            zeroRecords: "No se encontraron registros",
            lengthMenu: "Mostrar _MENU_ registros",
            search: "Buscar:"
        },
        columnDefs: [
            {
                className: "control",
                orderable: false,
                searchable: false,
                responsivePriority: 2,
                targets: 0,
                render: function (data, type, full, meta) {
                    return ""
                }
            }
        ],
        responsive: {
            details: {
                type: "inline",
                target: "tr"
                // display: DataTable.Responsive.display.modal({
                //     header: (row) => {
                //         return `Detalles de la fila ${row.index() + 1}`
                //     }
                // }),
                // type: "column",
                // renderer: (api, rowIdx, columns) => {
                //     const data = columns
                //         .map((col) => {
                //             return col.title !== ""
                //                 ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                //                 <td>${col.title}:</td>
                //                 <td>${col.data}</td>
                //                 </tr>`
                //                 : ""
                //         })
                //         .join("")

                //     if (data) {
                //         const div = document.createElement("div")
                //         div.classList.add("table-responsive")
                //         div.classList.add("z-1")
                //         const table = document.createElement("table")
                //         div.appendChild(table)
                //         table.classList.add("table")
                //         table.classList.add("datatables-basic")
                //         const tbody = document.createElement("tbody")
                //         tbody.innerHTML = data
                //         table.appendChild(tbody)
                //         return div
                //     }

                //     return false
                // }
            }
        }
    }

    configuracion.lengthChange = regXvista
    configuracion.searching = buscar
    configuracion.info = footerInfo
    configuracion.paging = paginacion
    configuracion.ordering = ordenar

    $(selector).DataTable(configuracion)
}

const actualizaDatosTabla = (selector, datos) => {
    const tabla = $(selector).DataTable()
    tabla.clear()
    if (Array.isArray(datos)) {
        datos.forEach((item) => {
            if (Array.isArray(item)) tabla.row.add(item).draw(false)
            else tabla.row.add(Object.values(item)).draw(false)
        })
    }
    tabla.draw()
}

const buscarEnTabla = (selector, columna, texto) => {
    const tabla = $(selector).DataTable()
    return tabla
        .rows()
        .data()
        .toArray()
        .filter((dato) => dato[columna] == texto)
}

/*
 * Configruación para inputs de tipo fecha
 * Librerias:
 * Date Range Picker -> https://github.com/dangrossman/daterangepicker
 */
const setInputFechas = (
    selector,
    { rango = false, inicio = 0, fin = 0, min = -1, max = -1, enModal = false } = {}
) => {
    const config = {
        locale: {
            format: MOMENT_FRONT,
            applyLabel: "Aplicar",
            cancelLabel: "Cancelar",
            fromLabel: "Desde",
            toLabel: "Hasta",
            customRangeLabel: "Personalizado",
            separator: " ➝ "
        },
        linkedCalendars: false,
        showDropdowns: true,
        singleDatePicker: !rango,
        autoApply: true,
        minYear: 2025,
        minDate: moment("01/01/2025", MOMENT_FRONT).format(MOMENT_FRONT),
        maxYear: moment().add(1, "years").year(),
        maxDate: moment().add(1, "year").endOf("year").format(MOMENT_FRONT),
        startDate: moment().add(inicio, "days").format(MOMENT_FRONT),
        endDate: moment().add(fin, "days").format(MOMENT_FRONT)
    }

    if (min >= 0) config.minDate = moment().subtract(min, "days").format(MOMENT_FRONT)
    if (max >= 0) config.maxDate = moment().add(max, "days").format(MOMENT_FRONT)
    if (enModal) config.parentEl = $(selector).closest(".modal-content")[0]

    $(selector).daterangepicker(config)
    inputFechasRestart[selector] = {
        inicio: config.startDate,
        fin: config.endDate
    }
}

const getInputFechas = (selector, rango = false, paraBack = true) => {
    const fecha = $(selector).data("daterangepicker")
    if (!fecha) return null
    const formato = paraBack ? MOMENT_BACK : MOMENT_FRONT
    const inicio = moment(fecha.startDate).format(formato)
    if (!rango) return inicio

    const fin = moment(fecha.endDate).format(formato)
    return { inicio, fin }
}

/*
 * Configruación para inputs de tipo moneda
 * Librerias:
 * Numeral.js -> https://github.com/adamwdraper/Numeral-js
 * cleave-zen -> https://github.com/nosir/cleave-zen
 */
const setInputMoneda = (selector, { negativo = false } = {}) => {
    $(selector).each((index, input) => {
        registerCursorTracker({
            input
        })
    })

    $(selector).on("input blur", function () {
        $(this).val(
            formatNumeral($(this).val(), {
                numeralThousandsGroupStyle: "thousand"
            })
        )
    })
}

/*
 * Configruación para validaciones
 * Librerias:
 * Form-Validation -> https://formvalidation.io/
 */
const setValidacionModal = (
    selector,
    campos,
    btnVal,
    accionVal,
    btnCancel,
    { accionCancel = null, limpiar = true } = {}
) => {
    const camposFV = {}

    Object.keys(campos).forEach((campo) => {
        camposFV[campo] = {
            validators: campos[campo]
        }
    })

    const validador = FormValidation.formValidation($(selector)[0], {
        fields: camposFV,
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            submitButton: new FormValidation.plugins.SubmitButton(),
            defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                defaultMessageContainer: false
            }),
            message: new FormValidation.plugins.Message({
                container: (field, element) => {
                    return $(element).closest(".form-group").find(".fv-message")[0]
                }
            })
        }
    })

    $(`${selector} ${btnVal}`).on("click", (e) => {
        validador.validate().then((validacion) => {
            if (validacion === "Valid") {
                if (accionVal) accionVal()
                limpiarCampos(true)
            } else showError("Debe corregir los errores marcados antes de continuar.")
        })
    })

    const cancelar = btnCancel ? `, ${selector} ${btnCancel}` : ""
    $(`${selector} .btn-close ${cancelar}`).on("click", () => {
        if (accionCancel) accionCancel()
        limpiarCampos(limpiar)
    })

    const limpiarCampos = (reset) => {
        validador.resetForm(reset)
        const campos = validador.getFields()
        Object.keys(campos).forEach((campo) => {
            const elemento = $(selector).find(`[name="${campo}"]`)
            if ($(elemento).hasClass("select2-hidden-accessible")) {
                $(elemento).val(null).trigger("change")
            } else if ($(elemento).data("daterangepicker")) {
                const fechasIniciales = inputFechasRestart["#" + $(elemento).attr("id")]
                if (fechasIniciales) {
                    $(elemento).data("daterangepicker").setStartDate(fechasIniciales.inicio)
                    $(elemento).data("daterangepicker").setEndDate(fechasIniciales.fin)
                }
            }
        })
    }

    return validador
}
