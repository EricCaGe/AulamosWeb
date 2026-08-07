<?php
session_start();
require_once '../Conexion/conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];

// =============================================
// 1. OBTENER PREFERENCIAS ACTUALES
// =============================================
$sql = "SELECT * FROM preferencias_accesibilidad WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$preferencias = $result->fetch_assoc();

// Si no tiene preferencias, insertar valores predeterminados
if (!$preferencias) {
    $sqlInsert = "INSERT INTO preferencias_accesibilidad (id_usuario) VALUES (?)";
    $stmtInsert = $conexion->prepare($sqlInsert);
    $stmtInsert->bind_param("i", $id_usuario);
    $stmtInsert->execute();
    
    // Volver a consultar
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $preferencias = $result->fetch_assoc();
}

// Mapeo de valores booleanos a texto para mostrar
$boolMap = [
    '0' => 'No',
    '1' => 'Sí'
];

// Mapeo de tamaños de texto
$textSizeMap = [
    'Pequeño' => 'Pequeño',
    'Normal' => 'Normal',
    'Grande' => 'Grande',
    'Muy Grande' => 'Muy Grande'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesibilidad - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/Accesibilidad.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="accesibilidad.php" class="menu-item active"><i class="fa-solid fa-gear"></i> Accesibilidad</a>
        </nav>
        
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1><i class="fa-solid fa-universal-access"></i> Accesibilidad</h1>
                <p>Personaliza tu experiencia de aprendizaje.</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- OPCIONES DE ACCESIBILIDAD -->
        <div class="accesibilidad-options-grid">
            
            <!-- Alto contraste -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-eye"></i></div>
                    <div class="option-info">
                        <h3>Alto contraste</h3>
                        <p>Mejora la visibilidad de textos y elementos</p>
                    </div>
                </div>
                <div class="option-control">
                    <button class="toggle-btn <?= $preferencias['alto_contraste'] ? 'active' : '' ?>" 
                            data-pref="alto_contraste" 
                            data-value="<?= $preferencias['alto_contraste'] ?>">
                        <span class="toggle-track">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span class="toggle-label"><?= $boolMap[$preferencias['alto_contraste']] ?></span>
                    </button>
                </div>
            </div>

            <!-- Modo oscuro -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-moon"></i></div>
                    <div class="option-info">
                        <h3>Modo oscuro</h3>
                        <p>Cambia a un tema oscuro para reducir el brillo</p>
                    </div>
                </div>
                <div class="option-control">
                    <button class="toggle-btn <?= $preferencias['modo_oscuro'] ? 'active' : '' ?>" 
                            data-pref="modo_oscuro" 
                            data-value="<?= $preferencias['modo_oscuro'] ?>">
                        <span class="toggle-track">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span class="toggle-label"><?= $boolMap[$preferencias['modo_oscuro']] ?></span>
                    </button>
                </div>
            </div>

            <!-- Texto grande -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-font"></i></div>
                    <div class="option-info">
                        <h3>Texto grande</h3>
                        <p>Aumenta el tamaño del texto en toda la plataforma</p>
                    </div>
                </div>
                <div class="option-control">
                    <select class="select-pref" data-pref="tamano_texto">
                        <?php foreach ($textSizeMap as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $preferencias['tamano_texto'] === $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Leer pantalla -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-volume-high"></i></div>
                    <div class="option-info">
                        <h3>Leer pantalla</h3>
                        <p>Escucha el contenido en voz alta</p>
                    </div>
                </div>
                <div class="option-control">
                    <button class="toggle-btn <?= $preferencias['lector_pantalla'] ? 'active' : '' ?>" 
                            data-pref="lector_pantalla" 
                            data-value="<?= $preferencias['lector_pantalla'] ?>">
                        <span class="toggle-track">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span class="toggle-label"><?= $boolMap[$preferencias['lector_pantalla']] ?></span>
                    </button>
                </div>
            </div>

            <!-- Subtítulos -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-closed-captioning"></i></div>
                    <div class="option-info">
                        <h3>Subtítulos</h3>
                        <p>Muestra subtítulos en vídeos y audios</p>
                    </div>
                </div>
                <div class="option-control">
                    <button class="toggle-btn <?= $preferencias['subtitulos'] ? 'active' : '' ?>" 
                            data-pref="subtitulos" 
                            data-value="<?= $preferencias['subtitulos'] ?>">
                        <span class="toggle-track">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span class="toggle-label"><?= $boolMap[$preferencias['subtitulos']] ?></span>
                    </button>
                </div>
            </div>

            <!-- Navegación por teclado -->
            <div class="accesibilidad-option">
                <div class="option-header">
                    <div class="option-icon"><i class="fa-solid fa-keyboard"></i></div>
                    <div class="option-info">
                        <h3>Navegación por teclado</h3>
                        <p>Navega por la plataforma usando solo el teclado</p>
                    </div>
                </div>
                <div class="option-control">
                    <button class="toggle-btn <?= $preferencias['navegacion_teclado'] ? 'active' : '' ?>" 
                            data-pref="navegacion_teclado" 
                            data-value="<?= $preferencias['navegacion_teclado'] ?>">
                        <span class="toggle-track">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span class="toggle-label"><?= $boolMap[$preferencias['navegacion_teclado']] ?></span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Botón restablecer -->
        <div class="reset-section">
            <button class="btn-reset" id="btnReset">
                <i class="fa-solid fa-rotate-left"></i> Restablecer configuración
            </button>
        </div>

        <!-- ACCESIBILIDAD (la misma barra de accesibilidad) -->
        <footer class="accessibility-bar">
            <div class="acc-info">
                <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                <div>
                    <strong>Accesibilidad siempre disponible</strong>
                    <p>Personaliza tu experiencia en cualquier momento.</p>
                </div>
            </div>
            <div class="acc-options">
                <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                <button class="acc-opt-btn" id="btn-text-size"><i class="fa-solid fa-font"></i><span>Texto grande</span></button>
                 <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                <button class="acc-opt-btn" id="btn-subtitulos"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                <button class="acc-opt-btn" id="btn-navegacion"><i class="fa-solid fa-keyboard"></i><span>Navegación</span></button>
            </div>
            <button class="btn-open-config" id="btn-config">Abrir configuración</button>
        </footer>

    </main>
</div>

<!-- 1. CSS del teclado virtual -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-keyboard@3.7.79/build/css/index.css">

<!-- 2. El contenedor del teclado virtual (Ahora existe antes de que el JS lo busque) -->
<div id="virtual-keyboard-container" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #f8fafc; z-index: 9999; padding: 10px 0 20px 0; box-shadow: 0 -4px 12px rgba(0,0,0,0.15); border-top: 2px solid #3b82f6;">
    <div class="simple-keyboard"></div>
</div>

<!-- 3. JS de la librería del teclado -->
 <script>
// Inyectamos el CSS directamente para que no dependa de internet
var style = document.createElement('style');
style.innerHTML = `
    #virtual-keyboard-container {
        display: none; position: fixed; bottom: 0; left: 0; width: 100%;
        background-color: #e9ecef; z-index: 10000; padding: 10px 0 20px 0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1); border-top: 2px solid #3b82f6;
    }
    .hg-row { display: flex; justify-content: center; gap: 4px; margin-bottom: 4px; }
    .hg-button {
        background-color: #ffffff; border: 1px solid #ced4da; border-radius: 6px;
        font-size: 1rem; color: #212529; height: 44px; min-width: 36px;
        padding: 0 8px; cursor: pointer; display: flex; align-items: center;
        justify-content: center; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .hg-button:hover { background-color: #f8f9fa; border-color: #adb5bd; }
    .hg-button-special { min-width: 60px; background-color: #f1f3f5; }
    body.modo-oscuro #virtual-keyboard-container { background-color: #1e1e32; border-top: 2px solid #4f46e5; }
    body.modo-oscuro .hg-button { background-color: #2d2d44; border-color: #4a4a6a; color: #e2e8f0; }
    body.modo-oscuro .hg-button:hover { background-color: #3d3d5a; }
`;
document.head.appendChild(style);

// RESPALDO: Dibuja el teclado si la librería externa falló
if (typeof SimpleKeyboard === 'undefined') {
    window.SimpleKeyboard = function(config) {
        var container = document.querySelector('.simple-keyboard');
        if (!container) return;

        var layout = ['1 2 3 4 5 6 7 8 9 0', 'q w e r t y u i o p', 'a s d f g h j k l ñ', 'z x c v b n m', '{bksp} {space} {enter}'];
        
        function renderKeyboard() {
            var html = '';
            layout.forEach(function(row) {
                html += '<div class="hg-row">';
                var keys = row.split(' ');
                keys.forEach(function(key) {
                    var special = (key === '{bksp}' || key === '{space}' || key === '{enter}') ? 'hg-button-special' : '';
                    var displayKey = key;
                    if(key === '{bksp}') displayKey = '⌫';
                    if(key === '{space}') displayKey = 'Espacio';
                    if(key === '{enter}') displayKey = '↵ Enter';
                    html += `<button class="hg-button ${special}" data-key="${key}">${displayKey}</button>`;
                });
                html += '</div>';
            });
            container.innerHTML = html;

            container.querySelectorAll('.hg-button').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var key = this.dataset.key;
                    var input = document.activeElement;
                    
                    if (key === '{bksp}') {
                        if(input) { input.value = input.value.slice(0, -1); input.dispatchEvent(new Event('input', { bubbles: true })); }
                    } else if (key === '{space}') {
                        if(input) { input.value += ' '; input.dispatchEvent(new Event('input', { bubbles: true })); }
                    } else if (key === '{enter}') {
                        console.log("Tecla Enter presionada");
                    } else {
                        if(input && (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA')) {
                            input.value += key;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                });
            });
        }
        renderKeyboard();
    };
}
</script>

<!-- El contenedor donde se dibujará el teclado -->
<div id="virtual-keyboard-container">
    <div class="simple-keyboard"></div>
</div>


<?php include '../API/teclado_accesibilidad.php'; ?>
<script src="js/navegacionTeclado.js"></script>
<script src="js/Inicio.js"></script>
<script src="js/Accesibilidad.js"></script>
<script src="./InicioSesion/js/lector.js"></script>
<script src="../Administrador/js/lector.js"></script>

</body>
</html>