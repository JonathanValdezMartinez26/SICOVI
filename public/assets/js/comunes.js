/*
 * Configuraciones globales de librerías
 */
numeral.zeroFormat("")
const NUMERAL_MONEDA = "$ 0,0.00"

moment.locale("es-MX")
const MOMENT_FRONT = "DD/MM/YYYY"
const MOMENT_BACK = "YYYY-MM-DD"

/*
 * Templates para mensajes de alerta
 * Usando SweetAlert2
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

/*
 * Funcion para manejar peticiones AJAX
 * Usando jQuery
 */
const consultaServidor = (
    url,
    datos,
    fncOK,
    { metodo = "POST", tipo = "JSON", tipoContenido = null, procesar = null } = {}
) => {
    showWait()

    const configuracion = {
        type: metodo,
        url: url,
        data: datos,
        success: (res) => {
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
 * Funciones para configuracion y uso de Datatables
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
            lengthMenu: "Mostrar _MENU_ registros por página",
            search: "Buscar:"
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
 * Configuracion para inputs de tipo fecha
 * Usando Flatpickr
 */
const setRangoFechas = (
    selector,
    { diasAntes = 0, diasDespues = 0, min = 0, max = 0, enModal = false } = {}
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
        showDropdowns: true,
        minYear: 2025,
        maxYear: moment().add(1, "years").year()
    }

    if (diasAntes > 0) config.startDate = moment().subtract(diasAntes, "days").format(MOMENT_FRONT)
    if (diasDespues > 0) config.endDate = moment().add(diasDespues, "days").format(MOMENT_FRONT)
    if (min > 0) config.minDate = moment().subtract(min, "days").format(MOMENT_FRONT)
    if (max > 0) config.maxDate = moment().add(max, "days").format(MOMENT_FRONT)
    if (enModal) config.parentEl = $(selector).closest(".modal-content")[0]

    $(selector).daterangepicker(config)
}

const getRangoFechas = (selector, back = true) => {
    const fecha = $(selector).data("daterangepicker")
    if (!fecha) return null
    const inicio = back ? moment(fecha.startDate).format(MOMENT_BACK) : fecha.startDate
    const fin = back ? moment(fecha.endDate).format(MOMENT_BACK) : fecha.endDate
    return { inicio, fin }
}

/*
 * Funciones utilitarias
 */
const setInputMoneda = (selector, opciones = {}) => {
    const config = {
        valorMinimo: null,
        valorMaximo: null,
        permitirNegativos: false
    }

    $(document).on("blur", selector, function () {
        let input = $(this)
        let valorOriginal = input.val()

        let caracteresPermitidos = config.permitirNegativos ? /[^\d.-]/g : /[^\d.]/g
        let valorLimpio = valorOriginal.replace(caracteresPermitidos, "")

        let partes = valorLimpio.split(".")
        if (partes.length > 2) {
            valorLimpio = partes[0] + "." + partes.slice(1).join("")
        }

        let numero = numeral(valorLimpio).value()

        if (config.valorMinimo !== null && numero < config.valorMinimo) {
            numero = config.valorMinimo
        }
        if (config.valorMaximo !== null && numero > config.valorMaximo) {
            numero = config.valorMaximo
        }

        let valorFormateado = numeral(numero).format("0,0.00")

        if (valorOriginal.endsWith(".")) {
            valorFormateado = valorFormateado.replace(".00", ".")
        }

        input.val(valorFormateado)
    })
}
