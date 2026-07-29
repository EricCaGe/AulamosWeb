<?php
// ========================================== */
// PROCESAR LOGIN                            */
// ========================================== */

require_once '../Conexion/conexion.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=sesion');
    exit;
}

// Obtener datos del formulario
$rol = $_POST['rol'] ?? 'alumno';  // Valor por defecto
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Validar que los campos no estén vacíos
if (empty($correo) || empty($password)) {
    header('Location: login.php?error=sesion');
    exit;
}

// Validar que el rol sea válido, si no forzar a alumno
if (!in_array($rol, ['alumno', 'docente'])) {
    $rol = 'alumno';
}

try {
    // Buscar usuario por correo y rol
    $stmt = $conexion->prepare("
        SELECT 
            u.id_usuario,
            u.nombre,
            u.apellido_paterno,
            u.apellido_materno,
            u.correo,
            u.password_hash,
            u.estado,
            r.nombre AS rol
        FROM usuarios u
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        INNER JOIN roles r ON ur.id_rol = r.id_rol
        WHERE u.correo = ? AND r.nombre = ?
    ");
    
    $stmt->bind_param("ss", $correo, $rol);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();
    
    // Verificar si el usuario existe
    if (!$usuario) {
        header('Location: login.php?error=credenciales');
        exit;
    }
    
    // Verificar estado del usuario
    if ($usuario['estado'] === 'Inactivo') {
        header('Location: login.php?error=inactivo');
        exit;
    }
    
    if ($usuario['estado'] === 'Bloqueado') {
        header('Location: login.php?error=bloqueado');
        exit;
    }
    
    // Verificar contraseña
    if (!password_verify($password, $usuario['password_hash'])) {
        header('Location: login.php?error=credenciales');
        exit;
    }
    
    // ========================================== */
    // INICIAR SESIÓN                            */
    // ========================================== */
    
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['usuario'] = [
        'id_usuario' => $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido_paterno' => $usuario['apellido_paterno'],
        'apellido_materno' => $usuario['apellido_materno'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol']
    ];
    
    // Actualizar último acceso
    $stmt = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
    $stmt->bind_param("i", $usuario['id_usuario']);
    $stmt->execute();
    $stmt->close();
    
    // Redirigir según el rol
    if ($rol === 'alumno') {
        header('Location: ../Alumno/alumno.php');
    } else {
        header('Location: ../Docente/docente.php');
    }
    exit;
    
} catch(Exception $e) {
    // Error de base de datos
    error_log("Error en login: " . $e->getMessage());
    header('Location: login.php?error=sesion');
    exit;
}
?>