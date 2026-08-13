<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_actividad = intval($_GET['id'] ?? 0);
$id_alumno = $_SESSION['usuario']['id_usuario'];

if ($id_actividad <= 0) {
    header('Location: actividades.php');
    exit;
}

// Obtener datos de la evaluación
$sql = "
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.fecha_limite,
        a.configuracion_evaluacion,
        a.puntaje_maximo,
        ae.id_actividad_estudiante,
        ae.estado,
        ae.porcentaje_avance,
        m.nombre AS materia,
        c.nombre AS curso
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
    WHERE a.id_actividad = ? AND ae.id_alumno = ? AND a.tipo = 'Evaluacion'
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_actividad, $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = 'No tienes acceso a esta evaluación.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: actividades.php');
    exit;
}

$evaluacion = $result->fetch_assoc();
$stmt->close();

// Decodificar configuración de evaluación
$config = json_decode($evaluacion['configuracion_evaluacion'], true);
$preguntas = $config['preguntas'] ?? [];
$duracion = $config['duracion_minutos'] ?? 30;

// Verificar si ya hay entregas
$sql_entrega = "SELECT * FROM entregas WHERE id_actividad_estudiante = ?";
$stmt_entrega = $conexion->prepare($sql_entrega);
$stmt_entrega->bind_param("i", $evaluacion['id_actividad_estudiante']);
$stmt_entrega->execute();
$entrega = $stmt_entrega->get_result()->fetch_assoc();
$stmt_entrega->close();

$ya_respondida = !empty($entrega);

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Evaluación - Aulamos</title>
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    <style>
        .evaluacion-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        .header-evaluacion {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header-evaluacion h1 {
            margin: 0 0 10px 0;
            color: #1a1a2e;
            font-size: 24px;
        }
        .header-evaluacion .meta {
            color: #64748b;
            font-size: 14px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .header-evaluacion .meta i {
            margin-right: 5px;
            width: 16px;
        }
        .pregunta-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .pregunta-card .numero {
            color: #4f7cff;
            font-weight: 600;
            font-size: 14px;
        }
        .pregunta-card .texto {
            font-size: 16px;
            margin: 10px 0 15px 0;
            line-height: 1.6;
        }
        .opcion-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e8edf2;
        }
        .opcion-item:hover {
            background: #f8f9fa;
            border-color: #4f7cff;
        }
        .opcion-item input[type="radio"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #4f7cff;
            flex-shrink: 0;
        }
        .opcion-item .opcion-texto {
            flex: 1;
        }
        .respuesta-abierta textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            min-height: 100px;
            font-family: inherit;
            resize: vertical;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .respuesta-abierta textarea:focus {
            outline: none;
            border-color: #4f7cff;
            box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.1);
        }
        .btn-enviar {
            background: #4f7cff;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-enviar:hover:not(:disabled) {
            background: #3a6beb;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 124, 255, 0.3);
        }
        .btn-enviar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .timer {
            background: #fff3cd;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #856404;
            font-size: 16px;
        }
        .timer i {
            font-size: 18px;
        }
        .completada-msg {
            background: #d4edda;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: #155724;
        }
        .completada-msg i {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        .completada-msg h2 {
            margin: 0 0 10px 0;
        }
        .badge-estado {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-Pendiente { background: #fff3cd; color: #856404; }
        .badge-En_proceso { background: #cce5ff; color: #004085; }
        .badge-Completada { background: #d4edda; color: #155724; }
        .badge-Calificada { background: #e8d5f5; color: #6c3483; }
        .badge-Atrasada { background: #f8d7da; color: #721c24; }
        
        .volver-link {
            display: inline-block;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s;
        }
        .volver-link:hover {
            color: #4f7cff;
        }
        
        .progress-container {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .progress-container .info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }
        .progress-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            background: #4f7cff;
            border-radius: 4px;
            transition: width 0.3s;
        }

        /* Mensajes de alerta */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Inicio</a>
            <a href="actividades.php" class="menu-item active"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
        </nav>
        <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión
        </a>
    </aside>

    <main class="main-content">
        <div class="evaluacion-container">
            <!-- Mensajes de error -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'error'; ?>">
                    <i class="fa-solid <?php 
                        $tipo = $_SESSION['tipo_mensaje'] ?? 'error';
                        if ($tipo === 'success') echo 'fa-check-circle';
                        elseif ($tipo === 'error') echo 'fa-exclamation-circle';
                        else echo 'fa-info-circle';
                    ?>"></i>
                    <?php echo $_SESSION['mensaje']; ?>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
                <?php unset($_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>

            <?php if ($ya_respondida): ?>
                <div class="completada-msg">
                    <i class="fa-solid fa-check-circle"></i>
                    <h2>¡Evaluación completada!</h2>
                    <p>Ya has respondido esta evaluación. Espera la calificación del docente.</p>
                    <a href="actividades.php" class="btn-enviar" style="display: inline-block; margin-top: 15px; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Volver a actividades
                    </a>
                </div>
            <?php else: ?>
                <div class="header-evaluacion">
                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                        <div style="flex: 1;">
                            <h1><?= htmlspecialchars($evaluacion['titulo']) ?></h1>
                            <div class="meta">
                                <span><i class="fa-solid fa-book"></i> <?= htmlspecialchars($evaluacion['materia']) ?></span>
                                <span><i class="fa-solid fa-users"></i> <?= htmlspecialchars($evaluacion['curso']) ?></span>
                                <span><i class="fa-regular fa-clock"></i> Límite: <?= date('d/m/Y H:i', strtotime($evaluacion['fecha_limite'])) ?></span>
                                <span><i class="fa-solid fa-star"></i> Puntaje máximo: <?= $evaluacion['puntaje_maximo'] ?></span>
                                <span><i class="fa-regular fa-file-lines"></i> <?= count($preguntas) ?> preguntas</span>
                            </div>
                        </div>
                        <div class="timer" id="timer">
                            <i class="fa-regular fa-hourglass"></i> 
                            <span id="tiempo_restante"><?= $duracion * 60 ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span class="badge-estado badge-<?= str_replace('_', '', $evaluacion['estado']) ?>">
                            <?= $evaluacion['estado'] ?>
                        </span>
                        <span style="font-size: 13px; color: #666;">
                            <i class="fa-regular fa-circle-check"></i> 
                            <?= $evaluacion['porcentaje_avance'] ?>% completado
                        </span>
                    </div>
                </div>

                <form id="evaluacionForm" action="guardar_respuestas_evaluacion.php" method="POST">
                    <input type="hidden" name="id_actividad" value="<?= $evaluacion['id_actividad'] ?>">
                    <input type="hidden" name="id_actividad_estudiante" value="<?= $evaluacion['id_actividad_estudiante'] ?>">
                    <input type="hidden" name="tiempo_empleado" id="tiempo_empleado" value="0">

                    <div id="preguntasContainer">
                        <?php foreach ($preguntas as $idx => $pregunta): ?>
                            <div class="pregunta-card" data-pregunta="<?= $idx ?>">
                                <div class="numero">
                                    Pregunta <?= $idx + 1 ?> 
                                    <?php if (isset($pregunta['obligatoria']) && $pregunta['obligatoria']): ?>
                                        <span style="color: red;">*</span>
                                    <?php endif; ?>
                                    <span style="float: right; color: #666; font-weight: normal;">
                                        <i class="fa-regular fa-star"></i> <?= $pregunta['puntaje'] ?? 1 ?> pts
                                    </span>
                                </div>
                                <div class="texto"><?= htmlspecialchars($pregunta['texto']) ?></div>
                                
                                <div style="font-size: 13px; color: #666; margin-bottom: 10px;">
                                    <i class="fa-regular fa-circle"></i> 
                                    <?php 
                                    $tipo_label = [
                                        'OpcionMultiple' => 'Opción múltiple',
                                        'VerdaderoFalso' => 'Verdadero / Falso',
                                        'Abierta' => 'Respuesta abierta'
                                    ];
                                    echo $tipo_label[$pregunta['tipo']] ?? $pregunta['tipo'];
                                    ?>
                                </div>

                                <?php if ($pregunta['tipo'] === 'Abierta'): ?>
                                    <div class="respuesta-abierta">
                                        <textarea 
                                            name="respuestas[<?= $idx ?>]" 
                                            placeholder="Escribe tu respuesta detallada aquí..." 
                                            rows="4"
                                            <?= isset($pregunta['obligatoria']) && $pregunta['obligatoria'] ? 'required' : '' ?>
                                        ></textarea>
                                    </div>
                                <?php elseif ($pregunta['tipo'] === 'VerdaderoFalso'): ?>
                                    <?php 
                                    $opciones = $pregunta['opciones'] ?? ['Verdadero', 'Falso'];
                                    ?>
                                    <?php foreach ($opciones as $opIdx => $opcion): ?>
                                        <label class="opcion-item">
                                            <input type="radio" 
                                                   name="respuestas[<?= $idx ?>]" 
                                                   value="<?= $opIdx ?>" 
                                                   <?= isset($pregunta['obligatoria']) && $pregunta['obligatoria'] ? 'required' : '' ?>>
                                            <span class="opcion-texto"><?= htmlspecialchars($opcion) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php 
                                    $opciones = $pregunta['opciones'] ?? [];
                                    ?>
                                    <?php foreach ($opciones as $opIdx => $opcion): ?>
                                        <label class="opcion-item">
                                            <input type="radio" 
                                                   name="respuestas[<?= $idx ?>]" 
                                                   value="<?= $opIdx ?>"
                                                   <?= isset($pregunta['obligatoria']) && $pregunta['obligatoria'] ? 'required' : '' ?>>
                                            <span class="opcion-texto"><?= htmlspecialchars($opcion) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="progress-container">
                        <div class="info">
                            <span><i class="fa-regular fa-circle-check"></i> Progreso</span>
                            <span id="progresoTexto">0 / <?= count($preguntas) ?> respondidas</span>
                        </div>
                        <div class="progress-bar">
                            <div class="fill" id="progresoBar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn-enviar" id="btnEnviar">
                            <i class="fa-solid fa-paper-plane"></i> Entregar evaluación
                        </button>
                        <br>
                        <a href="actividades.php" class="volver-link">
                            <i class="fa-solid fa-arrow-left"></i> Volver sin entregar
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Accesibilidad -->
<?php include '../Accesibilidad/accesibilidad.php'; ?>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$ya_respondida): ?>
    // Temporizador
    let tiempoRestante = parseInt(document.getElementById('tiempo_restante').textContent);
    const timerDisplay = document.getElementById('tiempo_restante');
    const tiempoEmpleado = document.getElementById('tiempo_empleado');
    let tiempoInicio = Date.now();

    const timerInterval = setInterval(() => {
        tiempoRestante--;
        if (tiempoRestante <= 0) {
            clearInterval(timerInterval);
            timerDisplay.textContent = '0:00';
            // Auto-enviar cuando se acabe el tiempo
            document.getElementById('evaluacionForm').submit();
        } else {
            const mins = Math.floor(tiempoRestante / 60);
            const segs = tiempoRestante % 60;
            timerDisplay.textContent = `${mins}:${segs.toString().padStart(2, '0')}`;
            
            // Cambiar color cuando queden pocos minutos
            if (tiempoRestante < 60) {
                timerDisplay.style.color = '#dc3545';
            }
        }
    }, 1000);

    // Contador de respuestas
    function actualizarProgreso() {
        const preguntas = document.querySelectorAll('.pregunta-card');
        let respondidas = 0;
        
        preguntas.forEach(pregunta => {
            const radio = pregunta.querySelector('input[type="radio"]:checked');
            const textarea = pregunta.querySelector('textarea');
            if (radio || (textarea && textarea.value.trim() !== '')) {
                respondidas++;
            }
        });
        
        const total = preguntas.length;
        const porcentaje = total > 0 ? (respondidas / total) * 100 : 0;
        
        document.getElementById('progresoTexto').textContent = `${respondidas} / ${total} respondidas`;
        document.getElementById('progresoBar').style.width = `${porcentaje}%`;
    }

    // Event listeners para actualizar progreso
    document.querySelectorAll('input[type="radio"], textarea').forEach(el => {
        el.addEventListener('change', actualizarProgreso);
        el.addEventListener('input', actualizarProgreso);
    });

    // Validar y enviar formulario
    document.getElementById('evaluacionForm').addEventListener('submit', function(e) {
        const preguntas = document.querySelectorAll('.pregunta-card');
        let todasRespondidas = true;
        let preguntasFaltantes = [];
        
        // Resetear estilos
        preguntas.forEach(p => {
            p.style.border = 'none';
            p.style.padding = '20px';
        });
        
        preguntas.forEach((pregunta, index) => {
            const required = pregunta.querySelector('[required]');
            if (required) {
                const radio = pregunta.querySelector('input[type="radio"]:checked');
                const textarea = pregunta.querySelector('textarea');
                if (!radio && (!textarea || textarea.value.trim() === '')) {
                    todasRespondidas = false;
                    preguntasFaltantes.push(index + 1);
                    pregunta.style.border = '2px solid #dc3545';
                    pregunta.style.borderRadius = '8px';
                    pregunta.style.padding = '18px';
                }
            }
        });
        
        if (!todasRespondidas) {
            e.preventDefault();
            alert('⚠️ Por favor, responde todas las preguntas obligatorias antes de entregar.\n\nFaltan las preguntas: ' + preguntasFaltantes.join(', '));
            return false;
        }
        
        // Confirmar entrega
        if (!confirm('¿Estás seguro de que deseas entregar la evaluación?\n\nUna vez entregada NO podrás modificarla.')) {
            e.preventDefault();
            return false;
        }
        
        // Calcular tiempo empleado
        const tiempoEmpleadoMs = Date.now() - tiempoInicio;
        const tiempoEmpleadoSeg = Math.floor(tiempoEmpleadoMs / 1000);
        document.getElementById('tiempo_empleado').value = tiempoEmpleadoSeg;
        
        // Mostrar estado de carga
        const btnEnviar = document.getElementById('btnEnviar');
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
    });

    // Actualizar progreso inicial
    actualizarProgreso();
    <?php endif; ?>
});
</script>

<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>