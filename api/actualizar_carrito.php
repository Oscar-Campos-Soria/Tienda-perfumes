<?php 
session_start();
header('Content-Type: application/json');

// Verificar que la sesión sea de un cliente autorizado
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Leer datos JSON enviados en el cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Validar que los datos sean correctos
if (!$data || !isset($data['id']) || !isset($data['cantidad'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
    exit;
}

// Validar ID y cantidad
$id = intval($data['id'] ?? 0);
$cantidad = intval($data['cantidad'] ?? 0);

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Validar ID válido
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Verificar que el producto exista en la base de datos
require_once 'db.php'; // Asegúrate de tener la conexión a la base de datos
$stmt = $conn->prepare("SELECT IdProducto FROM producto WHERE IdProducto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
    exit;
}

// Actualizar carrito en sesión: si cantidad <= 0 se elimina el producto
if ($cantidad <= 0) {
    unset($_SESSION['carrito'][$id]);
} else {
    $_SESSION['carrito'][$id] = $cantidad;
}

// Responder con éxito y el carrito actualizado
echo json_encode(['success' => true, 'carrito' => $_SESSION['carrito']]);
