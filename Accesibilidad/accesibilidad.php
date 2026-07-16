<!-- Archivo: Accesibilidad/accesibilidad.php -->
<div id="contenedor-barra-accesibilidad" class="barra-acc-oculta" role="region" aria-label="Barra de Accesibilidad">
    <div class="barra-acc-interna">
        <!-- Izquierda: Mensaje -->
        <div class="acc-mensaje">
            <div class="acc-icono-home"><i class="fa-solid fa-house-medical-flag"></i></div>
            <div class="acc-texto">
                <h4>Accesibilidad siempre disponible</h4>
                <p>Personaliza tu experiencia en cualquier momento.</p>
            </div>
        </div>

        <!-- Centro: Botones de opciones (Navegables con Teclado) -->
        <div class="acc-opciones-botones">
            <button class="acc-btn-opcion" id="acc-alto-contraste" type="button">
                <i class="fa-solid fa-eye"></i><span>Alto contraste</span>
            </button>
            <button class="acc-btn-opcion" id="acc-modo-oscuro" type="button">
                <i class="fa-solid fa-moon"></i><span>Modo oscuro</span>
            </button>
            <button class="acc-btn-opcion" id="acc-texto-grande" type="button">
                <i class="fa-solid fa-text-height"></i><span>Texto grande</span>
            </button>
            <button class="acc-btn-opcion" id="acc-leer-pantalla" type="button" aria-label="Función de lectura en desarrollo">
                <i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span>
            </button>
            <button class="acc-btn-opcion" id="acc-subtitulos" type="button" aria-label="Subtítulos en desarrollo">
                <i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span>
            </button>
            <button class="acc-btn-opcion" id="acc-teclado" type="button" aria-label="Navegación por teclado activada">
                <i class="fa-solid fa-keyboard"></i><span>Navegación por teclado</span>
            </button>
        </div>

        <!-- Derecha: Botón de configuración -->
        <div class="acc-config">
            <button class="btn-abrir-config" type="button">Abrir configuración</button>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- SECCIÓN DE ESTILOS UNIFICADOS (BARRA DE ACCESIBILIDAD FLOTANTE)  -->
<!-- ================================================================= -->
<style>
/* Posicionamiento flotante absoluto */
#contenedor-barra-accesibilidad {
    position: absolute;
    top: 90px; /* Se sitúa justo debajo del header */
    left: 0;
    width: 100%;
    padding: 10px 40px;
    background-color: var(--acc-bg-main, #ffffff);
    display: none;
    z-index: 9999; /* Por encima de todo el contenido */
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

#contenedor-barra-accesibilidad.mostrar-barra {
    display: block !important;
}

.barra-acc-interna {
    border: 1px solid var(--acc-border, #d1d5db);
    border-radius: 15px;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--acc-bg-inner, #ffffff);
}

/* Estilos de botones internos */
.acc-opciones-botones {
    display: flex;
    gap: 10px;
}

.acc-btn-opcion {
    background-color: var(--acc-bg-btn, #f3f4f6);
    border: 1px solid var(--acc-border-btn, #d1d5db);
    border-radius: 8px;
    width: 85px;
    height: 85px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 11px;
    color: var(--acc-text-btn, #111827);
    font-weight: 500;
    transition: all 0.2s ease;
}

.acc-btn-opcion:hover, 
.acc-btn-opcion:focus-visible {
    background-color: var(--acc-bg-btn-hover, #e5e7eb);
    outline: 3px solid #2563eb;
    outline-offset: 2px;
}

.acc-btn-opcion.activo {
    background-color: #2563eb !important;
    color: #ffffff !important;
    border-color: #1d4ed8 !important;
}
.acc-btn-opcion.activo i {
    color: #ffffff !important;
}

/* Ajustes dinámicos de Alto Contraste y Modo Oscuro usando variables en el HTML */
:root {
    --acc-text-size-base: 16px;
}

/* CLASE DE ALTO CONTRASTE */
.alto-contraste {
    background-color: #000000 !important;
    color: #ffff00 !important;
}
.alto-contraste * {
    background-color: transparent !important;
    color: #ffff00 !important;
    border-color: #ffff00 !important;
}
.alto-contraste button, .alto-contraste a {
    outline: 2px solid #ffff00 !important;
}

/* CLASE DE MODO OSCURO */
.modo-oscuro-activo {
    background-color: #121212 !important;
    color: #e0e0e0 !important;
}
.modo-oscuro-activo .main-header,
.modo-oscuro-activo .login-card,
.modo-oscuro-activo #contenedor-barra-accesibilidad {
    background-color: #1e1e1e !important;
    border-color: #333333 !important;
}
.modo-oscuro-activo p, .modo-oscuro-activo h1, .modo-oscuro-activo h2, .modo-oscuro-activo h3, .modo-oscuro-activo h4 {
    color: #ffffff !important;
}

/* CLASE DE TEXTO GRANDE */
.texto-grande-activo {
    font-size: 120% !important;
}
.texto-grande-activo p {
    font-size: 1.2rem !important;
}
.texto-grande-activo h1, .texto-grande-activo h2, .texto-grande-activo h3 {
    font-size: 2.2rem !important;
}
</style>

<!-- ================================================================= -->
<!-- SECCIÓN DE LÓGICA JAVASCRIPT (COMPORTAMIENTO DE LOS BOTONES)      -->
<!-- ================================================================= -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Referencias a los botones internos de la barra
    const btnAltoContraste = document.getElementById("acc-alto-contraste");
    const btnModoOscuro = document.getElementById("acc-modo-oscuro");
    const btnTextoGrande = document.getElementById("acc-texto-grande");
    const body = document.body;

    // 1. Lógica del Alto Contraste (Amarillo sobre Negro)
    if (btnAltoContraste) {
        btnAltoContraste.addEventListener("click", function() {
            body.classList.toggle("alto-contraste");
            btnAltoContraste.classList.toggle("activo");
            
            // Si activamos alto contraste, desactivamos modo oscuro para evitar conflictos visuales
            if (body.classList.contains("alto-contraste")) {
                body.classList.remove("modo-oscuro-activo");
                if (btnModoOscuro) btnModoOscuro.classList.remove("activo");
            }
        });
    }

    // 2. Lógica del Modo Oscuro
    if (btnModoOscuro) {
        btnModoOscuro.addEventListener("click", function() {
            body.classList.toggle("modo-oscuro-activo");
            btnModoOscuro.classList.toggle("activo");

            // Desactivar alto contraste si se activa modo oscuro
            if (body.classList.contains("modo-oscuro-activo")) {
                body.classList.remove("alto-contraste");
                if (btnAltoContraste) btnAltoContraste.classList.remove("activo");
            }
        });
    }

    // 3. Lógica del Tamaño de Letra (Texto Grande)
    if (btnTextoGrande) {
        btnTextoGrande.addEventListener("click", function() {
            body.classList.toggle("texto-grande-activo");
            btnTextoGrande.classList.toggle("activo");
        });
    }
});
</script>