"use strict";

document.addEventListener("DOMContentLoaded", () => {
    if (window.AULAMOS_CHATBOT_INICIALIZADO) {
        console.warn("AulaBot ya fue inicializado.");
        return;
    }

    window.AULAMOS_CHATBOT_INICIALIZADO = true;

    const config = window.AULAMOS_CHATBOT_CONFIG ?? {};

    const endpoint =
        typeof config.endpoint === "string"
            ? config.endpoint
            : "http://localhost:3000/api/chatbot/mensaje";

    const rol =
        typeof config.rol === "string" && config.rol.trim()
            ? config.rol.trim()
            : "alumno";

    const idUsuario =
        Number.isInteger(Number(config.idUsuario))
            ? Number(config.idUsuario)
            : "anonimo";

    const claveHistorial = `aulamos_chatbot_historial_${idUsuario}`;

    const formulario = document.getElementById("chatForm");
    const campoMensaje = document.getElementById("mensajeChat");
    const botonEnviar = document.getElementById("btnEnviarMensaje");
    const contenedorMensajes = document.getElementById("chatMessages");
    const estadoChat = document.getElementById("chatStatus");
    const contadorCaracteres =
        document.getElementById("cantidadCaracteres");
    const botonLimpiar = document.getElementById("btnLimpiarChat");
    const botonLeer = document.getElementById("btnLeerRespuesta");
    const preguntasRapidas =
        document.querySelectorAll(".quick-question");

    if (
        !formulario ||
        !campoMensaje ||
        !botonEnviar ||
        !contenedorMensajes ||
        !estadoChat
    ) {
        console.error(
            "No se encontraron todos los elementos necesarios de AulaBot.",
        );
        return;
    }

    let enviandoMensaje = false;
    let ultimaRespuesta = "";
    let historial = cargarHistorial();

    restaurarHistorial();

    function obtenerHoraActual() {
        return new Intl.DateTimeFormat("es-MX", {
            hour: "2-digit",
            minute: "2-digit",
        }).format(new Date());
    }

    function desplazarAlFinal() {
        requestAnimationFrame(() => {
            contenedorMensajes.scrollTop =
                contenedorMensajes.scrollHeight;
        });
    }

    function actualizarContador() {
        const cantidad = campoMensaje.value.length;

        if (contadorCaracteres) {
            contadorCaracteres.textContent = String(cantidad);
        }
    }

    function ajustarAlturaCampo() {
        campoMensaje.style.height = "auto";

        campoMensaje.style.height =
            `${Math.min(campoMensaje.scrollHeight, 150)}px`;
    }

    function establecerEstado(mensaje = "", esError = false) {
        estadoChat.textContent = mensaje;
        estadoChat.classList.toggle("is-error", esError);
    }

    function establecerCargando(cargando) {
        enviandoMensaje = cargando;

        botonEnviar.disabled = cargando;
        campoMensaje.disabled = cargando;

        botonEnviar.setAttribute(
            "aria-busy",
            cargando ? "true" : "false",
        );

        const textoBoton = botonEnviar.querySelector("span");

        if (textoBoton) {
            textoBoton.textContent = cargando
                ? "Enviando..."
                : "Enviar";
        }
    }

    function crearAvatar(tipo) {
        const avatar = document.createElement("div");
        avatar.className = "message-avatar";
        avatar.setAttribute("aria-hidden", "true");

        const icono = document.createElement("i");

        icono.className =
            tipo === "bot"
                ? "fa-solid fa-robot"
                : "fa-solid fa-user";

        avatar.appendChild(icono);

        return avatar;
    }

    function crearMensaje({
        tipo,
        texto,
        hora = obtenerHoraActual(),
        error = false,
        guardar = true,
    }) {
        const articulo = document.createElement("article");

        articulo.classList.add(
            "message",
            tipo === "usuario" ? "message-user" : "message-bot",
        );

        if (error) {
            articulo.classList.add("message-error");
        }

        articulo.dataset.messageType = tipo;

        articulo.appendChild(
            crearAvatar(tipo === "usuario" ? "usuario" : "bot"),
        );

        const contenido = document.createElement("div");
        contenido.className = "message-content";

        const parrafo = document.createElement("p");
        parrafo.textContent = texto;

        const tiempo = document.createElement("time");
        tiempo.textContent = hora;

        contenido.appendChild(parrafo);
        contenido.appendChild(tiempo);
        articulo.appendChild(contenido);

        contenedorMensajes.appendChild(articulo);

        if (tipo === "bot" && !error) {
            ultimaRespuesta = texto;
        }

        if (guardar) {
            historial.push({
                tipo,
                texto,
                hora,
                error,
            });

            guardarHistorial();
        }

        desplazarAlFinal();

        return articulo;
    }

    function crearIndicadorEscritura() {
        const articulo = document.createElement("article");

        articulo.className = "message message-bot";
        articulo.id = "indicadorEscritura";

        articulo.appendChild(crearAvatar("bot"));

        const contenido = document.createElement("div");
        contenido.className = "message-content";

        const indicador = document.createElement("div");
        indicador.className = "typing-indicator";
        indicador.setAttribute(
            "aria-label",
            "AulaBot está escribiendo",
        );

        for (let indice = 0; indice < 3; indice += 1) {
            indicador.appendChild(document.createElement("span"));
        }

        contenido.appendChild(indicador);
        articulo.appendChild(contenido);
        contenedorMensajes.appendChild(articulo);

        desplazarAlFinal();

        return articulo;
    }

    function obtenerRespuestaTexto(datos) {
        if (
            datos &&
            typeof datos.respuesta === "string" &&
            datos.respuesta.trim()
        ) {
            return datos.respuesta.trim();
        }

        if (
            datos &&
            typeof datos.mensaje === "string" &&
            datos.mensaje.trim()
        ) {
            return datos.mensaje.trim();
        }

        throw new Error(
            "El servidor respondió, pero no envió una respuesta válida.",
        );
    }

    async function consultarAulaBot(mensaje) {
        const controlador = new AbortController();

        const temporizador = window.setTimeout(() => {
            controlador.abort();
        }, 30000);

        try {
            const respuesta = await fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    mensaje,
                    rol,
                }),
                signal: controlador.signal,
            });

            let datos = null;

            try {
                datos = await respuesta.json();
            } catch {
                throw new Error(
                    "El servidor devolvió una respuesta que no es válida.",
                );
            }

            if (!respuesta.ok) {
                const mensajeError =
                    typeof datos?.error === "string"
                        ? datos.error
                        : typeof datos?.mensaje === "string"
                            ? datos.mensaje
                            : `Error del servidor: ${respuesta.status}`;

                throw new Error(mensajeError);
            }

            return obtenerRespuestaTexto(datos);
        } finally {
            window.clearTimeout(temporizador);
        }
    }

    function describirError(error) {
        if (error?.name === "AbortError") {
            return (
                "AulaBot tardó demasiado en responder. " +
                "Intenta nuevamente."
            );
        }

        if (error instanceof TypeError) {
            return (
                "No se pudo conectar con AulaBot. Verifica que el " +
                "backend esté encendido en el puerto 3000."
            );
        }

        if (
            error instanceof Error &&
            typeof error.message === "string" &&
            error.message.trim()
        ) {
            return error.message.trim();
        }

        return "Ocurrió un error inesperado al consultar AulaBot.";
    }

    async function enviarMensaje(mensajeRecibido = null) {
        if (enviandoMensaje) {
            return;
        }

        const mensaje =
            typeof mensajeRecibido === "string"
                ? mensajeRecibido.trim()
                : campoMensaje.value.trim();

        if (!mensaje) {
            establecerEstado(
                "Escribe una pregunta antes de enviarla.",
                true,
            );

            campoMensaje.focus();
            return;
        }

        if (mensaje.length > 1000) {
            establecerEstado(
                "La pregunta no puede superar los 1000 caracteres.",
                true,
            );
            return;
        }

        establecerEstado("");
        crearMensaje({
            tipo: "usuario",
            texto: mensaje,
        });

        campoMensaje.value = "";
        actualizarContador();
        ajustarAlturaCampo();
        establecerCargando(true);

        const indicador = crearIndicadorEscritura();

        establecerEstado("AulaBot está preparando una respuesta...");

        try {
            const respuesta = await consultarAulaBot(mensaje);

            indicador.remove();

            crearMensaje({
                tipo: "bot",
                texto: respuesta,
            });

            establecerEstado("Respuesta recibida.");
        } catch (error) {
            indicador.remove();

            const mensajeError = describirError(error);

            crearMensaje({
                tipo: "bot",
                texto: mensajeError,
                error: true,
                guardar: false,
            });

            establecerEstado(mensajeError, true);

            console.error("Error al consultar AulaBot:", error);
        } finally {
            establecerCargando(false);
            campoMensaje.focus();
        }
    }

    function cargarHistorial() {
        try {
            const historialGuardado =
                sessionStorage.getItem(claveHistorial);

            if (!historialGuardado) {
                return [];
            }

            const datos = JSON.parse(historialGuardado);

            if (!Array.isArray(datos)) {
                return [];
            }

            return datos.filter((mensaje) => {
                return (
                    mensaje &&
                    ["usuario", "bot"].includes(mensaje.tipo) &&
                    typeof mensaje.texto === "string" &&
                    mensaje.texto.trim()
                );
            });
        } catch (error) {
            console.warn(
                "No se pudo recuperar el historial de AulaBot:",
                error,
            );

            return [];
        }
    }

    function guardarHistorial() {
        try {
            const historialLimitado = historial.slice(-50);

            sessionStorage.setItem(
                claveHistorial,
                JSON.stringify(historialLimitado),
            );

            historial = historialLimitado;
        } catch (error) {
            console.warn(
                "No se pudo guardar el historial de AulaBot:",
                error,
            );
        }
    }

    function restaurarHistorial() {
        if (historial.length === 0) {
            return;
        }

        historial.forEach((mensaje) => {
            crearMensaje({
                tipo: mensaje.tipo,
                texto: mensaje.texto,
                hora: mensaje.hora,
                error: Boolean(mensaje.error),
                guardar: false,
            });
        });
    }

    function limpiarConversacion() {
        const confirmado = window.confirm(
            "¿Deseas eliminar la conversación actual con AulaBot?",
        );

        if (!confirmado) {
            return;
        }

        historial = [];
        ultimaRespuesta = "";

        sessionStorage.removeItem(claveHistorial);

        const mensajesGuardados =
            contenedorMensajes.querySelectorAll(
                ".message[data-message-type]",
            );

        mensajesGuardados.forEach((mensaje) => mensaje.remove());

        crearMensaje({
            tipo: "bot",
            texto:
                "La conversación fue limpiada. " +
                "¿Qué tema quieres aprender ahora?",
            guardar: false,
        });

        establecerEstado("Conversación limpiada.");
        campoMensaje.focus();
    }

    function leerUltimaRespuesta() {
        if (
            !("speechSynthesis" in window) ||
            typeof SpeechSynthesisUtterance === "undefined"
        ) {
            establecerEstado(
                "Este navegador no permite leer respuestas en voz alta.",
                true,
            );
            return;
        }

        if (!ultimaRespuesta) {
            establecerEstado(
                "Aún no hay una respuesta de AulaBot para leer.",
                true,
            );
            return;
        }

        window.speechSynthesis.cancel();

        const lectura = new SpeechSynthesisUtterance(
            ultimaRespuesta,
        );

        lectura.lang = "es-MX";
        lectura.rate = 0.95;
        lectura.pitch = 1;

        lectura.onstart = () => {
            establecerEstado("Leyendo la respuesta en voz alta...");
        };

        lectura.onend = () => {
            establecerEstado("Lectura finalizada.");
        };

        lectura.onerror = () => {
            establecerEstado(
                "No fue posible leer la respuesta.",
                true,
            );
        };

        window.speechSynthesis.speak(lectura);
    }

    formulario.addEventListener("submit", (evento) => {
        evento.preventDefault();
        void enviarMensaje();
    });

    campoMensaje.addEventListener("input", () => {
        actualizarContador();
        ajustarAlturaCampo();

        if (estadoChat.classList.contains("is-error")) {
            establecerEstado("");
        }
    });

    campoMensaje.addEventListener("keydown", (evento) => {
        if (
            evento.key === "Enter" &&
            !evento.shiftKey &&
            !evento.isComposing
        ) {
            evento.preventDefault();
            void enviarMensaje();
        }
    });

    preguntasRapidas.forEach((boton) => {
        boton.addEventListener("click", () => {
            const pregunta = boton.dataset.question;

            if (pregunta) {
                void enviarMensaje(pregunta);
            }
        });
    });

    botonLimpiar?.addEventListener(
        "click",
        limpiarConversacion,
    );

    botonLeer?.addEventListener(
        "click",
        leerUltimaRespuesta,
    );

    window.addEventListener("beforeunload", () => {
        if ("speechSynthesis" in window) {
            window.speechSynthesis.cancel();
        }
    });

    actualizarContador();
    ajustarAlturaCampo();
    desplazarAlFinal();
});