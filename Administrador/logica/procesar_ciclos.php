<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

// ========================================== */
// FUNCIONES DE VALIDACIÓN (ADAPTADAS DE ELLA) */
// ========================================== */

function limpiarTexto($valor) {
    if (!is_string($valor)) {
        return '';
    }
    return trim(preg_replace('/\s+/', ' ', $valor));
}

function idValido($valor) {
    $id = intval($valor);
    return $id > 0;
}

function fechaValida($valor) {
    if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return false;
    }
    $partes = explode('-', $valor);
    $anio = intval($partes[0]);
    $mes = intval($partes[1]);
    $dia = intval($partes[2]);
    return checkdate($mes, $dia, $anio);
}

function validarCiclo($nombre, $fecha_inicio, $fecha_fin, $estado) {
    $nombreLimpio = limpiarTexto($nombre);
    $estadosValidos = ['Activo', 'Inactivo', 'Cerrado'];

    if (empty($nombreLimpio)) {
        return ['valido' => false, 'campo' => 'nombre', 'mensaje' => 'El nombre del ciclo escolar es obligatorio'];
    }

    if (strlen($nombreLimpio) < 4) {
        return ['valido' => false, 'campo' => 'nombre', 'mensaje' => 'El nombre del ciclo debe tener al menos 4 caracteres'];
    }

    if (strlen($nombreLimpio) > 100) {
        return ['valido' => false, 'campo' => 'nombre', 'mensaje' => 'El nombre del ciclo no puede superar los 100 caracteres'];
    }

    if (!fechaValida($fecha_inicio)) {
        return ['valido' => false, 'campo' => 'fecha_inicio', 'mensaje' => 'La fecha de inicio no es válida. Utiliza el formato AAAA-MM-DD'];
    }

    if (!fechaValida($fecha_fin)) {
        return ['valido' => false, 'campo' => 'fecha_fin', 'mensaje' => 'La fecha final no es válida. Utiliza el formato AAAA-MM-DD'];
    }

    if ($fecha_inicio >= $fecha_fin) {
        return ['valido' => false, 'campo' => 'fecha_fin', 'mensaje' => 'La fecha final debe ser posterior a la fecha de inicio'];
    }

    if (!in_array($estado, $estadosValidos)) {
        return ['valido' => false, 'campo' => 'estado', 'mensaje' => 'El estado debe ser Activo, Inactivo o Cerrado'];
    }

    return [
        'valido' => true,
        'datos' => [
            'nombre' => $nombreLimpio,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'estado' => $estado
        ]
    ];
}

function buscarNombreDuplicado($conexion, $nombre, $idExcluir = 0) {
    $stmt = $conexion->prepare("SELECT id_ciclo FROM ciclos_escolares WHERE LOWER(nombre) = LOWER(?) AND id_ciclo <> ? LIMIT 1");
    $stmt->bind_param("si", $nombre, $idExcluir);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $existe = $resultado->num_rows > 0;
    $stmt->close();
    return $existe;
}

function buscarCicloSuperpuesto($conexion, $fechaInicio, $fechaFin, $idExcluir = 0) {
    $stmt = $conexion->prepare("
        SELECT id_ciclo, nombre, fecha_inicio, fecha_fin 
        FROM ciclos_escolares 
        WHERE id_ciclo <> ? AND fecha_inicio <= ? AND fecha_fin >= ? 
        LIMIT 1
    ");
    $stmt->bind_param("iss", $idExcluir, $fechaFin, $fechaInicio);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $ciclo = $resultado->fetch_assoc();
    $stmt->close();
    return $ciclo;
}

function buscarOtroCicloActivo($conexion, $idExcluir = 0) {
    $stmt = $conexion->prepare("SELECT id_ciclo, nombre FROM ciclos_escolares WHERE estado = 'Activo' AND id_ciclo <> ? LIMIT 1");
    $stmt->bind_param("i", $idExcluir);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $ciclo = $resultado->fetch_assoc();
    $stmt->close();
    return $ciclo;
}

// ========================================== */
// PROCESAR ACCIÓN                           */
// ========================================== */

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {

    // ========================================== */
    // GUARDAR NUEVO CICLO                        */
    // ========================================== */
    case 'guardar':
        $nombre = $_POST['nombre'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Inactivo';

        // Validar datos
        $validacion = validarCiclo($nombre, $fecha_inicio, $fecha_fin, $estado);
        if (!$validacion['valido']) {
            header('Location: ../ciclos_escolares.php?mensaje=' . urlencode($validacion['mensaje']) . '&tipo=error');
            exit;
        }

        $datos = $validacion['datos'];

        // Verificar nombre duplicado
        if (buscarNombreDuplicado($conexion, $datos['nombre'])) {
            header('Location: ../ciclos_escolares.php?mensaje=Ya existe un ciclo escolar con ese nombre&tipo=error');
            exit;
        }

        // Verificar superposición de fechas
        $superpuesto = buscarCicloSuperpuesto($conexion, $datos['fecha_inicio'], $datos['fecha_fin']);
        if ($superpuesto) {
            header('Location: ../ciclos_escolares.php?mensaje=Las fechas coinciden con el ciclo ' . $superpuesto['nombre'] . '&tipo=error');
            exit;
        }

        // Si es Activo, verificar que no haya otro activo
        if ($datos['estado'] === 'Activo') {
            $otroActivo = buscarOtroCicloActivo($conexion);
            if ($otroActivo) {
                header('Location: ../ciclos_escolares.php?mensaje=Ya existe un ciclo activo: ' . $otroActivo['nombre'] . '&tipo=error');
                exit;
            }
        }

        // Insertar
        $stmt = $conexion->prepare("
            INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, estado) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $datos['nombre'], $datos['fecha_inicio'], $datos['fecha_fin'], $datos['estado']);
        $stmt->execute();
        $stmt->close();

        header('Location: ../ciclos_escolares.php?mensaje=Ciclo creado exitosamente&tipo=exito');
        exit;
        break;

    // ========================================== */
    // EDITAR CICLO                               */
    // ========================================== */
    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $estado = $_POST['estado'] ?? 'Inactivo';

        if ($id <= 0) {
            header('Location: ../ciclos_escolares.php?mensaje=ID inválido&tipo=error');
            exit;
        }

        // Validar datos
        $validacion = validarCiclo($nombre, $fecha_inicio, $fecha_fin, $estado);
        if (!$validacion['valido']) {
            header('Location: ../ciclos_escolares.php?mensaje=' . urlencode($validacion['mensaje']) . '&tipo=error');
            exit;
        }

        $datos = $validacion['datos'];

        // Verificar nombre duplicado (excluyendo el actual)
        if (buscarNombreDuplicado($conexion, $datos['nombre'], $id)) {
            header('Location: ../ciclos_escolares.php?mensaje=Ya existe otro ciclo escolar con ese nombre&tipo=error');
            exit;
        }

        // Verificar superposición de fechas (excluyendo el actual)
        $superpuesto = buscarCicloSuperpuesto($conexion, $datos['fecha_inicio'], $datos['fecha_fin'], $id);
        if ($superpuesto) {
            header('Location: ../ciclos_escolares.php?mensaje=Las fechas coinciden con el ciclo ' . $superpuesto['nombre'] . '&tipo=error');
            exit;
        }

        // Si es Activo, verificar que no haya otro activo (excluyendo el actual)
        if ($datos['estado'] === 'Activo') {
            $otroActivo = buscarOtroCicloActivo($conexion, $id);
            if ($otroActivo) {
                header('Location: ../ciclos_escolares.php?mensaje=Ya existe un ciclo activo: ' . $otroActivo['nombre'] . '&tipo=error');
                exit;
            }
        }

        // Actualizar
        $stmt = $conexion->prepare("
            UPDATE ciclos_escolares 
            SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? 
            WHERE id_ciclo = ?
        ");
        $stmt->bind_param("ssssi", $datos['nombre'], $datos['fecha_inicio'], $datos['fecha_fin'], $datos['estado'], $id);
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