<?php
header('Content-Type: application/json');
require_once '../db.php';

$pedido_id = intval($_GET['pedido_id'] ?? 0);

try {
    $sql = "SELECT p.Nombre, dp.cantidad, dp.precio_unitario
            FROM detalle_pedidos dp
            JOIN productos p ON dp.producto_id = p.Id
            WHERE dp.pedido_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $detalles = [];
    while ($row = $res->fetch_assoc()) {
        $detalles[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $detalles]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalles']);
}
