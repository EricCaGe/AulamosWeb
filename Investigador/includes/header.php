<?php
// ==========================================
// HEADER - INVESTIGADOR (CON FOTO DE PERFIL)
// ==========================================

// Asegurar que la sesión esté disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos del usuario desde la sesión
$id_usuario = $_SESSION['usuario']['id_usuario'] ?? 0;
$nombre_investigador = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$foto_perfil_header = $_SESSION['usuario']['foto_perfil'] ?? null;

// Si la sesión NO tiene la foto, obtenerla de la BD
if (empty($foto_perfil_header) && $id_usuario > 0) {
    require_once __DIR__ . '/../../Conexion/conexion.php';
    $stmt = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario_data = $resultado->fetch_assoc();
    if ($usuario_data && !empty($usuario_data['foto_perfil'])) {
        $foto_perfil_header = $usuario_data['foto_perfil'];
        // Guardar en sesión para futuras cargas
        $_SESSION['usuario']['foto_perfil'] = $foto_perfil_header;
    }
    $stmt->close();
}

// Determinar la ruta de la foto
if (!empty($foto_perfil_header)) {
    $ruta_foto = '../uploads/perfiles/' . $foto_perfil_header;
} else {
    $ruta_foto = 'https://placehold.co/40x40/3b71f3/white?text=👤';
}
?>

<header class="content-header">
    <div class="welcome-text">
        <h1><?php echo $titulo_pagina ?? 'Panel de investigación'; ?></h1>
        <p><?php echo $descripcion_pagina ?? 'Consulta las métricas registradas durante las pruebas de uso de la plataforma.'; ?></p>
    </div>
    <div class="header-actions">
        <div class="icon-bell">
            <i class="fa-regular fa-bell"></i>
        </div>
        <a href="perfil_investigador.php" class="user-profile" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; cursor:pointer;">
            <img src="<?php echo $ruta_foto; ?>" alt="Avatar" class="avatar">
            <span class="user-name"><?php echo htmlspecialchars($nombre_investigador); ?></span>
            <i class="fa-solid fa-chevron-down drop-icon"></i>
        </a>
        <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</header>