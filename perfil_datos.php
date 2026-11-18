<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(["estado" => false, "mensaje" => "Error del servidor: " . $e->getMessage()]);
});

$conexion = new mysqli("localhost", "root", "", "otomi_502");
$conexion->set_charset("utf8mb4");
if ($conexion->query("SHOW COLUMNS FROM usuario LIKE 'avatar'")->num_rows === 0) {
    $conexion->query("ALTER TABLE usuario ADD COLUMN avatar MEDIUMTEXT NULL");
}


if (isset($_GET["salir"])) {
    session_destroy();
    echo json_encode(["estado" => true]);
    exit;
}

if (isset($_SESSION["id_usuario"])) {
    $id = (int)$_SESSION["id_usuario"];
} else {
    echo json_encode(["estado" => false, "mensaje" => "Debes iniciar sesión", "login" => true]);
    exit;
}

$stmt = $conexion->prepare("SELECT id_usuario, nombre, email, fecha_registro, fecha_nac, pais, nivel, avatar FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$u) {
    echo json_encode(["estado" => false, "mensaje" => "No se encontró el usuario con id $id"]);
    exit;
}

$q = $conexion->prepare("SELECT 1 FROM progreso WHERE id_usuario = ?");
$q->bind_param("i", $id);
$q->execute();
if ($q->get_result()->num_rows === 0) {
    $i = $conexion->prepare("INSERT INTO progreso (id_usuario, experiencia, racha, porcentaje) VALUES (?, 0, 0, 0)");
    $i->bind_param("i", $id);
    $i->execute();
    $i->close();
}
$q->close();

$stmt = $conexion->prepare("SELECT experiencia, racha, porcentaje FROM progreso WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc() ?: ["experiencia" => 0, "racha" => 0, "porcentaje" => 0];
$stmt->close();
$conexion->close();

$exp   = (int)$p["experiencia"];
$racha = (int)$p["racha"];
$pct   = (int)$p["porcentaje"];

$logros = [
    ["icono" => "🔥", "titulo" => "En llamas: alcanza una racha de 3 dias", "actual" => $racha, "meta" => 3],
    ["icono" => "📅", "titulo" => "Disciplinado: racha de 7 dias",           "actual" => $racha, "meta" => 7],
    ["icono" => "⚡", "titulo" => "Aprendiz: reune 100 EXP",                  "actual" => $exp,   "meta" => 100],
    ["icono" => "🌟", "titulo" => "Estudiante: reune 500 EXP",                "actual" => $exp,   "meta" => 500],
    ["icono" => "🏆", "titulo" => "Constante: completa el 50% del curso",     "actual" => $pct,   "meta" => 50],
    ["icono" => "🎓", "titulo" => "Graduado: completa el 100% del curso",     "actual" => $pct,   "meta" => 100],
];

echo json_encode([
    "estado"   => true,
    "usuario"  => $u,
    "progreso" => ["experiencia" => $exp, "racha" => $racha, "porcentaje" => $pct],
    "logros"   => $logros
]);