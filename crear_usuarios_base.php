<?php
// ========================================== */
// CREAR USUARIOS BASE (ADMIN E INVESTIGADOR) */
// ========================================== */

require_once 'Conexion/conexion.php';

// Datos del Administrador
$admin_nombre = 'Eric';
$admin_apellido_paterno = 'Candelaria';
$admin_apellido_materno = 'García';
$admin_correo = '230110496@itsoeh.edu.mx';
$admin_password = 'Admin123!';
$admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);



try {
    // ========================================== */
    // CREAR ADMINISTRADOR                        */
    // ========================================== */
    
    // Verificar si el admin ya existe
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $admin_correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $admin_existe = $resultado->fetch_assoc();
    $stmt->close();

    if (!$admin_existe) {
        // Insertar admin
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
        $stmt->bind_param("sssss", $admin_nombre, $admin_apellido_paterno, $admin_apellido_materno, $admin_correo, $admin_password_hash);
        $stmt->execute();
        $id_admin = $conexion->insert_id;
        $stmt->close();

        // Asignar rol Admin (id_rol = 4)
        $stmt = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, 4)");
        $stmt->bind_param("i", $id_admin);
        $stmt->execute();
        $stmt->close();

        echo "✅ Administrador creado: <strong>$admin_correo</strong> (Contraseña: <strong>$admin_password</strong>)<br>";
    } else {
        echo "⚠️ El administrador ya existe.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    error_log("Error en crear_usuarios_base: " . $e->getMessage());
}
?>