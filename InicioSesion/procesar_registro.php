<?php
// ========================================== */
// PROCESAR REGISTRO                         */
// ========================================== */

require_once '../Conexion/conexion.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php?error=sesion');
    exit;
}

// Obtener datos del formulario
$rol = $_POST['rol'] ?? 'alumno';  // Valor por defecto si no llega
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');

// Validar que los campos no estén vacíos
if (empty($correo) || empty($password) || empty($nombre) || empty($apellido_paterno) || empty($apellido_materno)) {
    header('Location: registro.php?error=campos_vacios');
    exit;
}

// Validar que el rol sea válido
if (!in_array($rol, ['alumno', 'docente'])) {
    $rol = 'alumno';  // Forzar a alumno si no es válido
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: registro.php?error=correo_invalido');
    exit;
}

// Validar longitud de contraseña (mínimo 8 caracteres)
if (strlen($password) < 8) {
    header('Location: registro.php?error=password_corta');
    exit;
}

try {
    // ========================================== */
    // VERIFICAR SI EL CORREO YA EXISTE          */
    // ========================================== */
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->fetch_assoc()) {
        header('Location: registro.php?error=correo_existe');
        exit;
    }
    $stmt->close();
    
    // ========================================== */
    // INSERTAR USUARIO                          */
    // ========================================== */
    
    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (
            nombre, 
            apellido_paterno, 
            apellido_materno, 
            correo, 
            password_hash, 
            estado,
            fecha_registro
        ) VALUES (?, ?, ?, ?, ?, 'Activo', NOW())
    ");
    $stmt->bind_param("sssss", $nombre, $apellido_paterno, $apellido_materno, $correo, $password_hash);
    $stmt->execute();
    $id_usuario = $conexion->insert_id;
    $stmt->close();
    
    // ========================================== */
    // ASIGNAR ROL                               */
    // ========================================== */
    
    // Obtener el ID del rol
    $stmt = $conexion->prepare("SELECT id_rol FROM roles WHERE nombre = ?");
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $rol_data = $resultado->fetch_assoc();
    $stmt->close();
    
    if (!$rol_data) {
        // Si el rol no existe, lo creamos
        $descripcion = ($rol === 'alumno') ? 'Usuario estudiante de la plataforma' : 'Usuario profesor de la plataforma';
        $stmt = $conexion->prepare("INSERT INTO roles (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $rol, $descripcion);
        $stmt->execute();
        $id_rol = $conexion->insert_id;
        $stmt->close();
    } else {
        $id_rol = $rol_data['id_rol'];
    }
    
    // Asignar rol al usuario
    $stmt = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_usuario, $id_rol);
    $stmt->execute();
    $stmt->close();
    
    // ========================================== */
    // REDIRIGIR AL LOGIN                        */
    // ========================================== */
    
    header('Location: login.php?registro=exitoso');
    exit;
    
} catch(Exception $e) {
    // Error de base de datos
    error_log("Error en registro: " . $e->getMessage());
    header('Location: registro.php?error=sesion');
    exit;
}
?>