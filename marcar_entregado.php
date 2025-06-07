<?php
session_start();
include 'db.php';

// Verifica que el usuario sea administrador
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['entregar'])) {
    $id = intval($_GET['entregar']);

    // Usa prepared statement para seguridad
    $stmt = $conn->prepare("UPDATE pedido SET Entregado = 1 WHERE IdPedido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Redirige al panel de administración de pedidos
header('Location: admin_pedidos.php');
exit;
