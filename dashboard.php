<?php
session_start();

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: admin_pedidos.php');
    exit;
} else {
    header('Location: ver_tienda.php');
    exit;
}
    