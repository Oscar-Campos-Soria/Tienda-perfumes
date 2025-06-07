<?php
header('Content-Type: application/json');
require_once '../db.php';

$idPedido = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;

if ($idPedido <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de pedido no válido o no proporcionado']);
    exit;
}

try {
    // Preparar la consulta con parámetros
    $sql = "SELECT p.Nombre, dp.Cantidad, dp.PrecioUnitario
            FROM detalle_pedido dp
            JOIN producto p ON dp.IdProducto = p.IdProducto
            WHERE dp.IdPedido = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Verificar si se obtuvieron resultados
    if ($res->num_rows > 0) {
        $detalles = [];
        while ($row = $res->fetch_assoc()) {
            $detalles[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $detalles], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron detalles para este pedido.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalles: ' . $e->getMessage()]);
}
?>
