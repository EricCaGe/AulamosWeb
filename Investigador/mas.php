<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$titulo_pagina = 'Más';
$descripcion_pagina = 'Consulta otras opciones del módulo de investigación.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Más - Investigador</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="styles/mas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        <section class="opciones-mas">
            <div class="opcion-item" onclick="alert('Mi perfil - Esta pantalla se agregará posteriormente.')">
                <div class="opcion-icono"><i class="fa-solid fa-user"></i></div>
                <div class="opcion-contenido">
                    <span class="opcion-titulo">Mi perfil</span>
                    <span class="opcion-descripcion">Consulta la información de tu cuenta</span>
                </div>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="opcion-item" onclick="alert('Pruebas de investigación - Esta opción se implementará posteriormente.')">
                <div class="opcion-icono"><i class="fa-solid fa-flask"></i></div>
                <div class="opcion-contenido">
                    <span class="opcion-titulo">Pruebas de investigación</span>
                    <span class="opcion-descripcion">Consulta las pruebas realizadas en la plataforma</span>
                </div>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="opcion-item" onclick="alert('Participantes - Esta opción se implementará posteriormente.')">
                <div class="opcion-icono"><i class="fa-solid fa-users"></i></div>
                <div class="opcion-contenido">
                    <span class="opcion-titulo">Participantes</span>
                    <span class="opcion-descripcion">Consulta los estudiantes participantes</span>
                </div>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="opcion-item" onclick="alert('Configuración - Esta opción se implementará posteriormente.')">
                <div class="opcion-icono"><i class="fa-solid fa-gear"></i></div>
                <div class="opcion-contenido">
                    <span class="opcion-titulo">Configuración</span>
                    <span class="opcion-descripcion">Configura tus preferencias</span>
                </div>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="opcion-item" onclick="alert('Ayuda - Esta opción se implementará posteriormente.')">
                <div class="opcion-icono"><i class="fa-solid fa-circle-question"></i></div>
                <div class="opcion-contenido">
                    <span class="opcion-titulo">Ayuda</span>
                    <span class="opcion-descripcion">Consulta información de ayuda</span>
                </div>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </section>
        <section class="cerrar-sesion">
            <div class="cerrar-item" onclick="if(confirm('¿Deseas cerrar tu sesión?')){ window.location.href='../InicioSesion/cerrar_sesion.php'; }">
                <div class="cerrar-icono"><i class="fa-solid fa-right-from-bracket"></i></div>
                <span class="cerrar-texto">Cerrar sesión</span>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </section>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/mas.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>