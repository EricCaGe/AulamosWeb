<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    
    case 'guardar':
        $id_alumno = intval($_POST['id_alumno'] ?? 0);
        $id_curso = intval($_POST['id_curso'] ?? 0);
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id_alumno <= 0 || $id_curso <= 0) {
            header('Location: ../inscripciones.php?mensaje=Debes seleccionar un estudiante y un curso&tipo=error');
            exit;
        }

        // Verificar que el estudiante no esté ya inscrito en ese curso
        $check = $conexion->query("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = $id_alumno AND id_curso = $id_curso");
        if ($check->num_rows > 0) {
            header('Location: ../inscripciones.php?mensaje=El estudiante ya está inscrito en este curso&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            INSERT INTO inscripciones (id_alumno, id_curso, estado) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $id_alumno, $id_curso, $estado);
        
        if ($stmt->execute()) {
            header('Location: ../inscripciones.php?mensaje=Inscripción creada exitosamente&tipo=exito');
        } else {
            header('Location: ../inscripciones.php?mensaje=Error al crear la inscripción: ' . $stmt->error . '&tipo=error');
        }
        $stmt->close();
        exit;
        break;

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
        $check = $conexion->query("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = $id_alumno AND id_curso = $id_curso AND id_inscripcion != $id");
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

    default:
        header('Location: ../inscripciones.php');
        exit;
        break;
}
?>