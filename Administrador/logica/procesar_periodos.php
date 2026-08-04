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
        $id_ciclo = $_POST['id_ciclo'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id_ciclo <= 0 || empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            header('Location: ../periodos.php?mensaje=Todos los campos son obligatorios&tipo=error');
            exit;
        }

        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            header('Location: ../periodos.php?mensaje=La fecha final debe ser posterior a la de inicio&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO periodos_evaluacion (id_ciclo, nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_ciclo, $nombre, $fecha_inicio, $fecha_fin, $estado);
        $stmt->execute();
        $stmt->close();

        header('Location: ../periodos.php?mensaje=Periodo creado exitosamente&tipo=exito');
        exit;
        break;

    case 'editar':
        $id = $_POST['id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id <= 0 || empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            header('Location: ../periodos.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            header('Location: ../periodos.php?mensaje=La fecha final debe ser posterior a la de inicio&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("UPDATE periodos_evaluacion SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id_periodo = ?");
        $stmt->bind_param("ssssi", $nombre, $fecha_inicio, $fecha_fin, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../periodos.php?mensaje=Periodo actualizado&tipo=exito');
        exit;
        break;

    case 'cerrar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../periodos.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE periodos_evaluacion SET estado = 'Cerrado' WHERE id_periodo = $id");
        header('Location: ../periodos.php?mensaje=Periodo cerrado&tipo=exito');
        exit;
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM periodos_evaluacion WHERE id_periodo = $id");
        $periodo = $resultado->fetch_assoc();
        echo json_encode($periodo);
        exit;
        break;

    default:
        header('Location: ../periodos.php');
        exit;
        break;
}
?>