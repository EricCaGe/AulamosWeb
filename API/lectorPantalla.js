// ======================================
// API - LECTOR DE PANTALLA
// ======================================

let lecturaActiva = false;
let mostrarSubtitulos = false;
let sintetizador = window.speechSynthesis;

// Activa el lector
function activarLectorPantalla() {

    lecturaActiva = true;

    // Cuando el lector se activa también activamos los subtítulos
    mostrarSubtitulos = true;

    leerContenido();
}

// Desactiva el lector
function desactivarLectorPantalla(){

    lecturaActiva = false;

    sintetizador.cancel();

    ocultarSubtitulos();
}

// Activa únicamente subtítulos
function activarSubtitulos(){

    mostrarSubtitulos = true;
}

// Desactiva únicamente subtítulos
function desactivarSubtitulos(){

    mostrarSubtitulos = false;

    ocultarSubtitulos();
}


// Lee toda la página
function leerContenido(){

    if(!lecturaActiva) return;

    sintetizador.cancel();

    let texto=document.body.innerText;

    let voz=new SpeechSynthesisUtterance(texto);

    voz.lang="es-MX";

    voz.rate=1;

    voz.pitch=1;

    voz.onstart=function(){

        mostrarTexto(texto);

    };

    voz.onend=function(){

        ocultarSubtitulos();

    };

    sintetizador.speak(voz);

}



// Muestra subtítulos
function mostrarTexto(texto){

    if(!mostrarSubtitulos) return;

    let caja=document.getElementById("subtitulosLectura");

    if(!caja) return;

    caja.innerHTML=texto;

    caja.style.display="block";

}



// Oculta subtítulos
function ocultarSubtitulos(){

    let caja=document.getElementById("subtitulosLectura");

    if(caja){

        caja.style.display="none";

    }

}