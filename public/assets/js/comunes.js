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

const consultaServidor = (
    url,
    datos,
    fncOK,
    metodo = "POST",
    tipo = "JSON",
    tipoContenido = null,
    procesar = null
) => {
    showWait()

    const configuracion = {
        type: metodo,
        url: url,
        data: datos,
        success: (res) => {
            if (tipo === "JSON") {
                try {
                    res = JSON.parse(res)
                } catch (error) {
                    console.error(error)
                    res = {
                        success: false,
                        mensaje: "Ocurrió un error al procesar la respuesta del servidor."
                    }
                }
            }
            if (tipo === "blob") res = new Blob([res], { type: "application/pdf" })

            Swal.close()
            fncOK(res)
        },
        error: (error) => {
            console.error(error)
            showError("Ocurrió un error al procesar la solicitud.")
        }
    }

    if (tipoContenido != null) configuracion.contentType = tipoContenido
    if (procesar != null) configuracion.processData = procesar

    $.ajax(configuracion)
}
/*
 * Funciones para formatear datos
 */
const formatoMoneda = (numero) =>
    parseFloat(numero).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })

/*
 * Funciones para las Datatables
 */
const configuraTabla = (
    selector,
    { regXvista = true, buscar = true, footerInfo = true, paginacion = false, ordenar = true } = {}
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
            paginate: {
                previous: "Anterior",
                next: "Siguiente"
            },
            info: "Mostrando de _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Sin registros para mostrar",
            zeroRecords: "No se encontraron registros",
            lengthMenu: "Mostrar _MENU_ registros por página",
            search: "Buscar:"
        },
        createdRow: (row) => {
            $(row).find("td").css("vertical-align", "middle")
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
 * Funciones para manejar inputs segun su tipo, funcion o finalidad
 */
const setInputMonto = (selector) => {
    $(document).on("input", selector, function () {
        let input = $(this)
        let valorOriginal = input.val()
        let valorNumerico = valorOriginal.replace(/[^\d.]/g, "")
        let partes = valorNumerico.split(".")

        if (partes.length > 2) valorNumerico = partes[0] + "." + partes[1]

        let [entero, decimal] = valorNumerico.split(".")
        entero = entero.replace(/^0+(?!$)/, "")
        let enteroFormateado = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        let valorFinal = decimal !== undefined ? `${enteroFormateado}.${decimal}` : enteroFormateado

        input.val(valorFinal)
    })
}

const getInputMonto = (selector) => {
    let valor = $(selector).val()
    if (!valor) return 0

    valor = valor.replace(/,/g, "").trim()
    let numero = parseFloat(valor)
    return isNaN(numero) ? 0 : numero
}

const setValidacionRangoFechas = (fechaInicioSelector, fechaFinSelector) => {
    const parsearFecha = (valor) => new Date(valor)

    $(document).on("input", fechaInicioSelector, () => {
        const fechaInicio = $(fechaInicioSelector).val()
        const fechaFin = $(fechaFinSelector).val()

        if (fechaInicio && fechaFin && parsearFecha(fechaInicio) > parsearFecha(fechaFin)) {
            $(fechaInicioSelector).val(fechaFin)
            showWarning("La fecha inicial no puede ser mayor que la fecha final.")
        }
    })

    $(document).on("input", fechaFinSelector, () => {
        const fechaInicio = $(fechaInicioSelector).val()
        const fechaFin = $(fechaFinSelector).val()

        if (fechaInicio && fechaFin && parsearFecha(fechaInicio) > parsearFecha(fechaFin)) {
            $(fechaFinSelector).val(fechaInicio)
            showWarning("La fecha final no puede ser menor que la fecha inicial.")
        }
    })
}
