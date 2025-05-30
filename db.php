<?php
$host = "127.0.0.1:3308";  // CAMBIADO: puerto correcto
$user = "root";
$password = "";
$db = "tienda_perfumes";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
