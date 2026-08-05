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
$instrucciones = $_POST['instrucciones'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$puntaje_maximo = $_POST['puntaje_maximo'] ?? 100.00;
$permite_entrega_archivo = isset($_POST['permite_entrega_archivo']) ? 1 : 0;
$id_curso = $_POST['id_curso'] ?? '';
$id_periodo = $_POST['id_periodo'] ?? null;
$fecha_limite = $_POST['fecha_limite'] ?? '';

// Validaciones
$errores = [];

if (empty($titulo)) {
    $errores[] = "El título es obligatorio.";
}

if (empty($descripcion)) {
    $errores[] = "La descripción es obligatoria.";
}

if (empty($instrucciones)) {
    $errores[] = "Las instrucciones son obligatorias.";
}

if (empty($tipo)) {
    $errores[] = "El tipo de actividad es obligatorio.";
}

if (empty($id_curso)) {
    $errores[] = "El curso es obligatorio.";
}

if (empty($puntaje_maximo) || !is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
}

if (empty($fecha_limite)) {
    $errores[] = "La fecha límite es obligatoria.";
} else {
    // Validar que la fecha límite sea en el futuro
    $fecha_limite_date = new DateTime($fecha_limite);
    $ahora = new DateTime();
    if ($fecha_limite_date <= $ahora) {
        $errores[] = "La fecha límite debe ser en el futuro.";
    }
}

// Si hay errores, redirigir de vuelta al formulario con los errores
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['form_data'] = $_POST;
    header('Location: crear_actividad.php');
    exit;
}

// Procesar la fecha límite para guardarla en el formato correcto
$fecha_limite_formateada = $fecha_limite;

// Insertar la actividad en la base de datos
$query = "
    INSERT INTO actividades (
        id_curso, id_periodo, id_docente, titulo, descripcion, instrucciones,
        tipo, puntaje_maximo, permite_entrega_archivo, fecha_limite, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Publicada')
";

$stmt = $conexion->prepare($query);
$stmt->bind_param(
    "iissssssdi",
    $id_curso,
    $id_periodo,
    $id_docente,
    $titulo,
    $descripcion,
    $instrucciones,
    $tipo,
    $puntaje_maximo,
    $permite_entrega_archivo,
    $fecha_limite_formateada
);

if ($stmt->execute()) {
    // Éxito: Obtener el ID de la actividad creada
    $id_actividad = $stmt->insert_id;

    // Crear registros en actividad_estudiantes para todos los estudiantes del curso
    $query_estudiantes = "
        SELECT id_usuario
        FROM inscripciones i
        JOIN usuarios u ON i.id_alumno = u.id_usuario
        WHERE i.id_curso = ? AND i.estado = 'Activo'
    ";
    $stmt_estudiantes = $conexion->prepare($query_estudiantes);
    $stmt_estudiantes->bind_param("i", $id_curso);
    $stmt_estudiantes->execute();
    $result_estudiantes = $stmt_estudiantes->get_result();

    while ($row = $result_estudiantes->fetch_assoc()) {
        $id_alumno = $row['id_usuario'];
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

    $_SESSION['mensaje'] = "Actividad creada correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
} else {
    $_SESSION['mensaje'] = "Error al crear la actividad: " . $conexion->error;
    $_SESSION['tipo_mensaje'] = "error";
}

// Cerrar conexiones
$stmt->close();
$conexion->close();

// Redirigir de vuelta al formulario
header('Location: crear_actividad.php');
exit;
?>