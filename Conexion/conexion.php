<?php
// ============================================
// CONEXIÓN A LA BASE DE DATOS - AULAMOS
// PARA USO DE TODO EL EQUIPO
// ============================================

// Configuración de la conexión
//$host = '10.2.2.43';
//$host = '10.2.0.234';
$host = 'localhost';
$usuario = 'root';
// .$password = '';
//$password = '12345678';
//$password = 'Jaziel123';
$password = 'qwerty1234.'; //Obregon
$basedatos = 'aulamos_mvp';
$puerto = 3306;

/*$host = '10.2.2.43';
$usuario = 'aulamos';
$password = 'E12345678!';
$basedatos = 'aulamos_mvp';
$puerto = 3306;*/

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