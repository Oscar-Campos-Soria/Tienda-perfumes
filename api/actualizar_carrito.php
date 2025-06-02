<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$cantidad = intval($data['cantidad'] ?? 0);

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($cantidad <= 0) {
    unset($_SESSION['carrito'][$id]);
} else {
    $_SESSION['carrito'][$id] = $cantidad;
}

echo json_encode(['success' => true, 'carrito' => $_SESSION['carrito']]);
