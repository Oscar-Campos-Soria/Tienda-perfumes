<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID de pedido inválido.");
}

// Preparar consulta para evitar inyección SQL
$stmt = $conn->prepare(
    "SELECT p.*, u.Username, m.Nombre AS MetodoPago
     FROM pedido p
     JOIN usuario u ON p.IdUsuario = u.IdUsuario
     JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
     WHERE p.IdPedido = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Preparar consulta para detalles del pedido
$stmt = $conn->prepare(
    "SELECT pd.*, pr.Nombre
     FROM pedidodetalle pd
     JOIN producto pr ON pd.IdProducto = pr.IdProducto
     WHERE pd.IdPedido = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$detalles = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Detalle del Pedido</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 30px; }
        h2 { color: #333; }
        .info { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info p { margin: 5px 0; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #eee; }
        .volver {
            margin-top: 25px;
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<h2>🧾 Detalles del Pedido #<?= htmlspecialchars($pedido['IdPedido']) ?></h2>

<div class="info">
    <p><strong>Usuario:</strong> <?= htmlspecialchars($pedido['Username']) ?></p>
    <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>
    <p><strong>Referencia:</strong> <?= htmlspecialchars($pedido['Referencia']) ?></p>
    <p><strong>Método de pago:</strong> <?= htmlspecialchars($pedido['MetodoPago']) ?></p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['FechaPedido']) ?></p>
</div>

<h3>📦 Productos del pedido</h3>
<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($fila = $detalles->fetch_assoc()):
            $subtotal = $fila['Cantidad'] * $fila['PrecioUnitario'];
        ?>
        <tr>
            <td><?= htmlspecialchars($fila['Nombre']) ?></td>
            <td><?= intval($fila['Cantidad']) ?></td>
            <td>$<?= number_format($fila['PrecioUnitario'], 2) ?></td>
            <td>$<?= number_format($subtotal, 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<a href="consultar_compras.php" class="volver">← Volver</a>

</body>
</html>
