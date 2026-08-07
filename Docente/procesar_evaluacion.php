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
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_materia = $_POST['id_materia'] ?? '';
$id_curso = $_POST['id_curso'] ?? '';
$tipo = $_POST['tipo_evaluacion'] ?? 'Cuestionario';
$puntaje_maximo = $_POST['puntaje_maximo'] ?? 100.00;
$permite_entrega_archivo = isset($_POST['permite_entrega_archivo']) ? 1 : 0;
$fecha_limite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;
$id_periodo = !empty($_POST['id_periodo']) ? $_POST['id_periodo'] : null;

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

if (!is_numeric($puntaje_maximo) || $puntaje_maximo <= 0) {
    $errores[] = "El puntaje máximo debe ser un número mayor a 0.";
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

// Si es examen o tarea, validar configuración de archivos
if ($tipo === 'Examen' || $tipo === 'Tarea') {
    if (!isset($_POST['tipos_archivos']) || empty($_POST['tipos_archivos'])) {
        $errores[] = "Debe seleccionar al menos un tipo de archivo permitido.";
    }
    if (empty($_POST['tamano_maximo']) || !is_numeric($_POST['tamano_maximo']) || $_POST['tamano_maximo'] <= 0) {
        $errores[] = "El tamaño máximo por archivo debe ser un número mayor a 0.";
    }
    if (empty($_POST['cantidad_archivos']) || !is_numeric($_POST['cantidad_archivos']) || $_POST['cantidad_archivos'] <= 0) {
        $errores[] = "La cantidad máxima de archivos debe ser un número mayor a 0.";
    }
}

// Si hay errores, redirigir de vuelta al formulario con los errores
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['form_data'] = $_POST;
    header('Location: crear_evaluacion.php');
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Insertar la evaluación en la tabla actividades
    $query = "
        INSERT INTO actividades (
            id_curso, id_docente, titulo, descripcion, tipo,
            puntaje_maximo, permite_entrega_archivo, fecha_limite, id_periodo, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Publicada')
    ";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param(
        "iisssddisi",
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

    // Si es cuestionario, guardar las preguntas
    if ($tipo === 'Cuestionario' && isset($_POST['preguntas'])) {
        // Crear configuración de evaluación (JSON)
        $configuracion_evaluacion = [
            'preguntas' => $_POST['preguntas'],
            'tipo' => 'Cuestionario'
        ];

        // Actualizar la actividad con la configuración
        $query_config = "
            UPDATE actividades
            SET configuracion_evaluacion = ?
            WHERE id_actividad = ?
        ";
        $stmt_config = $conexion->prepare($query_config);
        $stmt_config->bind_param("si", json_encode($configuracion_evaluacion), $id_actividad);
        $stmt_config->execute();
        $stmt_config->close();
    }

    // Si es examen o tarea, guardar configuración de archivos
    if ($tipo === 'Examen' || $tipo === 'Tarea') {
        $tipos_archivos = $_POST['tipos_archivos'] ?? [];
        $tamano_maximo = $_POST['tamano_maximo'] ?? 10;
        $cantidad_archivos = $_POST['cantidad_archivos'] ?? 1;

        $configuracion_evaluacion = [
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
        $stmt_config->bind_param("si", json_encode($configuracion_evaluacion), $id_actividad);
        $stmt_config->execute();
        $stmt_config->close();
    }

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

    // Confirmar transacción
    $conexion->commit();

    $_SESSION['mensaje'] = "Evaluación creada correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    $_SESSION['mensaje'] = "Error al crear la evaluación: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "error";
}

// Cerrar conexión
$conexion->close();

// Redirigir de vuelta al formulario
header('Location: crear_evaluacion.php');
exit;
?>