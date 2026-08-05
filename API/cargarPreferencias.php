<?php
session_start();
require_once "../Conexion/conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "success"=>false
    ]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT *
        FROM preferencias_accesibilidad
        WHERE id_usuario=?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i",$id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows>0){

    echo json_encode([
        "success"=>true,
        "preferencias"=>$resultado->fetch_assoc()
    ]);

}else{

    echo json_encode([
        "success"=>false
    ]);

}