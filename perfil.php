<?php
session_start();
ini_set('display_errors', '0');
set_exception_handler(function ($e) {
    die('Error del servidor: ' . htmlspecialchars($e->getMessage()));
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = new mysqli("localhost", "root", "", "otomi_502");
$conexion->set_charset("utf8mb4");

if (isset($_GET["salir"])) {
    session_destroy();
    header("Location: login.html");
    exit;
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}
$id = (int)$_SESSION["id_usuario"];

$stmt = $conexion->prepare("SELECT id_usuario, nombre, email, fecha_registro, fecha_nac, pais, nivel FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$u) { die("No se encontró el usuario con id " . $id . " en la tabla usuario."); }

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
    ["🔥", "En llamas: alcanza una racha de 3 días", $racha, 3],
    ["📅", "Disciplinado: racha de 7 días",           $racha, 7],
    ["⚡", "Aprendiz: reúne 100 EXP",                  $exp,   100],
    ["🌟", "Estudiante: reúne 500 EXP",                $exp,   500],
    ["🏆", "Constante: completa el 50% del curso",     $pct,   50],
    ["🎓", "Graduado: completa el 100% del curso",     $pct,   100],
];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fecha($f) {
    if (!$f) return "—";
    $meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
    $t = strtotime($f);
    return $t ? date("j", $t) . " de " . $meses[(int)date("n", $t) - 1] . " de " . date("Y", $t) : $f;
}
$inicial = mb_strtoupper(mb_substr($u["nombre"], 0, 1, "UTF-8"), "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi perfil</title>
<style>
:root{--oscuro:#4D52B4;--medio:#4E9CE8;--vivo:#70D6C5;--suave:#CAE5BC;--hielo:#E1EDD4;--texto:#181616;--gris:#6b7280}
*{box-sizing:border-box}
body{margin:0;font-family:"Segoe UI",system-ui,sans-serif;color:var(--texto);
  background:linear-gradient(160deg,var(--oscuro) 0%,var(--medio) 40%,var(--vivo) 75%,var(--suave) 100%);
  min-height:100vh;background-attachment:fixed}
.top{display:flex;justify-content:space-between;align-items:center;padding:14px 28px;background:rgba(255,255,255,.15)}
.top a{color:#fff;font-weight:700;text-decoration:none}
.wrap{max-width:1000px;margin:28px auto;padding:0 16px;display:grid;grid-template-columns:2fr 1fr;gap:22px;align-items:start}
@media(max-width:800px){.wrap{grid-template-columns:1fr}}
.card{background:var(--hielo);border-radius:1rem;padding:22px;box-shadow:0 20px 40px rgba(77,82,180,.3)}
.banner-wrap{position:relative;margin-bottom:60px}
.banner{height:170px;border-radius:1rem;background:linear-gradient(135deg,var(--oscuro),var(--medio))}
.avatar{width:130px;height:130px;border-radius:50%;background:var(--vivo);border:5px solid var(--hielo);display:flex;align-items:center;justify-content:center;font-size:3.4rem;font-weight:800;color:#fff;position:absolute;left:24px;bottom:-45px}
h1{margin:0;font-size:1.9rem;text-transform:uppercase;color:var(--oscuro)}
h2{margin:0 0 14px;color:var(--oscuro)}
.sub{color:var(--gris);margin:2px 0 8px}
.datos{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;margin-top:14px}
.datos b{display:block;font-size:.75rem;color:var(--gris);text-transform:uppercase;letter-spacing:.04em}
hr{border:0;border-top:2px solid rgba(77,82,180,.2);margin:22px 0}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.stat{background:#fff;border:2px solid var(--suave);border-radius:14px;padding:14px;display:flex;gap:12px;align-items:center}
.stat .ico{font-size:1.8rem}.stat .n{font-size:1.4rem;font-weight:800}.stat .l{font-size:.85rem;color:var(--gris)}
.barra{height:14px;background:#fff;border-radius:99px;overflow:hidden;border:2px solid var(--suave)}
.barra i{display:block;height:100%;background:linear-gradient(90deg,var(--vivo),var(--medio))}
.logro{display:flex;gap:14px;align-items:center;background:#fff;border-radius:14px;padding:14px;margin-bottom:10px}
.logro.hecho{border:2px solid var(--vivo)}
.logro .ico{font-size:2.2rem}.logro.pend .ico{filter:grayscale(1);opacity:.5}
.logro .info{flex:1}.logro .info b{display:block;margin-bottom:6px}
.logro small{color:var(--gris);font-weight:700}
.btn{display:block;padding:12px;border-radius:12px;background:var(--vivo);font-weight:700;margin-top:12px;text-align:center;text-decoration:none;color:var(--texto)}
.btn:hover{filter:brightness(.95)}
</style>
</head>
<body>
<div class="top">
  <a href="unidad1.html">← Volver a las lecciones</a>
  <a href="perfil.php?salir=1">Cerrar sesión</a>
</div>

<div class="wrap">
  <div class="card">
    <div class="banner-wrap">
      <div class="banner"></div>
      <div class="avatar"><?= h($inicial) ?></div>
    </div>
    <h1><?= h($u["nombre"]) ?></h1>
    <p class="sub"><?= h($u["email"]) ?></p>
    <p class="sub">Se unió el <?= h(fecha($u["fecha_registro"])) ?></p>

    <div class="datos">
      <div><b>País</b><?= h($u["pais"]) ?></div>
      <div><b>Fecha de nacimiento</b><?= h(fecha($u["fecha_nac"])) ?></div>
      <div><b>Nivel</b><?= h($u["nivel"]) ?></div>
      <div><b>ID de usuario</b>#<?= h($u["id_usuario"]) ?></div>
    </div>

    <hr>
    <h2>Estadísticas</h2>
    <div class="stats">
      <div class="stat"><span class="ico">🔥</span><div><div class="n"><?= $racha ?></div><div class="l">día<?= $racha == 1 ? "" : "s" ?> de racha</div></div></div>
      <div class="stat"><span class="ico">⚡</span><div><div class="n"><?= $exp ?></div><div class="l">EXP totales</div></div></div>
      <div class="stat"><span class="ico">📈</span><div><div class="n"><?= $pct ?>%</div><div class="l">progreso del curso</div></div></div>
      <div class="stat"><span class="ico">🎯</span><div><div class="n"><?= h($u["nivel"]) ?></div><div class="l">nivel actual</div></div></div>
    </div>

    <hr>
    <h2>Logros</h2>
    <?php foreach ($logros as [$ico, $titulo, $actual, $meta]):
        $hecho = $actual >= $meta;
        $w = min(100, round($actual / $meta * 100)); ?>
      <div class="logro <?= $hecho ? "hecho" : "pend" ?>">
        <span class="ico"><?= $ico ?></span>
        <div class="info"><b><?= h($titulo) ?></b><div class="barra"><i style="width:<?= $w ?>%"></i></div></div>
        <small><?= min($actual, $meta) ?>/<?= $meta ?></small>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Progreso del curso</h2>
    <div class="barra"><i style="width:<?= min(100, $pct) ?>%"></i></div>
    <p class="sub"><?= $pct ?>% completado</p>
    <a class="btn" href="unidad1.html">Continuar aprendiendo</a>
  </div>
</div>
</body>
</html>
