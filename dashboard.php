<?php
session_start();

// Verificar que haya sesión iniciada con rol
if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$rol = strtolower($_SESSION['role']); // Normalizar a minúsculas

// Redirigir según rol
switch ($rol) {
    case 'administrador':
        header('Location: admin_pedidos.php'); // Página administrativa
        break;
    case 'cliente':
        header('Location: ver_tienda.php'); // Vista cliente
        break;
    default:
        // Si el rol no es reconocido, redirigir a login por seguridad
        header('Location: login.php');
        break;
}

exit; // Terminar script para evitar ejecución posterior
?>
