<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    
    // ========================================== */
    // GUARDAR NUEVO CICLO                        */
    // ========================================== */
    case 'guardar':
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Activo';

        if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            header('Location: ../ciclos_escolares.php?mensaje=Todos los campos son obligatorios&tipo=error');
            exit;
        }

        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            header('Location: ../ciclos_escolares.php?mensaje=La fecha de finalización debe ser posterior a la de inicio&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $fecha_inicio, $fecha_fin, $estado);
        $stmt->execute();
        $stmt->close();

        header('Location: ../ciclos_escolares.php?mensaje=Ciclo creado exitosamente&tipo=exito');
        exit;
        break;

    // ========================================== */
    // EDITAR CICLO                               */
    // ========================================== */
    case 'editar':
        $id = $_POST['id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Activo';

        if ($id <= 0 || empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            header('Location: ../ciclos_escolares.php?mensaje=Datos incompletos&tipo=error');
            exit;
        }

        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            header('Location: ../ciclos_escolares.php?mensaje=La fecha de finalización debe ser posterior a la de inicio&tipo=error');
            exit;
        }

        $stmt = $conexion->prepare("UPDATE ciclos_escolares SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id_ciclo = ?");
        $stmt->bind_param("ssssi", $nombre, $fecha_inicio, $fecha_fin, $estado, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../ciclos_escolares.php?mensaje=Ciclo actualizado&tipo=exito');
        exit;
        break;

    // ========================================== */
    // CERRAR CICLO                               */
    // ========================================== */
    case 'cerrar':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            header('Location: ../ciclos_escolares.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        $conexion->query("UPDATE ciclos_escolares SET estado = 'Cerrado' WHERE id_ciclo = $id");
        header('Location: ../ciclos_escolares.php?mensaje=Ciclo cerrado correctamente&tipo=exito');
        exit;
        break;

    // ========================================== */
    // OBTENER DATOS DE UN CICLO (para editar)   */
    // ========================================== */
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $resultado = $conexion->query("SELECT * FROM ciclos_escolares WHERE id_ciclo = $id");
        $ciclo = $resultado->fetch_assoc();
        echo json_encode($ciclo);
        exit;
        break;

    default:
        header('Location: ../ciclos_escolares.php');
        exit;
        break;
}
?>