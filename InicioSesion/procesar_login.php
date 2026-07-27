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
$rol = $_POST['rol'] ?? '';
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Validar que los campos no estén vacíos
if (empty($rol) || empty($correo) || empty($password)) {
    header('Location: login.php?error=sesion');
    exit;
}

// Validar que el rol sea válido
if (!in_array($rol, ['alumno', 'docente'])) {
    header('Location: login.php?error=sesion');
    exit;
}

try {
    // Buscar usuario por correo
    $stmt = $pdo->prepare("
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
        WHERE u.correo = :correo AND r.nombre = :rol
    ");
    
    $stmt->execute([
        ':correo' => $correo,
        ':rol' => $rol
    ]);
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    
    $_SESSION['usuario'] = [
        'id_usuario' => $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido_paterno' => $usuario['apellido_paterno'],
        'apellido_materno' => $usuario['apellido_materno'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol']
    ];
    
    // Actualizar último acceso
    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id");
    $stmt->execute([':id' => $usuario['id_usuario']]);
    
    // Redirigir según el rol
    if ($rol === 'alumno') {
        header('Location: ../alumno/alumno.php');
    } else {
        header('Location: ../docente/docente.php');
    }
    exit;
    
} catch(PDOException $e) {
    // Error de base de datos
    error_log("Error en login: " . $e->getMessage());
    header('Location: login.php?error=sesion');
    exit;
}
?>