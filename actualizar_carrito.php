<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    http_response_code(403);
    echo "Acceso denegado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

    if ($id > 0) {
        if ($cantidad > 0) {
            $_SESSION['carrito'][$id] = $cantidad;
            echo "Cantidad actualizada";
        } else {
            unset($_SESSION['carrito'][$id]);
            echo "Producto eliminado";
        }
    } else {
        http_response_code(400);
        echo "Datos inválidos";
    }
} else {
    http_response_code(405);
    echo "Método no permitido";
}
?>
