<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    
    // =============================================
    // GUARDAR PARTICIPANTES
    // =============================================
    case 'guardar_participantes':
        $id_prueba = intval($_POST['id_prueba'] ?? 0);
        $participantes_data = $_POST['participantes'] ?? [];
        
        if ($id_prueba <= 0) {
            header('Location: ../ver_prueba.php?id=' . $id_prueba . '&mensaje=ID de prueba inválido&tipo=error');
            exit;
        }
        
        // Eliminar todos los participantes actuales
        $conexion->query("DELETE FROM participantes_prueba WHERE id_prueba = $id_prueba");
        
        // Insertar nuevos participantes
        $insertados = 0;
        foreach ($participantes_data as $id_usuario => $data) {
            // Solo insertar si el checkbox de selección está marcado
            if (!isset($data['seleccionado']) || $data['seleccionado'] != 1) {
                continue;
            }
            
            $grupo = $data['grupo'] ?? 'Control';
            $consentimiento = isset($data['consentimiento']) ? 1 : 0;
            
            $stmt = $conexion->prepare("
                INSERT INTO participantes_prueba (id_prueba, id_usuario, grupo_experimental, consentimiento) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iisi", $id_prueba, $id_usuario, $grupo, $consentimiento);
            
            if ($stmt->execute()) {
                $insertados++;
            }
            $stmt->close();
        }
        
        header("Location: ../ver_prueba.php?id=$id_prueba&mensaje=" . urlencode("$insertados participantes actualizados correctamente") . "&tipo=exito");
        exit;
        break;
    
    // =============================================
    // OBTENER CONTADORES DE PRUEBAS
    // =============================================
    case 'obtener_contadores':
        $total = $conexion->query("SELECT COUNT(*) FROM pruebas_investigacion")->fetch_row()[0] ?? 0;
        $activas = $conexion->query("SELECT COUNT(*) FROM pruebas_investigacion WHERE estado = 'Activa'")->fetch_row()[0] ?? 0;
        
        echo json_encode([
            'success' => true,
            'total' => $total,
            'activas' => $activas
        ]);
        exit;
        break;
    
    default:
        header('Location: ../pruebas_investigacion.php');
        exit;
        break;
}
?>