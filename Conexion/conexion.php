<?php
// ============================================
// CONEXIÓN A LA BASE DE DATOS - AULAMOS
// PARA USO DE TODO EL EQUIPO
// ============================================

// Configuración de la conexión
$host = 'localhost';
$usuario = 'root';
$password = '12345678';
$basedatos = 'aulamos_mvp';

// Crear la conexión
$conexion = new mysqli($host, $usuario, $password, $basedatos);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer el conjunto de caracteres a UTF-8
$conexion->set_charset("utf8mb4");

// Zona horaria de México
date_default_timezone_set('America/Mexico_City');

// Variable para saber si la conexión fue exitosa
$conexion_exitosa = true;
?>