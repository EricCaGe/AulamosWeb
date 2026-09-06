<?php
session_start();

// =====================================================
// VERIFICAR SESIÓN DOCENTE
// =====================================================
if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] ?? '') !== 'Docente' ||
    empty($_SESSION['usuario']['id_usuario'])
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = (int) $_SESSION['usuario']['id_usuario'];

// =====================================================
// SOLO ACEPTAR POST
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crear_actividad.php');
    exit;
}

// =====================================================
// OBTENER DATOS DEL FORMULARIO
// =====================================================
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$instrucciones = trim($_POST['instrucciones'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');

$puntaje_maximo = isset($_POST['puntaje_maximo'])
    ? (float) $_POST['puntaje_maximo']
    : 100.00;

$permite_entrega_archivo =
    isset($_POST['permite_entrega_archivo']) ? 1 : 0;

$id_curso = isset($_POST['id_curso'])
    ? (int) $_POST['id_curso']
    : 0;

$id_periodo = !empty($_POST['id_periodo'])
    ? (int) $_POST['id_periodo']
    : null;

// NUEVO: recurso opcional
$id_recurso = !empty($_POST['id_recurso'])
    ? (int) $_POST['id_recurso']
    : null;

$fecha_limite = trim($_POST['fecha_limite'] ?? '');

// =====================================================
// VALIDACIONES
// =====================================================
$errores = [];

$tipos_validos = [
    'Tarea',
    'Ejercicio',
    'Lectura',
    'Proyecto'
];

if ($titulo === '') {
    $errores[] = 'El título es obligatorio.';
}

if (mb_strlen($titulo) > 150) {
    $errores[] = 'El título no puede superar los 150 caracteres.';
}

if ($descripcion === '') {
    $errores[] = 'La descripción es obligatoria.';
}

if ($instrucciones === '') {
    $errores[] = 'Las instrucciones son obligatorias.';
}

if (!in_array($tipo, $tipos_validos, true)) {
    $errores[] = 'El tipo de actividad no es válido.';
}

if ($id_curso <= 0) {
    $errores[] = 'El curso es obligatorio.';
}

if (
    !is_numeric($puntaje_maximo) ||
    $puntaje_maximo <= 0 ||
    $puntaje_maximo > 999.99
) {
    $errores[] = 'El puntaje máximo debe estar entre 0.01 y 999.99.';
}

// =====================================================
// VALIDAR FECHA
// =====================================================
$fecha_limite_formateada = null;

if ($fecha_limite === '') {
    $errores[] = 'La fecha límite es obligatoria.';
} else {
    $fecha_objeto = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $fecha_limite
    );

    if (!$fecha_objeto) {
        $errores[] =
            'Formato de fecha no válido. Usa el selector de fecha y hora.';
    } else {
        $ahora = new DateTime();

        if ($fecha_objeto <= $ahora) {
            $errores[] =
                'La fecha límite debe ser posterior a la fecha actual.';
        }

        $fecha_limite_formateada =
            $fecha_objeto->format('Y-m-d H:i:s');
    }
}

// =====================================================
// SI HAY ERRORES
// =====================================================
if (!empty($errores)) {
    $_SESSION['mensaje'] = implode(' ', $errores);
    $_SESSION['tipo_mensaje'] = 'error';
    $_SESSION['form_data'] = $_POST;

    header('Location: crear_actividad.php');
    exit;
}

try {
    // =================================================
    // INICIAR TRANSACCIÓN
    // =================================================
    $conexion->begin_transaction();

    // =================================================
    // 1. VERIFICAR QUE EL CURSO PERTENEZCA AL DOCENTE
    // =================================================
    $stmt = $conexion->prepare("
        SELECT
            id_curso,
            id_ciclo
        FROM cursos
        WHERE id_curso = ?
          AND id_docente = ?
          AND estado = 'Activo'
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception(
            'Error al validar curso: ' . $conexion->error
        );
    }

    $stmt->bind_param(
        'ii',
        $id_curso,
        $id_docente
    );

    $stmt->execute();

    $curso = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if (!$curso) {
        throw new Exception(
            'El curso seleccionado no existe o no te pertenece.'
        );
    }

    // =================================================
    // 2. VALIDAR PERIODO, SI SE SELECCIONÓ
    // =================================================
    if ($id_periodo !== null) {
        $id_ciclo = (int) $curso['id_ciclo'];

        $stmt = $conexion->prepare("
            SELECT id_periodo
            FROM periodos_evaluacion
            WHERE id_periodo = ?
              AND id_ciclo = ?
              AND estado = 'Activo'
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Error al validar periodo: ' . $conexion->error
            );
        }

        $stmt->bind_param(
            'ii',
            $id_periodo,
            $id_ciclo
        );

        $stmt->execute();

        $periodo = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$periodo) {
            throw new Exception(
                'El periodo seleccionado no corresponde al ciclo del curso.'
            );
        }
    }

    // =================================================
    // 3. VALIDAR RECURSO, SI SE SELECCIONÓ
    // =================================================
    if ($id_recurso !== null) {
        $stmt = $conexion->prepare("
            SELECT
                id_recurso,
                id_curso,
                id_actividad,
                estado
            FROM recursos_educativos
            WHERE id_recurso = ?
              AND id_docente = ?
              AND estado = 'Activo'
              AND id_actividad IS NULL
              AND (
                    id_curso IS NULL
                    OR id_curso = ?
                  )
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Error al validar recurso: ' . $conexion->error
            );
        }

        $stmt->bind_param(
            'iii',
            $id_recurso,
            $id_docente,
            $id_curso
        );

        $stmt->execute();

        $recurso = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$recurso) {
            throw new Exception(
                'El recurso seleccionado no está disponible para este curso.'
            );
        }
    }

    // =================================================
    // 4. INSERTAR ACTIVIDAD
    // =================================================
    $query = "
        INSERT INTO actividades (
            id_curso,
            id_periodo,
            id_docente,
            titulo,
            descripcion,
            instrucciones,
            tipo,
            puntaje_maximo,
            permite_entrega_archivo,
            fecha_limite,
            estado
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Publicada'
        )
    ";

    $stmt = $conexion->prepare($query);

    if (!$stmt) {
        throw new Exception(
            'Error al preparar actividad: ' . $conexion->error
        );
    }

    $stmt->bind_param(
        'iiissssdis',
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

    if (!$stmt->execute()) {
        throw new Exception(
            'Error al crear la actividad: ' . $stmt->error
        );
    }

    $id_actividad = (int) $stmt->insert_id;

    $stmt->close();

    // =================================================
    // 5. ASOCIAR RECURSO A LA ACTIVIDAD
    // =================================================
    if ($id_recurso !== null) {
        $stmt = $conexion->prepare("
            UPDATE recursos_educativos
            SET
                id_actividad = ?,
                id_curso = COALESCE(id_curso, ?)
            WHERE id_recurso = ?
              AND id_docente = ?
              AND estado = 'Activo'
              AND id_actividad IS NULL
        ");

        if (!$stmt) {
            throw new Exception(
                'Error al preparar asociación del recurso: ' .
                $conexion->error
            );
        }

        $stmt->bind_param(
            'iiii',
            $id_actividad,
            $id_curso,
            $id_recurso,
            $id_docente
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();

            throw new Exception(
                'No se pudo asociar el recurso a la actividad.'
            );
        }

        $stmt->close();
    }

    // =================================================
    // 6. CREAR actividad_estudiantes
    // =================================================
    $stmt = $conexion->prepare("
        INSERT IGNORE INTO actividad_estudiantes (
            id_actividad,
            id_alumno,
            estado
        )
        SELECT
            ?,
            i.id_alumno,
            'Pendiente'
        FROM inscripciones i
        WHERE i.id_curso = ?
          AND i.estado = 'Activo'
    ");

    if (!$stmt) {
        throw new Exception(
            'Error al asignar estudiantes: ' .
            $conexion->error
        );
    }

    $stmt->bind_param(
        'ii',
        $id_actividad,
        $id_curso
    );

    $stmt->execute();

    $alumnos_asignados =
        max(0, (int) $stmt->affected_rows);

    $stmt->close();

    // =================================================
    // 7. CREAR NOTIFICACIONES
    // =================================================
    $stmt = $conexion->prepare("
        INSERT INTO notificaciones (
            id_usuario,
            titulo,
            mensaje,
            tipo,
            entidad_tipo,
            entidad_id,
            leida
        )
        SELECT
            i.id_alumno,
            'Nueva actividad',
            CONCAT('Se publicó la actividad: ', ?),
            'Actividad',
            'Actividad',
            ?,
            0
        FROM inscripciones i
        WHERE i.id_curso = ?
          AND i.estado = 'Activo'
    ");

    if ($stmt) {
        $stmt->bind_param(
            'sii',
            $titulo,
            $id_actividad,
            $id_curso
        );

        $stmt->execute();
        $stmt->close();
    }

    // =================================================
    // CONFIRMAR TRANSACCIÓN
    // =================================================
    $conexion->commit();

    $mensaje =
        'Actividad creada correctamente. ' .
        'Estudiantes asignados: ' .
        $alumnos_asignados . '.';

    if ($id_recurso !== null) {
        $mensaje .=
            ' El recurso fue asociado correctamente.';
    }

    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = 'success';

} catch (Throwable $e) {
    // =================================================
    // REVERTIR SI ALGO FALLA
    // =================================================
    $conexion->rollback();

    error_log(
        'Error procesar_actividad.php: ' .
        $e->getMessage()
    );

    $_SESSION['mensaje'] =
        'No se pudo crear la actividad. ' .
        $e->getMessage();

    $_SESSION['tipo_mensaje'] =
        'error';
}

$conexion->close();

header('Location: crear_actividad.php');
exit;
?>
