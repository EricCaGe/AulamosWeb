<!-- BARRA FLOTANTE DE ACCESIBILIDAD -->
<div id="barra-accesibilidad" class="accesibilidad-bar accesibilidad-hidden">
  <div class="acc-info">
    <div class="acc-icon-main">
      <i class="fa-solid fa-universal-access"></i>
    </div>
    <div class="acc-text">
      <strong>Accesibilidad siempre disponible</strong>
      <span>Personaliza tu experiencia en cualquier momento.</span>
    </div>
  </div>

  <div class="acc-buttons">
    <button class="acc-btn" onclick="toggleAltoContraste()">
      <i class="fa-regular fa-eye"></i>
      <span>Alto contraste</span>
    </button>
    <button class="acc-btn" onclick="toggleModoOscuro()">
      <i class="fa-solid fa-moon"></i>
      <span>Modo oscuro</span>
    </button>
    <button class="acc-btn" onclick="abrirModalTexto()">
      <span class="acc-text-icon">Tamaño</span>
      <span>Texto</span>
    </button>
    <button class="acc-btn" onclick="abrirModalContraste()">
      <i class="fa-solid fa-palette"></i>
      <span>Configurar contraste</span>
    </button>
    <button class="acc-btn" id="btnLectorPantalla">
      <i class="fa-solid fa-volume-high"></i>
      <span>Leer pantalla</span>
    </button>
    <button class="acc-btn" onclick="toggleSubtitulos()">
      <i class="fa-solid fa-closed-captioning"></i>
      <span>Subtítulos</span>
    </button>
    <button class="acc-btn" onclick="toggleNavegacionTeclado()">
      <i class="fa-solid fa-keyboard"></i>
      <span>Navegación por teclado</span>
    </button>
  </div>

  <div class="acc-action">
    <button class="acc-btn-primary" onclick="abrirConfiguracion()">Abrir configuración</button>
  </div>
</div>

<!-- MODAL DE CONFIGURACIÓN DE CONTRASTE -->
<div id="modal-contraste" class="modal-contraste-overlay modal-contraste-hidden">
  <div class="modal-contraste-container">
    <div class="modal-contraste-header">
      <h3><i class="fa-solid fa-sliders"></i> Configurar Alto Contraste</h3>
      <button class="btn-cerrar-modal" onclick="cerrarModalContraste()">&times;</button>
    </div>
    
    <div class="modal-contraste-body">
      <!-- Fondo -->
      <div class="opcion-grupo">
        <label>Color de Fondo:</label>
        <div class="opciones-botones">
          <button class="btn-opcion" data-grupo="fondo" data-fondo="negro" onclick="seleccionarBoton(this)">Negro</button>
          <button class="btn-opcion" data-grupo="fondo" data-fondo="blanco" onclick="seleccionarBoton(this)">Blanco</button>
        </div>
      </div>

      <!-- Color de Bordes -->
      <div class="opcion-grupo">
        <label>Color de Contornos (Botones, Inputs, Formularios):</label>
        <div class="opciones-botones">
          <button class="btn-opcion" data-grupo="borde" data-color="amarillo" onclick="seleccionarBoton(this)">Amarillo</button>
          <button class="btn-opcion" data-grupo="borde" data-color="azul" onclick="seleccionarBoton(this)">Azul</button>
          <button class="btn-opcion" data-grupo="borde" data-color="verde" onclick="seleccionarBoton(this)">Verde</button>
          <button class="btn-opcion" data-grupo="borde" data-color="rojo" onclick="seleccionarBoton(this)">Rojo</button>
        </div>
      </div>
    </div>

    <div class="modal-contraste-footer">
      <button class="btn-restablecer" onclick="limpiarSeleccionesVisuales()">Desactivar Contraste</button>
      <button class="acc-btn-primary" onclick="aplicarPersonalizacion()">Aplicar y Cerrar</button>
    </div>
  </div>
</div>

<!-- MODAL DE TAMAÑO DE TEXTO -->
<div id="modalTexto" class="modal-contraste-overlay modal-contraste-hidden">
  <div class="modal-contraste-container">
    <div class="modal-contraste-header">
      <h3><i class="fa-solid fa-text-height"></i> Tamaño de texto</h3>
      <button class="btn-cerrar-modal" onclick="cerrarModalTexto()">&times;</button>
    </div>
    <div class="modal-contraste-body">
      <div class="opcion-grupo">
        <label>Selecciona el tamaño de texto:</label>
        <div class="opciones-botones" id="opcionesTexto">
          <button class="btn-opcion seleccionado" data-tamano="normal" onclick="seleccionarTamano(this)">Normal</button>
          <button class="btn-opcion" data-tamano="grande" onclick="seleccionarTamano(this)">Grande</button>
          <button class="btn-opcion" data-tamano="muy_grande" onclick="seleccionarTamano(this)">Muy Grande</button>
        </div>
      </div>
    </div>
    <div class="modal-contraste-footer">
      <button class="btn-restablecer" onclick="limpiarTamanoTexto()">Restablecer</button>
      <button class="acc-btn-primary" onclick="aplicarTamanoTexto()">Aplicar</button>
    </div>
  </div>
</div>