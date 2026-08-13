"use strict";

document.addEventListener("DOMContentLoaded", () => {
    if (window.AULAMOS_CHATBOT_HISTORIAL_INICIALIZADO) {
        return;
    }

    window.AULAMOS_CHATBOT_HISTORIAL_INICIALIZADO = true;

    const endpoint = "api/chatbot/core.php";

    const headerActions =
        document.querySelector(".header-actions");

    const botonNueva =
        document.getElementById("btnLimpiarChat");

    if (!headerActions) {
        console.warn(
            "AulaBot Historial: no se encontró .header-actions."
        );
        return;
    }

    async function leerJson(respuesta) {
        const texto =
            await respuesta.text();

        let datos = {};

        try {
            datos =
                texto
                    ? JSON.parse(texto)
                    : {};
        } catch {
            throw new Error(
                "AulaBot devolvió una respuesta inválida."
            );
        }

        if (
            !respuesta.ok ||
            datos.success === false
        ) {
            throw new Error(
                datos.message ||
                datos.mensaje ||
                `Error ${respuesta.status}`
            );
        }

        return datos;
    }

    async function ejecutar(datos) {
        const respuesta =
            await fetch(
                endpoint,
                {
                    method: "POST",

                    credentials:
                        "same-origin",

                    headers: {
                        "Content-Type":
                            "application/json",

                        Accept:
                            "application/json",
                    },

                    body:
                        JSON.stringify(datos),
                }
            );

        return leerJson(respuesta);
    }

    function crearElemento(
        etiqueta,
        clase = "",
        texto = ""
    ) {
        const elemento =
            document.createElement(
                etiqueta
            );

        if (clase) {
            elemento.className =
                clase;
        }

        if (texto) {
            elemento.textContent =
                texto;
        }

        return elemento;
    }

    /*
     * Botón del historial
     */
    const botonHistorial =
        crearElemento(
            "button",
            "header-icon-button"
        );

    botonHistorial.type =
        "button";

    botonHistorial.id =
        "btnHistorialChat";

    botonHistorial.title =
        "Conversaciones";

    botonHistorial.setAttribute(
        "aria-label",
        "Abrir historial de conversaciones"
    );

    const iconoHistorial =
        crearElemento("i");

    iconoHistorial.className =
        "fa-solid fa-clock-rotate-left";

    iconoHistorial.setAttribute(
        "aria-hidden",
        "true"
    );

    botonHistorial.appendChild(
        iconoHistorial
    );

    if (botonNueva) {
        headerActions.insertBefore(
            botonHistorial,
            botonNueva
        );
    } else {
        headerActions.prepend(
            botonHistorial
        );
    }

    /*
     * Panel
     */
    const panel =
        crearElemento(
            "div",
            "aulabot-history-panel"
        );

    panel.id =
        "aulabotHistoryPanel";

    panel.setAttribute(
        "aria-hidden",
        "true"
    );

    const fondo =
        crearElemento(
            "div",
            "aulabot-history-backdrop"
        );

    const drawer =
        crearElemento(
            "aside",
            "aulabot-history-drawer"
        );

    drawer.setAttribute(
        "role",
        "dialog"
    );

    drawer.setAttribute(
        "aria-modal",
        "true"
    );

    drawer.setAttribute(
        "aria-labelledby",
        "aulabotHistoryTitle"
    );

    const encabezado =
        crearElemento(
            "div",
            "aulabot-history-header"
        );

    const textos =
        crearElemento("div");

    const titulo =
        crearElemento(
            "strong",
            "",
            "Conversaciones"
        );

    titulo.id =
        "aulabotHistoryTitle";

    const subtitulo =
        crearElemento(
            "small",
            "",
            "Continúa una conversación anterior"
        );

    textos.append(
        titulo,
        subtitulo
    );

    const cerrarBoton =
        crearElemento(
            "button",
            "aulabot-history-close"
        );

    cerrarBoton.type =
        "button";

    cerrarBoton.setAttribute(
        "aria-label",
        "Cerrar historial"
    );

    const iconoCerrar =
        crearElemento("i");

    iconoCerrar.className =
        "fa-solid fa-xmark";

    iconoCerrar.setAttribute(
        "aria-hidden",
        "true"
    );

    cerrarBoton.appendChild(
        iconoCerrar
    );

    encabezado.append(
        textos,
        cerrarBoton
    );

    const nueva =
        crearElemento(
            "button",
            "aulabot-history-new"
        );

    nueva.type =
        "button";

    const iconoNueva =
        crearElemento("i");

    iconoNueva.className =
        "fa-solid fa-plus";

    iconoNueva.setAttribute(
        "aria-hidden",
        "true"
    );

    const textoNueva =
        document.createTextNode(
            " Nueva conversación"
        );

    nueva.append(
        iconoNueva,
        textoNueva
    );

    const lista =
        crearElemento("div");

    lista.id =
        "aulabotHistoryList";

    lista.setAttribute(
        "aria-live",
        "polite"
    );

    drawer.append(
        encabezado,
        nueva,
        lista
    );

    panel.append(
        fondo,
        drawer
    );

    document.body.appendChild(
        panel
    );

    function cerrar() {
        panel.classList.remove(
            "open"
        );

        panel.setAttribute(
            "aria-hidden",
            "true"
        );

        botonHistorial.focus();
    }

    function abrir() {
        panel.classList.add(
            "open"
        );

        panel.setAttribute(
            "aria-hidden",
            "false"
        );

        void cargarConversaciones();
    }

    async function cargarConversaciones() {
        lista.replaceChildren();

        const cargando =
            crearElemento(
                "p",
                "aulabot-history-loading",
                "Cargando conversaciones..."
            );

        lista.appendChild(
            cargando
        );

        try {
            const datos =
                await ejecutar({
                    accion:
                        "listar_conversaciones",
                });

            const conversaciones =
                Array.isArray(
                    datos.conversaciones
                )
                    ? datos.conversaciones
                    : [];

            lista.replaceChildren();

            if (
                conversaciones.length ===
                0
            ) {
                lista.appendChild(
                    crearElemento(
                        "p",
                        "aulabot-history-empty",
                        "Aún no tienes conversaciones."
                    )
                );

                return;
            }

            conversaciones.forEach(
                (conversacion) => {
                    const boton =
                        crearElemento(
                            "button",
                            "aulabot-history-item"
                        );

                    boton.type =
                        "button";

                    if (
                        conversacion.activa
                    ) {
                        boton.classList.add(
                            "active"
                        );
                    }

                    const tituloConversacion =
                        crearElemento(
                            "strong",
                            "",
                            conversacion.titulo ||
                                "Nueva conversación"
                        );

                    const cantidad =
                        Number(
                            conversacion.totalMensajes ||
                            0
                        );

                    const meta =
                        crearElemento(
                            "small",
                            "",
                            `${cantidad} mensaje${
                                cantidad === 1
                                    ? ""
                                    : "s"
                            }${
                                conversacion.activa
                                    ? " · Activa"
                                    : ""
                            }`
                        );

                    boton.append(
                        tituloConversacion,
                        meta
                    );

                    boton.addEventListener(
                        "click",
                        async () => {
                            boton.disabled =
                                true;

                            try {
                                await ejecutar({
                                    accion:
                                        "activar_conversacion",

                                    idSesion:
                                        Number(
                                            conversacion.idSesion
                                        ),
                                });

                                /*
                                 * Al recargar, Chatbot.js
                                 * recupera la sesión activa.
                                 */
                                window.location.reload();
                            } catch (error) {
                                boton.disabled =
                                    false;

                                lista.replaceChildren(
                                    crearElemento(
                                        "p",
                                        "aulabot-history-empty",
                                        error.message ||
                                            "No se pudo abrir la conversación."
                                    )
                                );
                            }
                        }
                    );

                    lista.appendChild(
                        boton
                    );
                }
            );
        } catch (error) {
            lista.replaceChildren(
                crearElemento(
                    "p",
                    "aulabot-history-empty",
                    error.message ||
                        "No se pudo cargar el historial."
                )
            );
        }
    }

    botonHistorial.addEventListener(
        "click",
        abrir
    );

    cerrarBoton.addEventListener(
        "click",
        cerrar
    );

    fondo.addEventListener(
        "click",
        cerrar
    );

    nueva.addEventListener(
        "click",
        () => {
            cerrar();

            if (botonNueva) {
                botonNueva.click();
            }
        }
    );

    document.addEventListener(
        "keydown",
        (evento) => {
            if (
                evento.key ===
                    "Escape" &&
                panel.classList.contains(
                    "open"
                )
            ) {
                cerrar();
            }
        }
    );
});