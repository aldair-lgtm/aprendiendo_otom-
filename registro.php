<?php
header("Content-Type: application/json");
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(["estado" => false, "mensaje" => "❌ Error del servidor: " . $e->getMessage()]);
});


require_once __DIR__ . "/db.php";

$data = json_decode(file_get_contents("php://input"), true);


$nombre = $data['nombre'];
$email = $data['email'];
$contrasena = $data['contrasena'];
$fecha_nac = $data['fecha_nac'];
$pais = $data['pais'];
$nivel = $data['nivel'];



$fecha_registro = date("Y-m-d");

$contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
$nivel = (int)$nivel;


$sql = "INSERT INTO usuario
(nombre,email,contraseña,fecha_registro,fecha_nac,pais,nivel)
VALUES (?,?,?,?,?,?,?)";


$stmt = $conexion->prepare($sql);


$stmt->bind_param(
    "ssssssi",
    $nombre,
    $email,
    $contrasena,
    $fecha_registro,
    $fecha_nac,
    $pais,
    $nivel
);

if($stmt->execute()){
    $nuevoId = $conexion->insert_id;

    asegurar_progreso($conexion, $nuevoId);
    session_start();
    session_regenerate_id(true);
    $_SESSION["id_usuario"] = $nuevoId;
    $_SESSION["nombre"]     = $nombre;
    $_SESSION["nivel"]      = $nivel;

    echo json_encode([
        "estado" => true,
        "mensaje" => "✅ Usuario registrado correctamente",
        "redirect" => "unidad1.html"
    ]);

}else{
    echo json_encode([
        "estado" => false,
        "mensaje" => "❌ Error al registrar usuario: " . $stmt->error
    ]);
}

$stmt->close();
$conexion->close();
?>