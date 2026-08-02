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
$rol = $_POST['rol'] ?? 'Alumno';
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
if (!in_array($rol, ['Alumno', 'Docente'])) {
    $rol = 'Alumno';
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
    // ASIGNAR ROL (MANUAL)                       */
    // ========================================== */
    
    // Asignar ID de rol manualmente según lo que seleccionó el usuario
    if ($rol === 'Alumno') {
        $id_rol = 1;  // ID fijo para Alumno
    } else {
        $id_rol = 2;  // ID fijo para Docente
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