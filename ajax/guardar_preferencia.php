<?php
session_start();
require_once "../Conexion/conexion.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);
    exit;
}

$id_usuario = $_POST["id_usuario"] ?? "";
$pref = $_POST["pref"] ?? "";
$value = $_POST["value"] ?? "";

$columnasPermitidas = [
    "alto_contraste",
    "modo_oscuro",
    "tamano_texto",
    "lector_pantalla",
    "subtitulos",
    "navegacion_teclado"
];

if (!in_array($pref, $columnasPermitidas)) {
    echo json_encode([
        "success" => false,
        "message" => "Preferencia inválida"
    ]);
    exit;
}

$sql = "UPDATE preferencias_accesibilidad
        SET $pref = ?
        WHERE id_usuario = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("si", $value, $id_usuario);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Guardado correctamente"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Error al guardar"
    ]);

}
?>