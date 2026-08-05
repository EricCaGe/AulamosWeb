<?php
// Iniciar sesión
session_start();

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

// Obtener datos del formulario
$id_docente = $_SESSION['usuario']['id_usuario'];
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$id_materia = $_POST['id_materia'] ?? '';
$id_curso = $_POST['id_curso'] ?? '';
$tipo_evaluacion = $_POST['tipo_evaluacion'] ?? '';
$puntaje_maximo = $_POST['puntaje_maximo'] ?? 100.00;
$fecha_limite = $_POST['fecha_limite'] ?? null;
$id_periodo = $_POST['id_periodo'] ?? null;

// Validaciones
$errores = [];

if (empty($titulo)) {
    $errores[] = "El título es obligatorio.";
}

if (empty($descripcion)) {
    $errores[] = "La descripción es obligatoria.";
}

if (empty($id_materia)) {
    $errores[] = "La materia es obligatoria.";
}

if (empty($id_curso)) {
    $errores[] = "El curso es obligatorio.";
}

if (empty($tipo_evaluacion)) {
    $errores[] = "El tipo de evaluación es obligatorio.";
}

if (empty($puntaje_maximo) || !is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
}

// Si hay errores, redirigir de vuelta al formulario con los errores
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['form_data'] = $_POST;
    header('Location: crear_evaluacion.php');
    exit;
}

// Procesar la fecha límite
if (empty($fecha_limite)) {
    // Si no se proporcionó fecha límite, establecer una por defecto (ej: 7 días desde ahora)
    $fecha_limite = date('Y-m-d H:i:s', strtotime('+7 days'));
} else {
    // Convertir la fecha de datetime-local a formato MySQL
    $fecha_limite = date('Y-m-d H:i:s', strtotime($fecha_limite));
}

// Insertar la evaluación en la base de datos (como una actividad de tipo 'Evaluacion')
$query = "
    INSERT INTO actividades (
        id_curso, id_periodo, id_docente, titulo, descripcion, instrucciones,
        tipo, puntaje_maximo, permite_entrega_archivo, fecha_limite, estado
    ) VALUES (?, ?, ?, ?, ?, ?, 'Evaluacion', ?, 1, ?, 'Publicada')
";

$stmt = $conexion->prepare($query);
$stmt->bind_param(
    "iissssds",
    $id_curso,
    $id_periodo,
    $id_docente,
    $titulo,
    $descripcion,
    $tipo_evaluacion, // Guardamos el tipo de evaluación en el campo instrucciones (o podrías crear una tabla adicional)
    $puntaje_maximo,
    $fecha_limite
);

if ($stmt->execute()) {
    // Éxito: Obtener el ID de la actividad creada
    $id_actividad = $stmt->insert_id;

    // Crear registros en actividad_estudiantes para todos los estudiantes del curso
    $query_estudiantes = "
        SELECT i.id_alumno
        FROM inscripciones i
        WHERE i.id_curso = ? AND i.estado = 'Activo'
    ";
    $stmt_estudiantes = $conexion->prepare($query_estudiantes);
    $stmt_estudiantes->bind_param("i", $id_curso);
    $stmt_estudiantes->execute();
    $result_estudiantes = $stmt_estudiantes->get_result();

    while ($row = $result_estudiantes->fetch_assoc()) {
        $id_alumno = $row['id_alumno'];
        $query_actividad_estudiante = "
            INSERT INTO actividad_estudiantes (id_actividad, id_alumno, estado)
            VALUES (?, ?, 'Pendiente')
        ";
        $stmt_actividad_estudiante = $conexion->prepare($query_actividad_estudiante);
        $stmt_actividad_estudiante->bind_param("ii", $id_actividad, $id_alumno);
        $stmt_actividad_estudiante->execute();
        $stmt_actividad_estudiante->close();
    }
    $stmt_estudiantes->close();

    // Mensaje de éxito
    $_SESSION['mensaje'] = "Evaluación creada correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
} else {
    // Mensaje de error
    $_SESSION['mensaje'] = "Error al crear la evaluación: " . $conexion->error;
    $_SESSION['tipo_mensaje'] = "error";
}

// Cerrar conexiones
$stmt->close();
$conexion->close();

// Redirigir de vuelta al formulario
header('Location: crear_evaluacion.php');
exit;
?>