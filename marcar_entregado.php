<?php
session_start();
include 'db.php';

// Verifica si es administrador
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Verifica que haya un ID válido para marcar como entregado
if (isset($_GET['entregar'])) {
    $id = intval($_GET['entregar']);
    $sql = "UPDATE pedidos SET entregado = 1 WHERE id = $id";
    $conn->query($sql);
}

// Redirecciona de nuevo al panel de pedidos
header('Location: admin_pedidos.php');
exit;
?>
