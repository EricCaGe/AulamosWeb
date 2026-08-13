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

// Obtener datos de la evaluación y la entrega
$sql = "
    SELECT 
        a.titulo,
        a.descripcion,
        a.puntaje_maximo,
        a.configuracion_evaluacion,
        m.nombre AS materia,
        e.calificacion,
        e.respuestas_evaluacion,
        e.retroalimentacion,
        e.estado AS estado_entrega,
        e.fecha_entrega,
        e.tiempo_realizacion,
        ae.estado,
        (SELECT COUNT(*) FROM entregas WHERE id_actividad_estudiante = ae.id_actividad_estudiante) AS tiene_entrega
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    WHERE a.id_actividad = ? AND ae.id_alumno = ? AND a.tipo = 'Evaluacion'
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_actividad, $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'No tienes acceso a esta evaluación.';
    header('Location: actividades.php');
    exit;
}

$evaluacion = $result->fetch_assoc();
$stmt->close();

// Decodificar configuración y respuestas
$config = json_decode($evaluacion['configuracion_evaluacion'], true);
$preguntas = $config['preguntas'] ?? [];
$respuestas_alumno = json_decode($evaluacion['respuestas_evaluacion'] ?? '{}', true);

// Calcular estadísticas
$total_preguntas = count($preguntas);
$respondidas = count($respuestas_alumno);
$calificacion = $evaluacion['calificacion'];

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Evaluación - Aulamos</title>
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    <style>
        .resultado-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        .header-resultado {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header-resultado h1 {
            margin: 0 0 10px 0;
            color: #1a1a2e;
        }
        .calificacion-big {
            font-size: 48px;
            font-weight: 700;
            color: #4f7cff;
            margin: 10px 0;
        }
        .calificacion-big .max {
            font-size: 24px;
            color: #64748b;
            font-weight: 400;
        }
        .meta-info {
            color: #64748b;
            font-size: 14px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .respuesta-review {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .respuesta-review .correcta {
            color: #2ecc71;
            font-weight: 600;
        }
        .respuesta-review .incorrecta {
            color: #e74c3c;
            font-weight: 600;
        }
        .respuesta-review .tu-respuesta {
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin: 5px 0;
        }
        .badge-calificacion {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .badge-aprobado {
            background: #d4edda;
            color: #155724;
        }
        .badge-reprobado {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-volver {
            background: #4f7cff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-volver:hover {
            background: #3a6beb;
            transform: translateY(-2px);
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
        <div class="resultado-container">
            <div class="header-resultado">
                <h1><?= htmlspecialchars($evaluacion['titulo']) ?></h1>
                <div class="meta-info">
                    <span><i class="fa-solid fa-book"></i> <?= htmlspecialchars($evaluacion['materia']) ?></span>
                    <span><i class="fa-regular fa-calendar"></i> Entregado: <?= date('d/m/Y H:i', strtotime($evaluacion['fecha_entrega'])) ?></span>
                    <?php if ($evaluacion['tiempo_realizacion']): ?>
                        <span><i class="fa-regular fa-clock"></i> Tiempo: <?= floor($evaluacion['tiempo_realizacion'] / 60) ?> min <?= $evaluacion['tiempo_realizacion'] % 60 ?> seg</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($evaluacion['calificacion'] !== null): ?>
                    <div class="calificacion-big">
                        <?= number_format($evaluacion['calificacion'], 1) ?>
                        <span class="max">/ <?= number_format($evaluacion['puntaje_maximo'], 1) ?></span>
                    </div>
                    <div>
                        <span class="badge-calificacion <?= $evaluacion['calificacion'] >= $evaluacion['puntaje_maximo'] * 0.6 ? 'badge-aprobado' : 'badge-reprobado' ?>">
                            <?= $evaluacion['calificacion'] >= $evaluacion['puntaje_maximo'] * 0.6 ? '✅ Aprobado' : '❌ Reprobado' ?>
                        </span>
                        <span style="margin-left: 10px; color: #666; font-size: 14px;">
                            <?= $respondidas ?> de <?= $total_preguntas ?> preguntas respondidas
                        </span>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px 0;">
                        <i class="fa-solid fa-clock" style="font-size: 24px; color: #f39c12;"></i>
                        <p style="margin: 10px 0; color: #666;">Esta evaluación está en revisión por el docente.</p>
                        <p style="color: #999; font-size: 14px;">Las respuestas abiertas requieren revisión manual.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($evaluacion['calificacion'] !== null && !empty($respuestas_alumno)): ?>
                <h3 style="margin-bottom: 15px;">📋 Revisión de respuestas</h3>
                <?php foreach ($preguntas as $idx => $pregunta): ?>
                    <div class="respuesta-review">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong>Pregunta <?= $idx + 1 ?></strong>
                                <div style="margin: 5px 0 10px 0;"><?= htmlspecialchars($pregunta['texto']) ?></div>
                            </div>
                            <?php if (isset($pregunta['respuesta_correcta']) && isset($respuestas_alumno[$idx])): ?>
                                <?php 
                                $es_correcta = intval($respuestas_alumno[$idx]) === intval($pregunta['respuesta_correcta']);
                                ?>
                                <span class="<?= $es_correcta ? 'correcta' : 'incorrecta' ?>">
                                    <?= $es_correcta ? '✅ Correcta' : '❌ Incorrecta' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tu-respuesta">
                            <strong>Tu respuesta:</strong>
                            <?php if (isset($respuestas_alumno[$idx])): ?>
                                <?php 
                                $respuesta_idx = intval($respuestas_alumno[$idx]);
                                $opciones = $pregunta['opciones'] ?? [];
                                if (isset($opciones[$respuesta_idx])): ?>
                                    <?= htmlspecialchars($opciones[$respuesta_idx]) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($respuestas_alumno[$idx]) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">No respondida</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($pregunta['respuesta_correcta']) && isset($respuestas_alumno[$idx])): ?>
                            <div style="font-size: 13px; color: #2ecc71; margin-top: 5px;">
                                <i class="fa-regular fa-check-circle"></i> 
                                Respuesta correcta: <?= htmlspecialchars($opciones[intval($pregunta['respuesta_correcta'])] ?? '') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($evaluacion['retroalimentacion']): ?>
                <div style="background: #fff3cd; padding: 15px 20px; border-radius: 8px; margin: 20px 0;">
                    <strong><i class="fa-regular fa-comment"></i> Retroalimentación del docente:</strong>
                    <p style="margin: 10px 0 0 0;"><?= nl2br(htmlspecialchars($evaluacion['retroalimentacion'])) ?></p>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="actividades.php" class="btn-volver">
                    <i class="fa-solid fa-arrow-left"></i> Volver a actividades
                </a>
            </div>
        </div>
    </main>
</div>

<?php include '../Accesibilidad/accesibilidad.php'; ?>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>