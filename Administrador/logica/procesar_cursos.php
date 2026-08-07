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
        $id_ciclo = intval($_POST['id_ciclo'] ?? 0);
        $id_grupo = intval($_POST['id_grupo'] ?? 0);
        $id_materia = intval($_POST['id_materia'] ?? 0);
        $id_docente = intval($_POST['id_docente'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id_ciclo <= 0 || $id_grupo <= 0 || $id_materia <= 0 || $id_docente <= 0 || empty($nombre)) {
            header('Location: ../cursos.php?mensaje=Todos los campos obligatorios deben estar llenos&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            INSERT INTO cursos (
                id_ciclo,
                id_grupo,
                id_materia,
                id_docente,
                nombre,
                descripcion,
                estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiisss", $id_ciclo, $id_grupo, $id_materia, $id_docente, $nombre, $descripcion, $estado);
        
        if ($stmt->execute()) {
            header('Location: ../cursos.php?mensaje=Curso creado exitosamente&tipo=exito');
        } else {
            header('Location: ../cursos.php?mensaje=Error al crear el curso: ' . $stmt->error . '&tipo=error');
        }
        $stmt->close();
        exit;
        break;

    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $id_ciclo = intval($_POST['id_ciclo'] ?? 0);
        $id_grupo = intval($_POST['id_grupo'] ?? 0);
        $id_materia = intval($_POST['id_materia'] ?? 0);
        $id_docente = intval($_POST['id_docente'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id <= 0 || $id_ciclo <= 0 || $id_grupo <= 0 || $id_materia <= 0 || $id_docente <= 0 || empty($nombre)) {
            header('Location: ../cursos.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            UPDATE cursos 
            SET id_ciclo = ?,
                id_grupo = ?,
                id_materia = ?,
                id_docente = ?,
                nombre = ?,
                descripcion = ?,
                estado = ?
            WHERE id_curso = ?
        ");
        $stmt->bind_param("iiiiissi", $id_ciclo, $id_grupo, $id_materia, $id_docente, $nombre, $descripcion, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../cursos.php?mensaje=Curso actualizado&tipo=exito');
        exit;
        break;

    case 'deshabilitar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../cursos.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE cursos SET estado = 'Inactivo' WHERE id_curso = $id");
        header('Location: ../cursos.php?mensaje=Curso desactivado&tipo=exito');
        exit;
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../cursos.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("DELETE FROM cursos WHERE id_curso = $id");
        header('Location: ../cursos.php?mensaje=Curso eliminado&tipo=exito');
        exit;
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM cursos WHERE id_curso = $id");
        $curso = $resultado->fetch_assoc();
        echo json_encode($curso);
        exit;
        break;

    default:
        header('Location: ../cursos.php');
        exit;
        break;
}
?>