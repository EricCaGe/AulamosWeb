<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    
    // =============================================
    // CASO: guardar - Inscripción MÚLTIPLE (checkbox)
    // =============================================
    case 'guardar':
        // Ahora id_alumno es un array de IDs
        $alumnos_seleccionados = $_POST['id_alumno'] ?? [];
        $id_curso = intval($_POST['id_curso'] ?? 0);
        $estado = $_POST['estado'] ?? 'Activo';

        // Validar que haya alumnos seleccionados
        if (empty($alumnos_seleccionados) || !is_array($alumnos_seleccionados)) {
            header('Location: ../inscripciones.php?mensaje=Selecciona al menos un estudiante&tipo=error');
            exit;
        }

        if ($id_curso <= 0) {
            header('Location: ../inscripciones.php?mensaje=Selecciona un curso válido&tipo=error');
            exit;
        }

        // Verificar que el curso exista y esté activo
        $check_curso = $conexion->query("SELECT id_curso FROM cursos WHERE id_curso = $id_curso AND estado = 'Activo'");
        if ($check_curso->num_rows === 0) {
            header('Location: ../inscripciones.php?mensaje=El curso no existe o no está activo&tipo=error');
            exit;
        }

        $insertados = 0;
        $duplicados = 0;
        $errores = 0;
        $alumnos_invalidos = 0;

        foreach ($alumnos_seleccionados as $id_alumno) {
            $id_alumno = intval($id_alumno);
            
            if ($id_alumno <= 0) {
                $alumnos_invalidos++;
                continue;
            }

            // Verificar que el estudiante exista y esté activo
            $check_alumno = $conexion->query("SELECT id_usuario FROM usuarios WHERE id_usuario = $id_alumno AND estado = 'Activo'");
            if ($check_alumno->num_rows === 0) {
                $alumnos_invalidos++;
                continue;
            }

            // Verificar que no esté ya inscrito en ese curso
            $check = $conexion->query("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = $id_alumno AND id_curso = $id_curso AND estado != 'Finalizado'");
            if ($check->num_rows > 0) {
                $duplicados++;
                continue;
            }

            // Insertar inscripción
            $stmt = $conexion->prepare("
                INSERT INTO inscripciones (id_alumno, id_curso, estado, fecha_inscripcion) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iis", $id_alumno, $id_curso, $estado);
            
            if ($stmt->execute()) {
                $insertados++;
            } else {
                $errores++;
            }
            $stmt->close();
        }

        // Construir mensaje de resultado
        $mensaje = "";
        if ($insertados > 0) {
            $mensaje .= "✅ $insertados estudiantes inscritos correctamente";
        }
        if ($duplicados > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "⚠️ $duplicados ya estaban inscritos";
        }
        if ($alumnos_invalidos > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "❌ $alumnos_invalidos estudiantes inválidos";
        }
        if ($errores > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "❌ $errores errores al guardar";
        }

        if (empty($mensaje)) {
            $mensaje = "No se pudo realizar ninguna inscripción";
            $tipo = 'error';
        } else {
            $tipo = ($insertados > 0) ? 'exito' : 'error';
        }

        header("Location: ../inscripciones.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
        exit;
        break;

    // =============================================
    // CASO: editar - Editar inscripción
    // =============================================
    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $id_alumno = intval($_POST['id_alumno'] ?? 0);
        $id_curso = intval($_POST['id_curso'] ?? 0);
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id <= 0 || $id_alumno <= 0 || $id_curso <= 0) {
            header('Location: ../inscripciones.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        // Verificar que el estudiante no esté ya inscrito en ese curso (excepto él mismo)
        $check = $conexion->query("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = $id_alumno AND id_curso = $id_curso AND id_inscripcion != $id AND estado != 'Finalizado'");
        if ($check->num_rows > 0) {
            header('Location: ../inscripciones.php?mensaje=El estudiante ya está inscrito en este curso&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            UPDATE inscripciones 
            SET id_alumno = ?, id_curso = ?, estado = ? 
            WHERE id_inscripcion = ?
        ");
        $stmt->bind_param("iisi", $id_alumno, $id_curso, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../inscripciones.php?mensaje=Inscripción actualizada&tipo=exito');
        exit;
        break;

    // =============================================
    // CASO: deshabilitar - Deshabilitar inscripción
    // =============================================
    case 'deshabilitar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../inscripciones.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE inscripciones SET estado = 'Inactivo' WHERE id_inscripcion = $id");
        header('Location: ../inscripciones.php?mensaje=Inscripción desactivada&tipo=exito');
        exit;
        break;

    // =============================================
    // CASO: eliminar - Eliminar inscripción
    // =============================================
    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../inscripciones.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("DELETE FROM inscripciones WHERE id_inscripcion = $id");
        header('Location: ../inscripciones.php?mensaje=Inscripción eliminada&tipo=exito');
        exit;
        break;

    // =============================================
    // CASO: obtener - Obtener datos para editar
    // =============================================
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM inscripciones WHERE id_inscripcion = $id");
        $inscripcion = $resultado->fetch_assoc();
        echo json_encode($inscripcion);
        exit;
        break;

    // =============================================
    // CASO: guardar_masivo - Inscripción masiva (desde modal independiente)
    // =============================================
    case 'guardar_masivo':
        $id_curso = intval($_POST['id_curso'] ?? 0);
        $alumnos_seleccionados = $_POST['alumnos'] ?? [];
        $estado = $_POST['estado'] ?? 'Activo';
        
        if ($id_curso <= 0) {
            header('Location: ../inscripciones.php?mensaje=Selecciona un curso válido&tipo=error');
            exit;
        }
        
        if (empty($alumnos_seleccionados) || !is_array($alumnos_seleccionados)) {
            header('Location: ../inscripciones.php?mensaje=Selecciona al menos un estudiante&tipo=error');
            exit;
        }
        
        $stmt = $conexion->prepare("SELECT id_curso, nombre FROM cursos WHERE id_curso = ? AND estado = 'Activo'");
        $stmt->bind_param("i", $id_curso);
        $stmt->execute();
        $curso_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$curso_info) {
            header('Location: ../inscripciones.php?mensaje=El curso seleccionado no existe o no está activo&tipo=error');
            exit;
        }
        
        $insertados = 0;
        $errores = 0;
        $duplicados = 0;
        $alumnos_invalidos = 0;
        
        foreach ($alumnos_seleccionados as $id_alumno) {
            $id_alumno = intval($id_alumno);
            
            if ($id_alumno <= 0) {
                $alumnos_invalidos++;
                continue;
            }
            
            $stmt = $conexion->prepare("
                SELECT u.id_usuario 
                FROM usuarios u
                INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
                WHERE u.id_usuario = ? AND ur.id_rol = 1 AND u.estado = 'Activo'
            ");
            $stmt->bind_param("i", $id_alumno);
            $stmt->execute();
            $alumno_valido = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$alumno_valido) {
                $alumnos_invalidos++;
                continue;
            }
            
            $stmt = $conexion->prepare("
                SELECT id_inscripcion FROM inscripciones 
                WHERE id_alumno = ? AND id_curso = ? AND estado = 'Activo'
            ");
            $stmt->bind_param("ii", $id_alumno, $id_curso);
            $stmt->execute();
            $existe = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($existe) {
                $duplicados++;
                continue;
            }
            
            $stmt = $conexion->prepare("
                INSERT INTO inscripciones (id_alumno, id_curso, estado, fecha_inscripcion) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iis", $id_alumno, $id_curso, $estado);
            
            if ($stmt->execute()) {
                $insertados++;
            } else {
                $errores++;
            }
            $stmt->close();
        }
        
        $mensaje = "";
        if ($insertados > 0) {
            $mensaje .= "✅ $insertados estudiantes inscritos correctamente";
        }
        if ($duplicados > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "⚠️ $duplicados ya estaban inscritos";
        }
        if ($alumnos_invalidos > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "❌ $alumnos_invalidos estudiantes inválidos";
        }
        if ($errores > 0) {
            $mensaje .= ($mensaje ? " | " : "") . "❌ $errores errores al guardar";
        }
        
        if (empty($mensaje)) {
            $mensaje = "No se pudo realizar ninguna inscripción";
            $tipo = 'error';
        } else {
            $tipo = ($insertados > 0) ? 'exito' : 'error';
        }
        
        header("Location: ../inscripciones.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
        exit;
        break;

    default:
        header('Location: ../inscripciones.php');
        exit;
        break;
}
?>