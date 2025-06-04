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
