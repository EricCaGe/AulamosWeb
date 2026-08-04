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
        $nombre = trim($_POST['nombre'] ?? '');
        $campo_formativo = trim($_POST['campo_formativo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Activa';

        if (empty($nombre) || empty($campo_formativo)) {
            header('Location: ../materias.php?mensaje=El nombre y el campo formativo son obligatorios&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO materias (nombre, campo_formativo, descripcion, estado) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $campo_formativo, $descripcion, $estado);
        $stmt->execute();
        $stmt->close();

        header('Location: ../materias.php?mensaje=Materia creada exitosamente&tipo=exito');
        exit;
        break;

    case 'editar':
        $id = $_POST['id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $campo_formativo = trim($_POST['campo_formativo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Activa';

        if ($id <= 0 || empty($nombre) || empty($campo_formativo)) {
            header('Location: ../materias.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("UPDATE materias SET nombre = ?, campo_formativo = ?, descripcion = ?, estado = ? WHERE id_materia = ?");
        $stmt->bind_param("ssssi", $nombre, $campo_formativo, $descripcion, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../materias.php?mensaje=Materia actualizada&tipo=exito');
        exit;
        break;

    case 'deshabilitar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../materias.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE materias SET estado = 'Inactiva' WHERE id_materia = $id");
        header('Location: ../materias.php?mensaje=Materia deshabilitada&tipo=exito');
        exit;
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../materias.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("DELETE FROM materias WHERE id_materia = $id");
        header('Location: ../materias.php?mensaje=Materia eliminada&tipo=exito');
        exit;
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM materias WHERE id_materia = $id");
        $materia = $resultado->fetch_assoc();
        echo json_encode($materia);
        exit;
        break;

    default:
        header('Location: ../materias.php');
        exit;
        break;
}
?>