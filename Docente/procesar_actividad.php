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
$instrucciones = trim($_POST['instrucciones'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$puntaje_maximo = $_POST['puntaje_maximo'] ?? 100.00;
$permite_entrega_archivo = isset($_POST['permite_entrega_archivo']) ? 1 : 0;
$id_curso = $_POST['id_curso'] ?? '';
$id_periodo = !empty($_POST['id_periodo']) ? $_POST['id_periodo'] : null;
$fecha_limite = $_POST['fecha_limite'] ?? '';

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
if (empty($instrucciones)) {
    $errores[] = "Las instrucciones son obligatorias.";
}
if (empty($tipo)) {
    $errores[] = "El tipo de actividad es obligatorio.";
}
if (empty($id_curso)) {
    $errores[] = "El curso es obligatorio.";
}
if (!is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
}

// Validar y formatear la fecha límite
if (empty($fecha_limite)) {
    $errores[] = "La fecha límite es obligatoria.";
} else {
    $fecha_limite_date = DateTime::createFromFormat('Y-m-d\TH:i', $fecha_limite);
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

// Si hay errores, redirigir
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['form_data'] = $_POST;
    header('Location: crear_actividad.php');
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
    $_SESSION['mensaje'] = "El curso seleccionado no existe o no te pertenece.";
    $_SESSION['tipo_mensaje'] = "error";
    $stmt->close();
    header('Location: crear_actividad.php');
    exit;
}
$stmt->close();

// =====================================================
// INSERTAR ACTIVIDAD
// =====================================================
$query = "
    INSERT INTO actividades (
        id_curso, id_periodo, id_docente, titulo, descripcion, instrucciones,
        tipo, puntaje_maximo, permite_entrega_archivo, fecha_limite, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Publicada')
";

$stmt = $conexion->prepare($query);

if (!$stmt) {
    $_SESSION['mensaje'] = "Error al preparar la consulta: " . $conexion->error;
    $_SESSION['tipo_mensaje'] = "error";
    header('Location: crear_actividad.php');
    exit;
}

$stmt->bind_param(
    "iissssssds",
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
    $id_actividad = $stmt->insert_id;

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

    $query_actividad_estudiante = "
        INSERT INTO actividad_estudiantes (id_actividad, id_alumno, estado)
        VALUES (?, ?, 'Pendiente')
    ";
    $stmt_actividad_estudiante = $conexion->prepare($query_actividad_estudiante);

    while ($row = $result_estudiantes->fetch_assoc()) {
        $stmt_actividad_estudiante->bind_param("ii", $id_actividad, $row['id_alumno']);
        $stmt_actividad_estudiante->execute();
    }

    $stmt_actividad_estudiante->close();
    $stmt_estudiantes->close();

    $_SESSION['mensaje'] = "Actividad creada correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
} else {
    $_SESSION['mensaje'] = "Error al crear la actividad: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "error";
}

$stmt->close();
$conexion->close();

header('Location: crear_actividad.php');
exit;
?>