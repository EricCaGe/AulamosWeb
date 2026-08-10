<?php
// ========================================== */
// CREAR USUARIO INVESTIGADOR                 */
// ========================================== */

require_once 'Conexion/conexion.php';

// Datos del Investigador
$investigador_nombre = 'María';
$investigador_apellido_paterno = 'López';
$investigador_apellido_materno = 'Hernández';
$investigador_correo = 'investigador@aulamos.com';
$investigador_password = 'Invest123!';
$investigador_password_hash = password_hash($investigador_password, PASSWORD_DEFAULT);

try {
    // ========================================== */
    // VERIFICAR SI EL INVESTIGADOR YA EXISTE    */
    // ========================================== */
    
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $investigador_correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $investigador_existe = $resultado->fetch_assoc();
    $stmt->close();

    if (!$investigador_existe) {
        // ========================================== */
        // INSERTAR INVESTIGADOR                      */
        // ========================================== */
        
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
        $stmt->bind_param(
            "sssss", 
            $investigador_nombre, 
            $investigador_apellido_paterno, 
            $investigador_apellido_materno, 
            $investigador_correo, 
            $investigador_password_hash
        );
        $stmt->execute();
        $id_investigador = $conexion->insert_id;
        $stmt->close();

        // ========================================== */
        // ASIGNAR ROL INVESTIGADOR (id_rol = 3)     */
        // ========================================== */
        
        $stmt = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, 3)");
        $stmt->bind_param("i", $id_investigador);
        $stmt->execute();
        $stmt->close();

        // ========================================== */
        // CREAR PREFERENCIAS DE ACCESIBILIDAD BASE   */
        // ========================================== */
        
        $stmt = $conexion->prepare("
            INSERT INTO preferencias_accesibilidad (
                id_usuario,
                alto_contraste,
                modo_oscuro,
                tamano_texto,
                fuente_dislexia,
                lector_pantalla,
                velocidad_lectura,
                subtitulos,
                idioma,
                animaciones,
                navegacion_teclado
            ) VALUES (?, 0, 0, 'Normal', 0, 0, 1.0, 0, 'Español', 1, 0)
        ");
        $stmt->bind_param("i", $id_investigador);
        $stmt->execute();
        $stmt->close();

        echo "✅ Investigador creado exitosamente:<br>";
        echo "📧 Correo: <strong>$investigador_correo</strong><br>";
        echo "🔑 Contraseña: <strong>$investigador_password</strong><br>";
        echo "👤 Nombre: <strong>$investigador_nombre $investigador_apellido_paterno $investigador_apellido_materno</strong><br>";
        echo "🎯 Rol: <strong>Investigador</strong><br>";
        echo "♿ Preferencias de accesibilidad: <strong>Creadas</strong><br>";

    } else {
        echo "⚠️ El investigador ya existe.<br>";
        echo "📧 Correo: <strong>$investigador_correo</strong><br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    error_log("Error en crear_investigador: " . $e->getMessage());
}
?>