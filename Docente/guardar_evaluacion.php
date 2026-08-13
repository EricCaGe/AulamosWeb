<?php
session_start();

// =====================================================
// VERIFICAR SESIÓN DOCENTE
// =====================================================
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

// Regenerar ID de sesión por seguridad
session_regenerate_id(true);

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];

// =====================================================
// OBTENER DATOS DEL FORMULARIO
// =====================================================
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_materia = intval($_POST['id_materia'] ?? 0);
$id_curso = intval($_POST['id_curso'] ?? 0);
$id_periodo = !empty($_POST['id_periodo']) ? intval($_POST['id_periodo']) : null;
$fecha_limite = $_POST['fecha_limite'] ?? '';
$duracion_minutos = intval($_POST['duracion_minutos'] ?? 30);
$intentos_permitidos = intval($_POST['intentos_permitidos'] ?? 1);
$puntaje_maximo = floatval($_POST['puntaje_maximo'] ?? 100);
$tipo_evaluacion = $_POST['tipo_evaluacion'] ?? 'Cuestionario';
$preguntas = $_POST['preguntas'] ?? [];

// =====================================================
// VALIDACIONES
// =====================================================
$errores = [];

if (empty($titulo)) {
    $errores[] = "El título es obligatorio.";
}
if (empty($descripcion)) {
    $errores[] = "La descripción es obligatoria.";
}
if (empty($id_curso)) {
    $errores[] = "El curso es obligatorio.";
}
if (empty($preguntas) || count($preguntas) === 0) {
    $errores[] = "Debes agregar al menos una pregunta.";
}
if (!is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
}

// =====================================================
// VALIDAR Y FORMATEAR FECHA LÍMITE
// =====================================================
if (empty($fecha_limite)) {
    $errores[] = "La fecha límite es obligatoria.";
} else {
    // Reemplazar 'T' por espacio para formato MySQL
    $fecha_limite = str_replace('T', ' ', $fecha_limite);
    
    // Agregar segundos si no tiene (formato: YYYY-MM-DD HH:MM)
    if (strlen($fecha_limite) === 16) {
        $fecha_limite .= ':00';
    }
    
    // Validar formato
    $fecha_limite_date = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_limite);
    if (!$fecha_limite_date) {
        $errores[] = "Formato de fecha no válido. Use el selector de fecha y hora.";
    } else {
        $fecha_limite_formateada = $fecha_limite_date->format('Y-m-d H:i:s');
        $ahora = new DateTime();
        if ($fecha_limite_date <= $ahora) {
            $errores[] = "La fecha límite debe ser en el futuro.";
        }
    }
}

// Validar preguntas
foreach ($preguntas as $idx => $pregunta) {
    if (empty(trim($pregunta['texto'] ?? ''))) {
        $errores[] = "La pregunta " . ($idx + 1) . " no tiene texto.";
    }
    
    $tipo_pregunta = $pregunta['tipo'] ?? '';
    if ($tipo_pregunta === 'OpcionMultiple' || $tipo_pregunta === 'VerdaderoFalso') {
        $opciones = $pregunta['opciones'] ?? [];
        $opciones_vacias = 0;
        foreach ($opciones as $opcion) {
            if (empty(trim($opcion))) {
                $opciones_vacias++;
            }
        }
        if ($opciones_vacias > 0) {
            $errores[] = "La pregunta " . ($idx + 1) . " tiene opciones vacías.";
        }
        if (!isset($pregunta['respuesta_correcta']) && $pregunta['respuesta_correcta'] !== '0') {
            $errores[] = "La pregunta " . ($idx + 1) . " no tiene respuesta correcta seleccionada.";
        }
    }
}

// Si hay errores, redirigir
if (!empty($errores)) {
    $_SESSION['mensaje'] = "❌ " . implode(" ", $errores);
    $_SESSION['tipo_mensaje'] = "error";
    header('Location: crear_evaluacion.php');
    exit;
}

// =====================================================
// VERIFICAR QUE EL CURSO PERTENEZCA AL DOCENTE
// =====================================================
$stmt = $conexion->prepare("SELECT id_curso FROM cursos WHERE id_curso = ? AND id_docente = ? AND estado = 'Activo'");
$stmt->bind_param("ii", $id_curso, $id_docente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "❌ El curso seleccionado no existe o no te pertenece.";
    $_SESSION['tipo_mensaje'] = "error";
    $stmt->close();
    header('Location: crear_evaluacion.php');
    exit;
}
$stmt->close();

// =====================================================
// INSERTAR EVALUACIÓN (actividad tipo Evaluacion)
// =====================================================
try {
    $conexion->begin_transaction();

    // Configuración de la evaluación
    $config_evaluacion = json_encode([
        'tipo' => $tipo_evaluacion,
        'preguntas' => $preguntas,
        'duracion_minutos' => $duracion_minutos,
        'intentos_permitidos' => $intentos_permitidos
    ]);

    $sql = "
        INSERT INTO actividades (
            id_curso, id_periodo, id_docente, titulo, descripcion, 
            tipo, configuracion_evaluacion, fecha_limite, 
            puntaje_maximo, permite_entrega_archivo, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conexion->prepare($sql);
    $tipo = 'Evaluacion';
    $permite_entrega = 1;
    $estado = 'Publicada';

    $stmt->bind_param(
        "iiisssssdis", // 11 tipos: i,i,i,s,s,s,s,s,d,i,s
        $id_curso,      // i - entero
        $id_periodo,    // i - entero (puede ser null)
        $id_docente,    // i - entero
        $titulo,        // s - string
        $descripcion,   // s - string
        $tipo,          // s - string
        $config_evaluacion, // s - string (JSON)
        $fecha_limite_formateada, // s - string (fecha formateada)
        $puntaje_maximo, // d - decimal
        $permite_entrega, // i - entero
        $estado         // s - string
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al guardar la evaluación: ' . $stmt->error);
    }

    $id_actividad = $stmt->insert_id;
    $stmt->close();

    // =====================================================
    // CREAR REGISTROS EN actividad_estudiantes
    // =====================================================
    $query_estudiantes = "
        SELECT id_alumno
        FROM inscripciones
        WHERE id_curso = ? AND estado = 'Activo'
    ";
    $stmt_estudiantes = $conexion->prepare($query_estudiantes);
    $stmt_estudiantes->bind_param("i", $id_curso);
    $stmt_estudiantes->execute();
    $result_estudiantes = $stmt_estudiantes->get_result();

    $alumnos_registrados = 0;
    $query_actividad_estudiante = "
        INSERT INTO actividad_estudiantes (id_actividad, id_alumno, estado, fecha_inicio)
        VALUES (?, ?, 'Pendiente', NOW())
    ";
    $stmt_actividad_estudiante = $conexion->prepare($query_actividad_estudiante);

    while ($row = $result_estudiantes->fetch_assoc()) {
        $stmt_actividad_estudiante->bind_param("ii", $id_actividad, $row['id_alumno']);
        if ($stmt_actividad_estudiante->execute()) {
            $alumnos_registrados++;
        }
    }

    $stmt_actividad_estudiante->close();
    $stmt_estudiantes->close();

    // Confirmar transacción
    $conexion->commit();

    $_SESSION['mensaje'] = "✅ ¡Evaluación creada exitosamente! Se ha asignado a $alumnos_registrados estudiantes.";
    $_SESSION['tipo_mensaje'] = "success";

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje'] = "❌ Error al crear la evaluación: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "error";
}

$conexion->close();
header('Location: crear_evaluacion.php');
exit;
?>