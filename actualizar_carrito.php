<?php 
session_start();

// Verificar que el usuario sea cliente
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    http_response_code(403);
    echo "Acceso denegado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar entrada
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

    if ($id > 0) {
        if ($cantidad > 0) {
            // Inicializar carrito si no existe
            if (!isset($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            // Actualizar cantidad del producto
            $_SESSION['carrito'][$id] = $cantidad;
            echo "Cantidad actualizada";
        } else {
            // Remover producto si cantidad es 0 o menor
            if (isset($_SESSION['carrito'][$id])) {
                unset($_SESSION['carrito'][$id]);
                echo "Producto eliminado";
            } else {
                echo "Producto no existe en el carrito";
            }
        }
    } else {
        http_response_code(400);
        echo "Datos inválidos";
    }
} else {
    http_response_code(405);
    echo "Método no permitido";
}
  