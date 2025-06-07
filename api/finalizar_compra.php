<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Verificar sesión activa de cliente
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos JSON enviados en el cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['producto']) || empty($input['producto'])) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío o mal enviado']);
    exit;
}

// Validar método de pago
$metodoPagoTxt = $input['metodo_pago'] ?? 'efectivo';
$metodosPermitidos = ['efectivo', 'transferencia'];
if (!in_array($metodoPagoTxt, $metodosPermitidos)) {
    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
    exit;
}

// Relación método de pago texto <-> id
$metodoPagoIds = [
    'efectivo' => 1, // ajusta estos IDs según tu catálogo
    'transferencia' => 2,
];
$metodoPago = $metodoPagoIds[$metodoPagoTxt];

// Variables necesarias
$idUsuario = $_SESSION['id_usuario'] ?? null;
if (!$idUsuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no identificado']);
    exit;
}

$referencia = strtoupper(uniqid("PED"));
$fecha = date('Y-m-d H:i:s');
$total = 0.0;
$carrito = $input['producto'];

// Iniciar la transacción
try {
    $conn->begin_transaction();

    // Validar stock y calcular total
    foreach ($carrito as $item) {
        $id = intval($item['id']);
        $cantidad = intval($item['cantidad']);
        if ($cantidad <= 0) continue;

        // Verificar stock disponible
        $stmt = $conn->prepare("SELECT Precio, Cantidad FROM producto WHERE IdProducto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res || $res['Cantidad'] < $cantidad) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Stock insuficiente para el producto con ID $id"]);
            exit;
        }
        $total += floatval($res['Precio']) * $cantidad;
    }

    // Verificar total válido
    if ($total <= 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Total inválido o vacío']);
        exit;
    }

    // ===== DEBUG DE VALORES ANTES DE INSERT =====
    file_put_contents("debug_bind.txt", print_r([
        'idUsuario'       => $idUsuario,
        'total'           => $total,
        'referencia'      => $referencia,
        'fecha'           => $fecha,
        'metodoPago'      => $metodoPago,
        'idEstatus'       => 1,           // Estatus "pendiente" (ajusta si tienes catálogo)
        'idUsuarioCrea'   => $idUsuario,  // Puede ser el mismo usuario que crea el pedido
    ], true));

    // ===== INSERT EN TABLA PEDIDO =====
    $idEstatus = 1; // Estatus "pendiente", ajusta según tu catálogo

    // Verifica que NINGÚN valor sea NULL
    $insertData = [$idUsuario, $total, $referencia, $fecha, $metodoPago, $idEstatus, $idUsuario];
    if (in_array(null, $insertData, true)) {
        throw new Exception("Algún dato del pedido es NULL: " . print_r($insertData, true));
    }

    $stmt = $conn->prepare("INSERT INTO pedido (IdUsuario, Total, Referencia, FechaPedido, IdMetodoPago, IdEstatus, IdUsuarioCrea) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Error preparando el insert de pedido: " . $conn->error);
    }
    $stmt->bind_param("idssiii", $idUsuario, $total, $referencia, $fecha, $metodoPago, $idEstatus, $idUsuario);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando el insert de pedido: " . $stmt->error);
    }

    $idPedido = $stmt->insert_id;
    $stmt->close();

    // Insertar los detalles del pedido y actualizar el inventario
    foreach ($carrito as $item) {
        $idProducto = intval($item['id']);
        $cantidad = intval($item['cantidad']);
        if ($cantidad <= 0) continue;

        // Obtener el precio unitario
        $stmt = $conn->prepare("SELECT Precio FROM producto WHERE IdProducto = ?");
        $stmt->bind_param("i", $idProducto);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $precioUnitario = floatval($res['Precio']);

        // Insertar el detalle del pedido en "detalle_pedido"
        $stmt = $conn->prepare("INSERT INTO detalle_pedido (IdPedido, IdProducto, Cantidad, PrecioUnitario) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Error al preparar detalle: ' . $conn->error);
        }
        $stmt->bind_param("iiid", $idPedido, $idProducto, $cantidad, $precioUnitario);
        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando el insert de detalle: ' . $stmt->error);
        }
        $stmt->close();

        // Actualizar el inventario (restar la cantidad comprada)
        $stmt = $conn->prepare("UPDATE producto SET Cantidad = Cantidad - ?, IdUsuarioModifica = ? WHERE IdProducto = ?");
        $stmt->bind_param("iii", $cantidad, $idUsuario, $idProducto);
        if (!$stmt->execute()) {
            throw new Exception('Error actualizando inventario: ' . $stmt->error);
        }
        $stmt->close();
    }

    // Confirmar la transacción si todo va bien
    $conn->commit();

    // Preparar la respuesta para el frontend
    echo json_encode([
        'success' => true,
        'message' => 'Compra realizada con éxito',
        'referencia' => $referencia,
        'metodo_pago' => $metodoPagoTxt,
        'total' => number_format($total, 2),
        'instrucciones_pago' => $metodoPagoTxt === 'transferencia' ? [
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

} catch (Exception $e) {
    // Si hay un error, hacer rollback de toda la transacción
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
