<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Validar sesión activa de cliente
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['productos']) || empty($input['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío o mal enviado']);
    exit;
}

// Validar método de pago
$metodo_pago = $input['metodo_pago'] ?? 'efectivo';
if (!in_array($metodo_pago, ['efectivo', 'transferencia'])) {
    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
    exit;
}

// Variables necesarias
$usuario = $_SESSION['username'] ?? '';
if (trim($usuario) === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre de usuario no puede estar vacío']);
    exit;
}

$referencia = strtoupper(uniqid("PED"));
$fecha = date('Y-m-d H:i:s');
$total = 0.0;
$carrito = $input['productos'];

// Calcular total de productos
foreach ($carrito as $item) {
    $id = intval($item['id']);
    $stmt = $conn->prepare("SELECT Precio FROM productos WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && isset($res['Precio'])) {
        $total += floatval($res['Precio']) * intval($item['cantidad']);
    }
}

if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total inválido o vacío']);
    exit;
}

// Insertar pedido principal
$stmt = $conn->prepare("INSERT INTO pedidos (usuario, total, referencia, fecha_pedido, metodo_pago) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar el pedido: ' . $conn->error]);
    exit;
}
$stmt->bind_param("sdsss", $usuario, $total, $referencia, $fecha, $metodo_pago);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar el pedido: ' . $stmt->error]);
    exit;
}
$pedido_id = $stmt->insert_id;
$stmt->close();

// Insertar productos del pedido y actualizar inventario
foreach ($carrito as $item) {
    $producto_id = intval($item['id']);
    $cantidad = intval($item['cantidad']);

    $stmt = $conn->prepare("SELECT Precio FROM productos WHERE Id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && isset($res['Precio'])) {
        $precio_unitario = floatval($res['Precio']);

        // Insertar detalle del pedido
        $stmt = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar detalle: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio_unitario);
        $stmt->execute();
        $stmt->close();

        // Actualizar inventario
        $stmt = $conn->prepare("UPDATE productos SET Cantidad = Cantidad - ? WHERE Id = ?");
        $stmt->bind_param("ii", $cantidad, $producto_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Limpiar carrito de la sesión si se usó (opcional)
$_SESSION['carrito'] = [];

// Preparar respuesta para frontend
echo json_encode([
    'success' => true,
    'message' => 'Compra realizada con éxito',
    'referencia' => $referencia,
    'metodo_pago' => $metodo_pago,
    'total' => number_format($total, 2),
    'instrucciones_pago' => $metodo_pago === 'transferencia' ? [
        'Banco' => 'BANCO FICTICIO S.A.',
        'Cuenta' => '1234 5678 9012 3456',
        'CLABE' => '012345678901234567',
        'Beneficiario' => 'Perfime Tienda Virtual',
        'Referencia' => $referencia,
        'Nota' => 'Una vez realizada la transferencia, recibirás un correo de confirmación.',
        'Confirmacion' => 'Confirmaremos tu pedido en cuanto verifiquemos la transferencia bancaria.'
    ] : [
        'Instrucciones' => 'Presenta esta referencia al momento de pagar en efectivo en tienda física.',
        'Referencia' => $referencia,
        'Nota' => 'También recibirás un correo con la información de tu compra.',
        'Confirmacion' => 'Se te cobrará al momento de la entrega o al recoger tu pedido.'
    ]
]);
?>
