<?php
// Configuración de conexión a la base de datos (puerto personalizado)
$host = "127.0.0.1:3308";
$user = "root";
$password = "";
$db = "tienda_perfumes";

// Crear conexión
$conn = new mysqli($host, $user, $password, $db);

// Verificar conexión
if ($conn->connect_error) {
    // En entorno de desarrollo, puedes mostrar error detallado
    // En producción, mejor manejarlo de forma más segura
    die("Error de conexión a la base de datos. Por favor, intenta más tarde.");
}

// Configurar charset para evitar problemas con acentos y caracteres especiales
$conn->set_charset("utf8mb4");
?>
