// ==========================================
// TECLADO VIRTUAL CON NAVEGACIÓN
// ==========================================

class TecladoVirtual {

    constructor() {
        this.keyboard = null;
        this.activado = false;
        this.inputActual = null;
        this.inicializar();
    }

    inicializar() {
        this.activado = localStorage.getItem("navegacion_teclado") === "true";
        this.crearTeclado();
        if(this.activado){
            this.activar();
        }
    }

    crearTeclado() {
        const contenedor = document.getElementById("simpleKeyboard");
        if (!contenedor) {
            console.warn("⚠️ No se encontró el elemento #simpleKeyboard.");
            return; 
        }

        // Seguridad: Elegir la clase correcta (con .default o sin él)
        const KeyboardClass = window.SimpleKeyboard.default || window.SimpleKeyboard;

        if (!KeyboardClass) {
            console.error("❌ SimpleKeyboard no está disponible.");
            return;
        }

        this.keyboard = new KeyboardClass({
            onChange: input => this.escribir(input),
            onKeyPress: button => this.tecla(button),
            layout: {
                default: [
                    "1 2 3 4 5 6 7 8 9 0 {bksp}",
                    "q w e r t y u i o p",
                    "a s d f g h j k l ñ",
                    "{shift} z x c v b n m . ,",
                    "{tab} {arrowup} {arrowdown} {arrowleft} {arrowright} {space} {enter}"
                ],
                shift: [
                    "! @ # $ % ^ & * ( ) {bksp}",
                    "Q W E R T Y U I O P",
                    "A S D F G H J K L Ñ",
                    "{shift} Z X C V B N M : ;",
                    "{tab} {arrowup} {arrowdown} {arrowleft} {arrowright} {space} {enter}"
                ]
            },
            display: {
                "{bksp}": "⌫",
                "{enter}": "Enter",
                "{shift}": "⇧",
                "{space}": "Espacio",
                "{tab}": "Tab ↹",
                "{arrowup}": "↑",
                "{arrowdown}": "↓",
                "{arrowleft}": "←",
                "{arrowright}": "→"
            },
            theme: "hg-theme-default hg-layout-default"
        });

        contenedor.style.display = "none";
    }

    activar() {
        this.activado = true;
        localStorage.setItem("navegacion_teclado", "true");
        this.detectarInputs();

        const padre = document.getElementById("virtual-keyboard-container");
        if(padre) padre.style.display = "block";
        console.log("⌨️ Teclado activado");
    }

    desactivar() {
        this.activado = false;
        localStorage.setItem("navegacion_teclado", "false");

        const padre = document.getElementById("virtual-keyboard-container");
        if(padre) padre.style.display = "none";
        console.log("⌨️ Teclado desactivado");
    }

    toggle(){
        if(this.activado){
            this.desactivar();
        }else{
            this.activar();
        }
    }

    detectarInputs() {
        document.querySelectorAll("input, textarea").forEach(input => {
            input.addEventListener("focus", () => {
                this.inputActual = input;
                if (this.activado) {
                    const padre = document.getElementById("virtual-keyboard-container");
                    if(padre) padre.style.display = "block";
                    if (this.keyboard) {
                        this.keyboard.setInput(input.value);
                    }
                }
            });

            input.addEventListener("blur", () => {
                setTimeout(() => {
                    if (this.activado) {
                        const padre = document.getElementById("virtual-keyboard-container");
                        if(padre) padre.style.display = "none";
                    }
                }, 300);
            });
        });
    }

    escribir(texto){
        if(!this.inputActual) return;
        this.inputActual.value = texto;
        this.inputActual.dispatchEvent(new Event("input"));
    }

    tecla(button) {
        // 1. Lógica para el botón Shift (Mayúsculas)
        if (button === "{shift}") {
            const actual = this.keyboard.options.layoutName || "default";
            this.keyboard.setOptions({
                layoutName: actual === "default" ? "shift" : "default"
            });
            return;
        }

        // 2. Lógica para Teclas de Navegación (Flechas y Tabulador)
        const teclasNavegacion = ["{tab}", "{arrowup}", "{arrowdown}", "{arrowleft}", "{arrowright}"];
        if (teclasNavegacion.includes(button)) {
            // Extraemos el nombre de la tecla sin las llaves (ej: "arrowup")
            const keyName = button.replace(/{|}/g, '');
            
            // Creamos un evento de teclado real y lo enviamos al documento
            const event = new KeyboardEvent('keydown', {
                key: keyName,
                code: keyName,
                bubbles: true,
                cancelable: true
            });

            // Disparamos el evento en el elemento activo o en el documento global
            if (document.activeElement) {
                document.activeElement.dispatchEvent(event);
            } else {
                document.dispatchEvent(event);
            }
        }
    }
}

let teclado = null;
document.addEventListener("DOMContentLoaded", () => {
    teclado = new TecladoVirtual();
    window.teclado = teclado;
});