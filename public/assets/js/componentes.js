const menuAcciones = (opciones) => {
    const acciones = opciones
        .map((opcion) => {
            if (opcion === null || opcion === undefined) return ""
            if (opcion instanceof HTMLElement) return opcion.outerHTML
            if (opcion instanceof jQuery) return opcion[0].outerHTML
            if (typeof opcion === "string") {
                if (opcion === "divisor") return `<div class="dropdown-divider"></div>`
                return opcion
            }

            return `<a class="dropdown-item ${opcion.clase}" href="${
                opcion.href || "javascript:;"
            }" onclick="${opcion.funcion}">
                        <i class="fa ${opcion.icono}">&nbsp;</i>${opcion.texto}
                    </a>`
        })
        .join("")

    return `<button type="button" class="btn dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-vertical"></i></button>
        <div class="dropdown-menu">${acciones}</div>`
}

const modalVisorArchivos = (
    url,
    parametros,
    { metodo = "POST", titulo = null, fncClose = null } = {}
) => {
    const id = new Date().getTime()
    const idModal = `modalVisorArchivos_${id}`
    const idVisor = `archivoVisor_${id}`
    titulo = titulo || "Visor de Archivos"

    const getModal = (modalURL, modalTitulo, modalId, modalVisorId) => {
        return `<div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true" role="dialog">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close btnCerrarVer" data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="text-center w-100">
                                <h4 class="address-title mb-2">${modalTitulo}</h4>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 text-center">
                                    <embed id="${modalVisorId}" src="${modalURL}" style="width: 100%; min-height: 30vh;"></embed>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btnCerrarVer" data-bs-dismiss="modal" aria-label="Close">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>`
    }

    showWait("Por favor, espere mientras se carga el archivo.")
    fetch(url, {
        method: metodo,
        body: parametros
    })
        .then((response) => {
            if (!response.ok) throw new Error("Error al obtener el comprobante")
            return response.blob()
        })
        .then((blob) => {
            const urlModal = URL.createObjectURL(blob)
            $("body").append(getModal(urlModal, titulo, idModal, idVisor))
            const modalElement = $(`#${idModal}`)
            modalElement.modal("show")
            modalElement.on("hidden.bs.modal", function () {
                if (typeof fncClose === "function") {
                    fncClose()
                }
                $(this).remove()
            })
            Swal.close()
        })
        .catch((error) => {
            Swal.close()
            showError(error.message)
        })
}
