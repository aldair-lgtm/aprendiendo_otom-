<?php
// Datos de conexión
$servername = "localhost";
$username = "root"; // usuario por defecto en XAMPP
$password = "";     // contraseña vacía por defecto
$dbname = "otomi_502";
// Crear conexión
$conexion = new mysqli($servername, $username, $password, $dbname);
// Acentos y ñ (la columna "contraseña" la necesita)
$conexion->set_charset("utf8mb4");

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Crea la fila de progreso del usuario si todavía no existe
function asegurar_progreso($conexion, $idUsuario) {
    $idUsuario = (int)$idUsuario;
    $q = $conexion->prepare("SELECT 1 FROM progreso WHERE id_usuario = ?");
    $q->bind_param("i", $idUsuario);
    $q->execute();
    $existe = $q->get_result()->num_rows > 0;
    $q->close();
    if (!$existe) {
        $i = $conexion->prepare("INSERT INTO progreso (id_usuario, experiencia, racha, porcentaje) VALUES (?, 0, 0, 0)");
        $i->bind_param("i", $idUsuario);
        $i->execute();
        $i->close();
    }
}
?>
