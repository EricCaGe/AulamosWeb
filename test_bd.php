<?php
require_once 'Conexion/conexion.php';

echo "<h1>🔌 Verificación de conexión - AULAMOS</h1>";

// Verificar conexión
if ($conexion_exitosa) {
    echo "<p style='color:green;'>✅ Conexión exitosa a <strong>$basedatos</strong></p>";
    
    // Contar usuarios
    $resultado = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
    $fila = $resultado->fetch_assoc();
    echo "<p>📊 Total de usuarios registrados: <strong>" . $fila['total'] . "</strong></p>";
    
    // Mostrar roles
    echo "<h2>Roles disponibles:</h2>";
    $roles = $conexion->query("SELECT * FROM roles");
    echo "<ul>";
    while ($rol = $roles->fetch_assoc()) {
        echo "<li><strong>" . $rol['nombre'] . "</strong>: " . $rol['descripcion'] . "</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p style='color:red;'>❌ Error de conexión</p>";
}
?>