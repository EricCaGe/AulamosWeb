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
$id_materia = $_POST['id_materia'] ?? '';
$id_curso = $_POST['id_curso'] ?? '';
$tipo = $_POST['tipo_evaluacion'] ?? 'Cuestionario';
$puntaje_maximo = $_POST['puntaje_maximo'] ?? 100.00;
$permite_entrega_archivo = isset($_POST['permite_entrega_archivo']) ? 1 : 0;
$fecha_limite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;
$id_periodo = !empty($_POST['id_periodo']) ? $_POST['id_periodo'] : null;

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
if (empty($id_materia)) {
    $errores[] = "La materia es obligatoria.";
}
if (empty($id_curso)) {
    $errores[] = "El curso es obligatorio.";
}
if (!is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
}

// Validar fecha límite
if (!empty($fecha_limite)) {
    $fecha_limite_date = DateTime::createFromFormat('Y-m-d\TH:i', $fecha_limite);
    if ($fecha_limite_date) {
        $fecha_limite = $fecha_limite_date->format('Y-m-d H:i:s');
        $ahora = new DateTime();
        if ($fecha_limite_date <= $ahora) {
            $errores[] = "La fecha límite debe ser en el futuro.";
        }
    } else {
        $errores[] = "Formato de fecha no válido.";
    }
}

// Si es cuestionario, validar preguntas
if ($tipo === 'Cuestionario') {
    if (!isset($_POST['preguntas']) || empty($_POST['preguntas'])) {
        $errores[] = "Debe agregar al menos una pregunta para el cuestionario.";
    } else {
        foreach ($_POST['preguntas'] as $index => $pregunta) {
            if (empty($pregunta['texto'])) {
                $errores[] = "El texto de la pregunta " . ($index + 1) . " es obligatorio.";
            }
            if (!isset($pregunta['tipo'])) {
                $errores[] = "Debe seleccionar el tipo de la pregunta " . ($index + 1) . ".";
            }
            if (isset($pregunta['tipo']) && ($pregunta['tipo'] === 'OpcionMultiple' || $pregunta['tipo'] === 'VerdaderoFalso')) {
                if (!isset($pregunta['opciones']) || empty($pregunta['opciones'])) {
                    $errores[] = "La pregunta " . ($index + 1) . " debe tener al menos una opción.";
                }
                if (!isset($pregunta['respuesta_correcta'])) {
                    $errores[] = "Debe seleccionar la respuesta correcta para la pregunta " . ($index + 1) . ".";
                }
            }
        }
    }
}

// Si hay errores, redirigir
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['form_data'] = $_POST;
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
    $_SESSION['mensaje'] = "El curso seleccionado no existe o no te pertenece.";
    $_SESSION['tipo_mensaje'] = "error";
    $stmt->close();
    header('Location: crear_evaluacion.php');
    exit;
}
$stmt->close();

// =====================================================
// INICIAR TRANSACCIÓN
// =====================================================
$conexion->begin_transaction();

try {
    // =====================================================
    // INSERTAR EVALUACIÓN EN actividades
    // =====================================================
    $query = "
        INSERT INTO actividades (
            id_curso, id_docente, titulo, descripcion, tipo,
            puntaje_maximo, permite_entrega_archivo, fecha_limite, id_periodo, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Publicada')
    ";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param(
        "iisssddii",
        $id_curso,
        $id_docente,
        $titulo,
        $descripcion,
        $tipo,
        $puntaje_maximo,
        $permite_entrega_archivo,
        $fecha_limite,
        $id_periodo
    );
    $stmt->execute();
    $id_actividad = $stmt->insert_id;
    $stmt->close();

    // =====================================================
    // GUARDAR CONFIGURACIÓN SEGÚN TIPO
    // =====================================================
    $configuracion = [];

    if ($tipo === 'Cuestionario' && isset($_POST['preguntas'])) {
        $configuracion = [
            'preguntas' => $_POST['preguntas'],
            'tipo' => 'Cuestionario'
        ];

        $query_config = "UPDATE actividades SET configuracion_evaluacion = ? WHERE id_actividad = ?";
        $stmt_config = $conexion->prepare($query_config);
        $stmt_config->bind_param("si", json_encode($configuracion), $id_actividad);
        $stmt_config->execute();
        $stmt_config->close();
    }

    if ($tipo === 'Examen' || $tipo === 'Tarea') {
        $tipos_archivos = $_POST['tipos_archivos'] ?? [];
        $tamano_maximo = $_POST['tamano_maximo'] ?? 10;
        $cantidad_archivos = $_POST['cantidad_archivos'] ?? 1;

        $configuracion = [
            'tipos_archivos' => $tipos_archivos,
            'tamano_maximo' => $tamano_maximo,
            'cantidad_archivos' => $cantidad_archivos,
            'tipo' => $tipo
        ];

        $query_config = "
            UPDATE actividades
            SET configuracion_evaluacion = ?, permite_entrega_archivo = 1
            WHERE id_actividad = ?
        ";
        $stmt_config = $conexion->prepare($query_config);
        $stmt_config->bind_param("si", json_encode($configuracion), $id_actividad);
        $stmt_config->execute();
        $stmt_config->close();
    }

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

    // =====================================================
    // CONFIRMAR TRANSACCIÓN
    // =====================================================
    $conexion->commit();

    $_SESSION['mensaje'] = "Evaluación creada correctamente.";
    $_SESSION['tipo_mensaje'] = "success";

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje'] = "Error al crear la evaluación: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "error";
}

$conexion->close();

header('Location: crear_evaluacion.php');
exit;
?>