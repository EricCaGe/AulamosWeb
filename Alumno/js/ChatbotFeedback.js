"use strict";

document.addEventListener("DOMContentLoaded", () => {
    if (window.AULAMOS_CHATBOT_FEEDBACK_INICIALIZADO) {
        return;
    }

    window.AULAMOS_CHATBOT_FEEDBACK_INICIALIZADO = true;

    const chat =
        document.getElementById("chatMessages");

    const estado =
        document.getElementById("chatStatus");

    if (!chat) {
        console.warn(
            "AulaBot Feedback: no se encontró chatMessages."
        );
        return;
    }

    const api = {
        iniciar:
            "api/chatbot/iniciar_sesion.php",

        historial:
            "api/chatbot/obtener_historial.php",

        core:
            "api/chatbot/core.php",
    };

    let actualizando = false;
    let temporizador = null;

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
                "El servidor devolvió una respuesta inválida."
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

    async function obtenerSesion() {
        const respuesta =
            await fetch(
                api.iniciar,
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    headers: {
                        "Content-Type":
                            "application/json",

                        Accept:
                            "application/json",
                    },

                    body:
                        JSON.stringify({}),
                }
            );

        const datos =
            await leerJson(
                respuesta
            );

        const id =
            Number(
                datos.idSesion
            );

        if (
            !Number.isInteger(id) ||
            id <= 0
        ) {
            throw new Error(
                "No se encontró una conversación activa."
            );
        }

        return id;
    }

    async function obtenerHistorial() {
        const idSesion =
            await obtenerSesion();

        const respuesta =
            await fetch(
                `${api.historial}?id_sesion=${encodeURIComponent(idSesion)}`,
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {
                        Accept:
                            "application/json",
                    },
                }
            );

        return leerJson(
            respuesta
        );
    }

    async function ejecutarCore(
        datos
    ) {
        const respuesta =
            await fetch(
                api.core,
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    headers: {
                        "Content-Type":
                            "application/json",

                        Accept:
                            "application/json",
                    },

                    body:
                        JSON.stringify(
                            datos
                        ),
                }
            );

        return leerJson(
            respuesta
        );
    }

    function mostrarEstado(
        mensaje,
        error = false
    ) {
        if (!estado) {
            return;
        }

        estado.textContent =
            mensaje;

        estado.classList.toggle(
            "is-error",
            error
        );
    }

    function crearBoton({
        icono,
        titulo,
        etiqueta,
    }) {
        const boton =
            document.createElement(
                "button"
            );

        boton.type =
            "button";

        boton.className =
            "aulabot-tool";

        boton.title =
            titulo;

        boton.setAttribute(
            "aria-label",
            etiqueta
        );

        let simbolo = "•";

        if (
            icono.includes(
                "thumbs-up"
            )
        ) {
            simbolo = "👍";
        }

        if (
            icono.includes(
                "thumbs-down"
            )
        ) {
            simbolo = "👎";
        }

        if (
            icono.includes(
                "rotate-right"
            )
        ) {
            simbolo = "↻";
        }

        const span =
            document.createElement(
                "span"
            );

        span.textContent =
            simbolo;

        span.setAttribute(
            "aria-hidden",
            "true"
        );

        boton.appendChild(
            span
        );

        return boton;
    }

    function marcarUtilidad(
        botonUtil,
        botonNoUtil,
        utilidad
    ) {
        botonUtil.classList.toggle(
            "active",
            utilidad === "Útil"
        );

        botonNoUtil.classList.toggle(
            "active",
            utilidad === "No útil"
        );
    }

    function agregarHerramientas(
        articulo,
        interaccion
    ) {
        const herramientasExistentes =
            articulo.querySelector(
                ".aulabot-response-tools"
            );

        if (herramientasExistentes) {
            const botones =
                herramientasExistentes.querySelectorAll(
                    ".aulabot-tool"
                );

            if (botones.length >= 2) {
                marcarUtilidad(
                    botones[0],
                    botones[1],
                    interaccion.utilidadUsuario
                );
            }

            return;
        }

        const idMensaje =
            Number(
                interaccion.idMensaje
            );

        if (
            !Number.isInteger(
                idMensaje
            ) ||
            idMensaje <= 0
        ) {
            return;
        }

        articulo.dataset.idMensaje =
            String(idMensaje);

        const contenido =
            articulo.querySelector(
                ".message-content"
            );

        if (!contenido) {
            return;
        }

        const zona =
            document.createElement(
                "div"
            );

        zona.className =
            "aulabot-response-tools";

        const util =
            crearBoton({
                icono:
                    "fa-regular fa-thumbs-up",

                titulo:
                    "Respuesta útil",

                etiqueta:
                    "Marcar respuesta como útil",
            });

        const noUtil =
            crearBoton({
                icono:
                    "fa-regular fa-thumbs-down",

                titulo:
                    "Respuesta no útil",

                etiqueta:
                    "Marcar respuesta como no útil",
            });

        const regenerar =
            crearBoton({
                icono:
                    "fa-solid fa-rotate-right",

                titulo:
                    "Regenerar respuesta",

                etiqueta:
                    "Generar otra respuesta",
            });

        marcarUtilidad(
            util,
            noUtil,
            interaccion.utilidadUsuario
        );

        util.addEventListener(
            "click",
            async () => {
                util.disabled =
                    true;

                noUtil.disabled =
                    true;

                try {
                    await ejecutarCore({
                        accion:
                            "utilidad",

                        idMensaje,

                        utilidad:
                            "Útil",
                    });

                    marcarUtilidad(
                        util,
                        noUtil,
                        "Útil"
                    );

                    mostrarEstado(
                        "Gracias por tu valoración."
                    );
                } catch (error) {
                    mostrarEstado(
                        error.message,
                        true
                    );
                } finally {
                    util.disabled =
                        false;

                    noUtil.disabled =
                        false;
                }
            }
        );

        noUtil.addEventListener(
            "click",
            async () => {
                util.disabled =
                    true;

                noUtil.disabled =
                    true;

                try {
                    await ejecutarCore({
                        accion:
                            "utilidad",

                        idMensaje,

                        utilidad:
                            "No útil",
                    });

                    marcarUtilidad(
                        util,
                        noUtil,
                        "No útil"
                    );

                    mostrarEstado(
                        "Gracias por tu valoración."
                    );
                } catch (error) {
                    mostrarEstado(
                        error.message,
                        true
                    );
                } finally {
                    util.disabled =
                        false;

                    noUtil.disabled =
                        false;
                }
            }
        );

        regenerar.addEventListener(
            "click",
            async () => {
                regenerar.disabled =
                    true;

                regenerar.classList.add(
                    "loading"
                );

                mostrarEstado(
                    "AulaBot está generando otra respuesta..."
                );

                try {
                    const datos =
                        await ejecutarCore({
                            accion:
                                "regenerar",

                            idMensaje,
                        });

                    const parrafo =
                        contenido.querySelector(
                            "p"
                        );

                    if (
                        parrafo &&
                        typeof datos.respuesta ===
                            "string"
                    ) {
                        parrafo.textContent =
                            datos.respuesta;
                    }

                    mostrarEstado(
                        "Respuesta regenerada."
                    );

                    /*
                     * Chatbot.js también detectará
                     * el cambio en MySQL durante
                     * su sincronización normal.
                     */
                    programarDecoracion();
                } catch (error) {
                    mostrarEstado(
                        error.message,
                        true
                    );
                } finally {
                    regenerar.disabled =
                        false;

                    regenerar.classList.remove(
                        "loading"
                    );
                }
            }
        );

        zona.append(
            util,
            noUtil,
            regenerar
        );

        const tiempo =
            contenido.querySelector(
                "time"
            );

        if (tiempo) {
            contenido.insertBefore(
                zona,
                tiempo
            );
        } else {
            contenido.appendChild(
                zona
            );
        }
    }

    async function decorar() {
        if (actualizando) {
            return;
        }

        actualizando =
            true;

        try {
            const datos =
                await obtenerHistorial();

            const interacciones =
                Array.isArray(
                    datos.interacciones
                )
                    ? datos.interacciones
                    : [];

            /*
             * Solo mensajes dinámicos.
             * El saludo inicial de PHP no tiene
             * data-message-type y queda fuera.
             */
            const respuestas =
                Array.from(
                    chat.querySelectorAll(
                        '.message[data-message-type="bot"]:not(.message-error)'
                    )
                );

            respuestas.forEach(
                (
                    articulo,
                    indice
                ) => {
                    const interaccion =
                        interacciones[
                            indice
                        ];

                    if (
                        interaccion
                    ) {
                        agregarHerramientas(
                            articulo,
                            interaccion
                        );
                    }
                }
            );
        } catch (error) {
            /*
             * No mostramos error cada vez que
             * corre el observador para evitar
             * molestar al usuario.
             */
            console.warn(
                "AulaBot Feedback:",
                error
            );
        } finally {
            actualizando =
                false;
        }
    }

    function programarDecoracion() {
        if (
            temporizador !==
            null
        ) {
            window.clearTimeout(
                temporizador
            );
        }

        temporizador =
            window.setTimeout(
                () => {
                    temporizador =
                        null;

                    void decorar();
                },
                300
            );
    }

    const observador =
        new MutationObserver(
            () => {
                programarDecoracion();
            }
        );

    observador.observe(
        chat,
        {
            childList:
                true,

            subtree:
                true,
        }
    );

    programarDecoracion();

    window.setInterval(
        () => {
            programarDecoracion();
        },
        3000
    );
});