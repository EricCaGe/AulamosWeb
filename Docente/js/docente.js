document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. CONTROL DEL ASISTENTE VIRTUAL (CHATBOT)
    // ==========================================
    const btnAsistente = document.getElementById("btnOpenAssistant");
    const chatbot = document.getElementById("chatbot");
    const btnCloseChat = document.getElementById("btnCloseChat");
    const chatInput = document.querySelector(".chatbot-footer input");
    const chatBtnSend = document.querySelector(".btn-send");
    const chatBody = document.querySelector(".chatbot-body");

    if (btnAsistente && chatbot) {
        btnAsistente.addEventListener("click", () => {
            chatbot.style.display = chatbot.style.display === "flex" ? "none" : "flex";
        });

        if (btnCloseChat) {
            btnCloseChat.addEventListener("click", () => {
                chatbot.style.display = "none";
            });
        }

        const enviarMensaje = () => {
            const texto = chatInput.value.trim();
            if (texto === "") return;

            const userMsg = document.createElement("div");
            userMsg.className = "msg user";
            userMsg.textContent = texto;
            chatBody.appendChild(userMsg);

            chatInput.value = "";
            chatBody.scrollTop = chatBody.scrollHeight;

            setTimeout(() => {
                const botMsg = document.createElement("div");
                botMsg.className = "msg bot";
                botMsg.innerHTML = `Recibí tu pregunta: "<strong>${texto}</strong>". Estoy aquí para ayudarte a navegar en tu panel docente.`;
                chatBody.appendChild(botMsg);
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 1000);
        };

        chatBtnSend.addEventListener("click", enviarMensaje);
        chatInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") enviarMensaje();
        });
    }

    // ==========================================
    // 2. CONTROL DEL DESLIZADOR DE TEXTO (NUEVO)
    // ==========================================
    const fontSizeSlider = document.getElementById("fontSizeSlider");
    const fontSizeVal = document.getElementById("fontSizeVal");

    if (fontSizeSlider) {
        fontSizeSlider.addEventListener("input", (e) => {
            const size = e.target.value;
            // Modifica directamente el tamaño de raíz del documento de manera proporcional
            document.documentElement.style.fontSize = `${size}px`;
            if (fontSizeVal) {
                fontSizeVal.textContent = `${size}px`;
            }
        });
    }

    // ==========================================
    // 3. INTERACTIVIDAD DE ACCESIBILIDAD
    // ==========================================
    const accButtons = document.querySelectorAll(".acc-option-btn");

    accButtons.forEach(button => {
        button.addEventListener("click", () => {
            const textoBoton = button.textContent.toLowerCase();

            // Opción: Alto Contraste
            if (textoBoton.includes("alto contraste")) {
                document.body.classList.toggle("high-contrast");
                button.classList.toggle("active-acc");
            }
            
            // Opción: Modo Oscuro
            else if (textoBoton.includes("modo oscuro")) {
                document.body.classList.toggle("dark-mode");
                button.classList.toggle("active-acc");
            }

            // Opción: Leer Pantalla (Voz)
            else if (textoBoton.includes("leer pantalla")) {
                const activo = document.body.classList.toggle("screen-reader-active");
                button.classList.toggle("active-acc");
                
                if (activo) {
                    speak("Lector de pantalla activado. Pasa el cursor sobre cualquier texto para escucharlo.");
                    activarLectorDePantalla();
                } else {
                    speak("Lector de pantalla desactivado.");
                    desactivarLectorDePantalla();
                }
            }
        });
    });

    // ==========================================
    // 4. MOTOR DE TEXT-TO-SPEECH (LEER PANTALLA)
    // ==========================================
    function speak(text) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'es-ES';
        window.speechSynthesis.speak(utterance);
    }

    function leerElemento(e) {
        const texto = e.target.innerText || e.target.alt;
        if (texto) {
            speak(texto);
        }
    }

    function activarLectorDePantalla() {
        const elementosLectura = document.querySelectorAll("h1, h2, h3, p, span, .nav-link, button");
        elementosLectura.forEach(el => {
            el.addEventListener("mouseenter", leerElemento);
        });
    }

    function desactivarLectorDePantalla() {
        const elementosLectura = document.querySelectorAll("h1, h2, h3, p, span, .nav-link, button");
        elementosLectura.forEach(el => {
            el.removeEventListener("mouseenter", leerElemento);
        });
        window.speechSynthesis.cancel();
    }
});