<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(["estado" => false, "mensaje" => "❌ Error del servidor: " . $e->getMessage()]);
});

require_once __DIR__ . "/db.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = $_POST;
}

$email      = trim($data["Email"] ?? $data["email"] ?? "");
$contrasena = $data["Contraseña"] ?? $data["contraseña"] ?? "";

if ($email === "" || $contrasena === "") {
    echo json_encode(["estado" => false, "mensaje" => "❌ Ingresa email y contraseña"]);
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();

$ok = false;
if ($u) {
    $hash = $u["contraseña"];
    if (password_verify($contrasena, $hash)) {
        $ok = true;
    } elseif (hash_equals((string)$hash, (string)$contrasena)) {
        // Contraseña antigua guardada sin cifrar: se acepta y se cifra
        $ok = true;
        $nuevo = password_hash($contrasena, PASSWORD_DEFAULT);
        $idCol = $u["Id_usuario"] ?? $u["id_usuario"] ?? $u["id"] ?? null;
        if ($idCol !== null) {
            $upd = $conexion->prepare("UPDATE usuario SET contraseña = ? WHERE email = ?");
            $upd->bind_param("ss", $nuevo, $email);
            $upd->execute();
            $upd->close();
        }
    }
}

if ($ok) {
    $idUsuario = $u["Id_usuario"] ?? $u["id_usuario"] ?? $u["id"];
    asegurar_progreso($conexion, $idUsuario);
    session_regenerate_id(true);
    $_SESSION["id_usuario"] = $idUsuario;
    $_SESSION["nombre"]     = $u["nombre"];
    $_SESSION["nivel"]      = $u["nivel"];
    echo json_encode(["estado" => true, "mensaje" => "✅ Bienvenido, " . $u["nombre"], "redirect" => "unidad1.html"]);
} else {
    echo json_encode(["estado" => false, "mensaje" => "❌ Email o contraseña incorrectos"]);
}

$stmt->close();
$conexion->close();
