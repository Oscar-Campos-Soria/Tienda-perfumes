<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Validar sesión activa de cliente
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['producto']) || empty($input['producto'])) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío o mal enviado']);
    exit;
}

// Validar método de pago
$IdMetodoPago = $input['metodo_pago'] ?? 'efectivo';
if (!in_array($IdMetodoPago, ['efectivo', 'transferencia'])) {
    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
    exit;
}

// Variables necesarias
$idUsuario = $_SESSION['id_usuario'] ?? '';  // Usar el ID de usuario desde la sesión
if (trim($idUsuario) === '') {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no disponible']);
    exit;
}

$referencia = strtoupper(uniqid("PED"));
$fecha = date('Y-m-d H:i:s');
$total = 0.0;
$carrito = $input['producto'];

// Calcular total de producto (usando tabla "producto" y campos CamelCase)
foreach ($carrito as $item) {
    $id = intval($item['id']);
    $cantidad = intval($item['cantidad']);
    if ($cantidad <= 0) continue;

    $stmt = $conn->prepare("SELECT Precio FROM producto WHERE IdProducto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && isset($res['Precio'])) {
        $total += floatval($res['Precio']) * $cantidad;
    }
}

if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total inválido o vacío']);
    exit;
}

// Insertar pedido principal en tabla "pedido"
$stmt = $conn->prepare("INSERT INTO pedido (IdUsuario, Total, Referencia, FechaPedido, IdMetodoPago) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar el pedido: ' . $conn->error]);
    exit;
}
$stmt->bind_param("idsss", $idUsuario, $total, $referencia, $fecha, $metodoPago);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar el pedido: ' . $stmt->error]);
    exit;
}
$idPedido = $stmt->insert_id;
$stmt->close();

// Insertar productos del pedido y actualizar inventario (tablas y campos actualizados)
foreach ($carrito as $item) {
    $idProducto = intval($item['id']);
    $cantidad = intval($item['cantidad']);
    if ($cantidad <= 0) continue;

    $stmt = $conn->prepare("SELECT Precio FROM producto WHERE IdProducto = ?");
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && isset($res['Precio'])) {
        $precioUnitario = floatval($res['Precio']);

        // Insertar detalle del pedido
        $stmt = $conn->prepare("INSERT INTO detalle_pedido (IdPedido, IdProducto, Cantidad, PrecioUnitario) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar detalle: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iiid", $idPedido, $idProducto, $cantidad, $precioUnitario);
        $stmt->execute();
        $stmt->close();

        // Actualizar inventario
        $stmt = $conn->prepare("UPDATE producto SET Cantidad = Cantidad - ? WHERE IdProducto = ?");
        $stmt->bind_param("ii", $cantidad, $idProducto);
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
    'metodo_pago' => $metodoPago,
    'total' => number_format($total, 2),
    'instrucciones_pago' => $metodoPago === 'transferencia' ? [
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
], JSON_UNESCAPED_UNICODE);
?>

