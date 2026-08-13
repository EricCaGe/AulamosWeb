<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_actividad = intval($_POST['id_actividad'] ?? 0);
$id_actividad_estudiante = intval($_POST['id_actividad_estudiante'] ?? 0);
$respuestas = $_POST['respuestas'] ?? [];
$tiempo_empleado = intval($_POST['tiempo_empleado'] ?? 0);

if (empty($respuestas)) {
    $_SESSION['mensaje'] = 'Debes responder al menos una pregunta.';
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: realizar_evaluacion.php?id=$id_actividad");
    exit;
}

try {
    // Verificar que la actividad existe y pertenece al alumno
    $sql_check = "SELECT id_actividad_estudiante, estado FROM actividad_estudiantes 
                  WHERE id_actividad_estudiante = ? AND id_alumno = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("ii", $id_actividad_estudiante, $_SESSION['usuario']['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('No tienes permiso para responder esta evaluación.');
    }
    
    $act_est = $result->fetch_assoc();
    if (in_array($act_est['estado'], ['Completada', 'Calificada'])) {
        throw new Exception('Esta evaluación ya fue completada.');
    }
    $stmt->close();

    // Obtener configuración de la evaluación (para saber respuestas correctas)
    $sql_config = "SELECT configuracion_evaluacion FROM actividades WHERE id_actividad = ?";
    $stmt = $conexion->prepare($sql_config);
    $stmt->bind_param("i", $id_actividad);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row || empty($row['configuracion_evaluacion'])) {
        throw new Exception('No se encontró la configuración de la evaluación.');
    }
    
    $config = json_decode($row['configuracion_evaluacion'], true);
    $stmt->close();

    $preguntas = $config['preguntas'] ?? [];

    // Calcular calificación automática para preguntas de opción múltiple y V/F
    $puntaje_obtenido = 0;
    $puntaje_total = 0;
    $respuestas_procesadas = [];
    $respuestas_correctas = 0;
    $total_objetivas = 0;

    foreach ($preguntas as $idx => $pregunta) {
        $puntaje = floatval($pregunta['puntaje'] ?? 1);
        $puntaje_total += $puntaje;
        
        if (isset($respuestas[$idx])) {
            $respuesta_alumno = $respuestas[$idx];
            $respuestas_procesadas[$idx] = $respuesta_alumno;

            // Verificar si es correcta (solo para opción múltiple y V/F)
            if (($pregunta['tipo'] === 'OpcionMultiple' || $pregunta['tipo'] === 'VerdaderoFalso') && 
                isset($pregunta['respuesta_correcta'])) {
                $total_objetivas++;
                if (intval($respuesta_alumno) === intval($pregunta['respuesta_correcta'])) {
                    $puntaje_obtenido += $puntaje;
                    $respuestas_correctas++;
                }
            }
        }
    }

    // Determinar si está calificada (automática solo si todas las preguntas son objetivas)
    $hay_abiertas = false;
    foreach ($preguntas as $pregunta) {
        if ($pregunta['tipo'] === 'Abierta') {
            $hay_abiertas = true;
            break;
        }
    }

    $estado_entrega = $hay_abiertas ? 'Entregada' : 'Calificada';
    $calificacion = $hay_abiertas ? null : $puntaje_obtenido;

    // Guardar entrega
    $sql_entrega = "INSERT INTO entregas (
        id_actividad_estudiante, 
        respuestas_evaluacion, 
        texto_entrega,
        calificacion,
        estado,
        tiempo_realizacion,
        fecha_entrega
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $respuestas_json = json_encode($respuestas_procesadas);
    $texto_entrega = "Evaluación completada con " . count($respuestas_procesadas) . " respuestas.";

    $stmt = $conexion->prepare($sql_entrega);
    
    // CORREGIR: El orden de los tipos debe coincidir con las variables
    // id_actividad_estudiante (i), respuestas_json (s), texto_entrega (s), 
    // calificacion (d o null), estado_entrega (s), tiempo_empleado (i)
    if ($calificacion === null) {
        // Si es null, usamos 's' para que sea NULL en la base de datos
        $stmt->bind_param(
            "issdsi",  // i, s, s, d (o s), s, i
            $id_actividad_estudiante,
            $respuestas_json,
            $texto_entrega,
            $calificacion,
            $estado_entrega,
            $tiempo_empleado
        );
    } else {
        $stmt->bind_param(
            "issdsi",
            $id_actividad_estudiante,
            $respuestas_json,
            $texto_entrega,
            $calificacion,
            $estado_entrega,
            $tiempo_empleado
        );
    }

    if (!$stmt->execute()) {
        throw new Exception('Error al guardar la entrega: ' . $stmt->error);
    }
    $stmt->close();

    // Actualizar estado del estudiante
    $estado_final = $hay_abiertas ? 'Completada' : 'Calificada';
    $sql_update = "UPDATE actividad_estudiantes 
                   SET estado = ?, porcentaje_avance = 100, fecha_finalizacion = NOW()
                   WHERE id_actividad_estudiante = ?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("si", $estado_final, $id_actividad_estudiante);
    $stmt->execute();
    $stmt->close();

    // Mensaje de éxito
    $mensaje = '✅ ¡Evaluación entregada exitosamente!';
    if (!$hay_abiertas) {
        $mensaje .= " Calificación: $puntaje_obtenido / $puntaje_total pts ";
        $mensaje .= "($respuestas_correctas de $total_objetivas correctas)";
    } else {
        $mensaje .= " El docente revisará tus respuestas abiertas.";
    }
    
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = 'success';
    header("Location: actividades.php");
    exit;

} catch (Exception $e) {
    $_SESSION['mensaje'] = '❌ Error al entregar la evaluación: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: realizar_evaluacion.php?id=$id_actividad");
    exit;
} finally {
    $conexion->close();
}
?>