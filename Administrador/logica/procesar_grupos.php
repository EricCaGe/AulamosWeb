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
        $id_docente = intval($_POST['id_docente'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $grado = $_POST['grado'] ?? '';
        $turno = $_POST['turno'] ?? '';
        $modalidad = $_POST['modalidad'] ?? '';
        $cupo_maximo = intval($_POST['cupo_maximo'] ?? 30);
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id_ciclo <= 0 || $id_docente <= 0 || empty($nombre) || empty($turno) || empty($modalidad)) {
            header('Location: ../grupos.php?mensaje=Todos los campos obligatorios deben estar llenos&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            INSERT INTO grupos (
                id_ciclo, 
                id_docente, 
                nombre, 
                grado, 
                turno, 
                modalidad, 
                cupo_maximo, 
                estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissssis", $id_ciclo, $id_docente, $nombre, $grado, $turno, $modalidad, $cupo_maximo, $estado);
        
        if ($stmt->execute()) {
            header('Location: ../grupos.php?mensaje=Grupo creado exitosamente&tipo=exito');
        } else {
            header('Location: ../grupos.php?mensaje=Error al crear el grupo: ' . $stmt->error . '&tipo=error');
        }
        $stmt->close();
        exit;
        break;

    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $id_ciclo = intval($_POST['id_ciclo'] ?? 0);
        $id_docente = intval($_POST['id_docente'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $grado = $_POST['grado'] ?? '';
        $turno = $_POST['turno'] ?? '';
        $modalidad = $_POST['modalidad'] ?? '';
        $cupo_maximo = intval($_POST['cupo_maximo'] ?? 30);
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id <= 0 || $id_ciclo <= 0 || $id_docente <= 0 || empty($nombre)) {
            header('Location: ../grupos.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("
            UPDATE grupos 
            SET id_ciclo = ?, 
                id_docente = ?, 
                nombre = ?, 
                grado = ?, 
                turno = ?, 
                modalidad = ?, 
                cupo_maximo = ?, 
                estado = ? 
            WHERE id_grupo = ?
        ");
        $stmt->bind_param("iissssisi", $id_ciclo, $id_docente, $nombre, $grado, $turno, $modalidad, $cupo_maximo, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../grupos.php?mensaje=Grupo actualizado&tipo=exito');
        exit;
        break;

    case 'deshabilitar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../grupos.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE grupos SET estado = 'Inactivo' WHERE id_grupo = $id");
        header('Location: ../grupos.php?mensaje=Grupo desactivado&tipo=exito');
        exit;
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../grupos.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("DELETE FROM grupos WHERE id_grupo = $id");
        header('Location: ../grupos.php?mensaje=Grupo eliminado&tipo=exito');
        exit;
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM grupos WHERE id_grupo = $id");
        $grupo = $resultado->fetch_assoc();
        echo json_encode($grupo);
        exit;
        break;

    default:
        header('Location: ../grupos.php');
        exit;
        break;
}
?>