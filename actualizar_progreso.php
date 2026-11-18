<?php
session_start();
require_once 'db.php';
header("Content-Type: application/json");



$conexion->set_charset("utf8mb4");

// Verificar conexión
if ($conexion->connect_error) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error de conexión"
    ]);
    exit;
}

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Debes iniciar sesión"
    ]);
    exit;
}

// Recibir datos JSON
$datos = json_decode(
    file_get_contents("php://input"),
    true
);

// Obtener experiencia
$puntajeGanado = (int)($datos["experienciaGanada"] ?? 0);

$idUsuario = (int)$_SESSION["id_usuario"];

// Verificar experiencia
if ($puntajeGanado <= 0) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Experiencia inválida"
    ]);
    exit;
}

// Consulta SQL
$sql = "
UPDATE progreso
SET
    experiencia = experiencia + ?,
    racha = racha + 1,
    porcentaje = LEAST(porcentaje + 5, 100)
WHERE id_usuario = ?
";

// Preparar consulta
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al preparar consulta"
    ]);
    exit;
}

// Vincular parámetros
$stmt->bind_param(
    "ii",
    $puntajeGanado,
    $idUsuario
);

// Ejecutar
$resultado = $stmt->execute();

echo json_encode([
    "success" => $resultado,
    "mensaje" => $resultado
        ? "Progreso actualizado correctamente"
        : "Error al actualizar progreso"
]);

$stmt->close();
$conexion->close();
?>