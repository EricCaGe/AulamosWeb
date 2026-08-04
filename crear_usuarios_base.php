<?php
// ========================================== */
// CREAR USUARIOS BASE (ADMIN E INVESTIGADOR) */
// ========================================== */

require_once 'Conexion/conexion.php';

// Datos del Administrador
$admin_nombre = 'Admin';
$admin_apellido_paterno = 'Sistema';
$admin_apellido_materno = 'Aulamos';
$admin_correo = 'admin@aulamos.edu.mx';
$admin_password = 'Admin123!';
$admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

// Datos del Investigador
$investigador_nombre = 'Investigador';
$investigador_apellido_paterno = 'Principal';
$investigador_apellido_materno = 'Aulamos';
$investigador_correo = 'investigador@aulamos.edu.mx';
$investigador_password = 'Invest123!';
$investigador_password_hash = password_hash($investigador_password, PASSWORD_DEFAULT);

echo "<h1>📌 Creando usuarios base...</h1>";

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

    // ========================================== */
    // CREAR INVESTIGADOR                         */
    // ========================================== */
    
    // Verificar si el investigador ya existe
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $investigador_correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $investigador_existe = $resultado->fetch_assoc();
    $stmt->close();

    if (!$investigador_existe) {
        // Insertar investigador
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
        $stmt->bind_param("sssss", $investigador_nombre, $investigador_apellido_paterno, $investigador_apellido_materno, $investigador_correo, $investigador_password_hash);
        $stmt->execute();
        $id_investigador = $conexion->insert_id;
        $stmt->close();

        // Asignar rol Investigador (id_rol = 3)
        $stmt = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, 3)");
        $stmt->bind_param("i", $id_investigador);
        $stmt->execute();
        $stmt->close();

        echo "✅ Investigador creado: <strong>$investigador_correo</strong> (Contraseña: <strong>$investigador_password</strong>)<br>";
    } else {
        echo "⚠️ El investigador ya existe.<br>";
    }

    echo "<br><h3>✅ Usuarios base creados correctamente.</h3>";
    echo "<p><a href='InicioSesion/login.php'>Ir al login</a></p>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>